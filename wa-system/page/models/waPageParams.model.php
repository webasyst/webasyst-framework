<?php

class waPageParamsModel extends waModel
{
    public function getById($id)
    {
        $sql = "SELECT name, value FROM ".$this->table." WHERE page_id = i:id";
        return $this->query($sql, array('id' => $id))->fetchAll('name', true);
    }

    public function save($id, $params)
    {
        $old_params = $this->getById($id);

        if ($params || $old_params) {
            $add = array();
            $update = array();
            foreach ($params as $param => $value) {
                if (isset($old_params[$param])) {
                    if ($value != $old_params[$param]) {
                        $update[$param] = $value;
                    }
                    unset($old_params[$param]);
                } else {
                    $add[$param] = $value;
                }
            }
            $delete = $old_params;
            if ($delete) {
                $this->deleteByField(array('page_id' => $id, 'name' => array_keys($delete)));
            }
            if ($add) {
                foreach ($add as $name => $value) {
                    $this->insert(array('page_id' => $id, 'name' => $name, 'value' => $value));
                }
            }
            if ($update) {
                foreach ($update as $name => $value) {
                    $this->updateByField(array('page_id' => $id, 'name' => $name), array('value' => $value));
                }
            }
        }
    }
}
