<?php 

class siteBlockModel extends waModel
{
    protected $table = 'site_block';


    /**
     * Returns data from table record with specified value in id field ($this->id).
     *
     * @param int|array $value Field value or array of values
     * @return array|null
     */
    public function getById($value)
    {
        if (is_numeric($value)) {
            if ($cache = wa()->getCache()) {
                $cache_key = 'block_' . $value;
                $result = $cache->get($cache_key);
                if (!$result) {
                    $result = parent::getById($value);
                    if ($result) {
                        $cache->set($cache_key, $result, 86400);
                    }
                }
                return $result;
            }
        } else {
            return parent::getById($value);
        }
    }

    /**
     * @param int|array $value
     * @return bool
     */
    public function deleteById($value)
    {
        if ($cache = wa()->getCache()) {
            $cache->delete('block_' . $value);
        }
        return parent::deleteById($value);
    }

    public function updateById($id, $data, $options = null, $return_object = false)
    {
        if ($cache = wa()->getCache()) {
            $cache->delete('block_' . $id);
        }
        return parent::updateById($id, $data, $options, $return_object);
    }

    /**
     * @param array $data
     * @return bool|int
     */
    public function add($data)
    {
        if (!isset($data['create_datetime'])) {
            $data['create_datetime'] = date('Y-m-d H:i:s');
        }
        $data['sort'] = $this->select("MAX(sort)")->fetchField();
        return $this->insert($data);
    }

    /**
     * @param int $id
     * @param int $sort
     * @return bool
     */
    public function move($id, $sort)
    {
        if (!$id) {
            return false;
        }
        $sort = (int)$sort;
        if ($row = $this->getByField($this->id, $id)) {
            $sql = "UPDATE ".$this->table." SET sort = sort ";
            if ($row['sort'] < $sort) {
                $sql .= "- 1 WHERE sort > ".$row['sort']." AND sort <= ".$sort;
            } elseif ($row['sort'] > $sort) {
                $sql .= "+ 1 WHERE sort >= ".$sort." AND sort < ".$row['sort'];
            }
            return $this->exec($sql) && $this->updateById($id, array('sort' => $sort));
        }
        return false;
    }
}