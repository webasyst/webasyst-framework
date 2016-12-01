<?php

class teamParamsModel extends waModel
{
    protected $relation_id = '';


    /**
     * @param int $id relation id
     * @param string [string] $params
     */
    public function set($id, $params)
    {
        $delete = array();
        foreach ($params as $name => $value) {
            if ($value === null) {
                $delete[] = $name;
            } else {
                $this->insert(
                    array(
                        $this->relation_id => $id,
                        'name' => $name,
                        'value' => $value
                    ),
                    1
                );
            }
        }
        if ($delete) {
            $this->deleteByField(array(
                $this->relation_id => $id,
                'name' => $delete
            ));
        }
    }

    /**
     * @param $id relation id
     * @param array $params key-value map
     */
    public function add($id, $params)
    {
        $delete = array();
        $params = (array) $params;
        foreach ($params as $name => $value) {
            if ($value === null) {
                $delete[] = $name;
            } else {
                $this->insert(
                    array(
                        $this->relation_id => $id,
                        'name' => $name,
                        'value' => $value
                    ),
                    1
                );
            }
        }
        if ($delete) {
            $this->deleteByField(array($this->relation_id => $id, 'name' => $delete));
        }
    }

    /**
     * @param $id relation id
     * @param string $key
     * @param string $value
     */
    public function addOne($id, $key, $value)
    {
        return $this->add($id, array($key => $value));
    }

    /**
     * @param $id relation id
     * @param string|array[]string $key
     */
    public function delete($id, $key)
    {
        $keys = array_map('strval', (array) $key);
        $this->deleteByField(array($this->relation_id => $id, 'name' => $keys));
    }

    /**
     * @param $id relation id
     */
    public function deleteAll($id)
    {
        $this->deleteByField($this->relation_id, $id);
    }

    /**
     * @param int|array $id relation id(s)
     * @return array key-value in case of relation id is int,
     *  otherwise indexed by relation id array of array of key-value
     */
    public function get($id)
    {
        $ids = array_map('intval', (array) $id);
        $params = array_fill_keys($ids, array());
        foreach ($this->getByField(array($this->relation_id => $ids), true) as $item) {
            $params[$item[$this->relation_id]][$item['name']] = $item['value'];
        }
        return !is_array($id) ? $params[(int) $id] : $params;
    }
}
