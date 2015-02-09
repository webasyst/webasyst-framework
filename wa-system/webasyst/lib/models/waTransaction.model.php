<?php

class waTransactionModel extends waModel
{
    protected $table = 'wa_transaction';
    
    public function insert($data, $type=0)
    {
        if (!isset($data['app_id'])) {
            $data['app_id'] = waSystem::getInstance()->getApp();
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
            $result = $this->query($sql)->fetchAll('id');
        }
        return $result;
    }
    
    public function getPrimary($order_id)
    {
        $sql = "SELECT * FROM ".$this->table." WHERE
            order_id=i:order_id AND parent_id IS NULL AND (type='"
            .waPayment::OPERATION_AUTH_CAPTURE."' OR type='"
            .waPayment::OPERATION_AUTH_ONLY."') ORDER BY id DESC";
        return $this->query($sql, array('order_id'=>$order_id))->fetchAll('id');
    }
    
    public function getRefundable($order_id, $plugin)
    {
        $sql = "SELECT * FROM ".$this->table." WHERE order_id=i:order_id AND plugin=s:plugin
            AND (type='".waPayment::OPERATION_AUTH_ONLY."' OR type='".waPayment::OPERATION_AUTH_CAPTURE
            ."') AND state='CAPTURED' AND result=1 ORDER BY create_datetime DESC";
        $result = $this->query($sql, array('order_id'=>$order_id, 'plugin'=>$plugin))->fetchAll();
        return $result;
    }

    public function getCancelable($order_id)
    {
        $sql = "SELECT * FROM ".$this->table." WHERE order_id=i:order_id AND state='"
            .waPayment::STATE_AUTH."' AND result=1 ORDER BY create_datetime DESC";
        $result = $this->query($sql, array('order_id'=>$order_id))->fetchAll();
        return $result;
    }
    
    public function updateStateByOrder($order_id, $state)
    {
        $sql = "UPDATE ".$this->table." SET state=s:state WHERE order_id=s:order_id";
        $result = $this->exec($sql, array('order_id'=>$order_id, 'state'=>$state));
        return $result;
    }
    
}


