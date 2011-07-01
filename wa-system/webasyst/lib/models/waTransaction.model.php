<?php

class waTransactionModel extends waModel
{
    protected $table = 'wa_transaction';

    public function insert($data, $type=0)
    {
        if (!isset($data['application_id'])) {
            $data['application_id'] = waSystem::getInstance()->getApp();
        }
        if (!isset($data['create_datetime'])) {
            $data['create_datetime'] = date('Y-m-d H:i:s');
        }
        return parent::insert($data);
    }

    public function getByFields($conditions)
    {
        $result = array();
        if (is_array($conditions)) {
            $where = array();
            foreach ($conditions as $key=>$val) {
                $where[] = $this->escape($key)."='".$this->escape($val)."'";
            }
            $where = join(' AND ', $where);
            $sql = "SELECT * FROM ".$this->table." WHERE $where";
            $result = $this->query($sql)->fetchAll('id', true);
        }
        return $result;
    }

}


