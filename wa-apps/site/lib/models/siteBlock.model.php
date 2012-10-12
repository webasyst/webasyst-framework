<?php 

class siteBlockModel extends waModel
{
    protected $table = 'site_block';

    public function add($data)
    {
        if (!isset($data['create_datetime'])) {
            $data['create_datetime'] = date('Y-m-d H:i:s');
        }
        $data['sort'] = $this->select("MAX(sort)")->fetchField();
        return $this->insert($data);
    }

    public function move($id, $sort)
    {
        if (!$id) {
            return false;
        }
        $sort = (int)$sort;
        if ($row = $this->getById($id)) {
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