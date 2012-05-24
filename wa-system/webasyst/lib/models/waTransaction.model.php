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
      $result = $this->query($sql)->fetchAll('id');
    }
    return $result;
  }
  
  public function getPrimary($order_id)
  {
    $sql = "SELECT * FROM ".$this->table." WHERE
      order_id=i:order_id AND parent_id IS NULL AND (type='AUTH+CAPTURE' OR type='AUTH_ONLY') ORDER BY id DESC";
    return $this->query($sql, array('order_id'=>$order_id))->fetchAll('id');
  }
  
  public function getRefundable($order_id, $paymentsystem_id)
  {
    $sql = "SELECT * FROM ".$this->table." WHERE order_id=i:order_id AND paymentsystem_id=s:paymentsystem_id
      AND state='CAPTURED' ORDER BY create_datetime DESC";
    $result = $this->query($sql, array('order_id'=>$order_id, 'paymentsystem_id'=>$paymentsystem_id))->fetchAll();
    return $result;
  }
  
  public function getCancelable($order_id)
  {
    $sql = "SELECT * FROM ".$this->table." WHERE order_id=i:order_id AND state='AUTH' AND result=1 ORDER BY create_datetime DESC";
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


