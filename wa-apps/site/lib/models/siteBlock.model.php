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
        if ($cache = wa('site')->getCache()) {
            $cache_key = 'block_' . $value;
            $result = $cache->get($cache_key);
            if (!$result) {
                $result = parent::getById($value);
                if ($result) {
                    $cache->set($cache_key, $result, 86400);
                }
            }
            return $result;
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
        if ($cache = wa('site')->getCache()) {
            $cache->delete('block_' . $value);
        }
        return parent::deleteById($value);
    }

    public function updateById($id, $data, $options = null, $return_object = false)
    {
        if ($cache = wa('site')->getCache()) {
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
        $data['sort'] = (int) $this->select("MAX(sort)")->fetchField() + 1;
        return $this->insert($data);
    }

    /**
     * @param int $id
     * @param int $sort
     * @return bool
     */
    public function move($id, $sort)
    {
        $sort = (int)$sort;
        if (!$id || $sort < 1) {
            return false;
        }

        $blocks = $this->select('id, sort')->order('sort')->fetchAll('id', true);
        if (empty($blocks[$id])) {
            return false;
        }
        $block_ids = $blocks;
        unset($block_ids[$id]);
        $block_ids = array_keys($block_ids);
        array_splice($block_ids, $sort - 1, 0, array($id));
        foreach($block_ids as $i => $id) {
            $new_sort = $i + 1;
            if ($blocks[$id] != $new_sort) {
                $this->updateById($id, array(
                    'sort' => $new_sort,
                ));
            }
        }
        return true;
    }
}