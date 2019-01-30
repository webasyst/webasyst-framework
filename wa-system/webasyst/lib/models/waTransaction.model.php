<?php

class waTransactionModel extends waModel
{
    protected $table = 'wa_transaction';

    /**
     * @param array $data
     * @param int $type
     * @return bool|int|resource
     */
    public function insert($data, $type = 0)
    {
        if (!isset($data['app_id'])) {
            $data['app_id'] = wa()->getApp();
        }
        if (!isset($data['create_datetime'])) {
            $data['create_datetime'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['update_datetime'])) {
            $data['update_datetime'] = $data['create_datetime'];
        }

        return parent::insert($data, $type);
    }

    /**
     * @param $conditions
     * @return array
     */
    public function getByFields($conditions)
    {
        $result = array();
        if (is_array($conditions)) {
            if (!isset($conditions['app_id'])) {
                $conditions['app_id'] = wa()->getApp();
            }
            $result = $this->getByField($conditions, $this->id);
            if ($result) {
                ksort($result, SORT_NUMERIC);
            }
        }

        return $result;
    }

    /**
     * @param $order_id
     * @param string|null $app_id
     * @return array
     */
    public function getPrimary($order_id, $app_id = null)
    {
        $sql = /** @lang text */
            "SELECT * FROM ".$this->table." WHERE
            app_id='".$this->getAppId($app_id)."' AND order_id=i:order_id AND parent_id IS NULL AND (type='"
            .waPayment::OPERATION_AUTH_CAPTURE."' OR type='"
            .waPayment::OPERATION_AUTH_ONLY."') ORDER BY id DESC";
        return $this->query($sql, array('order_id'=>$order_id))->fetchAll('id');
    }

    /**
     * @param $order_id
     * @param $plugin
     * @param string|null $app_id
     * @return array
     */
    public function getRefundable($order_id, $plugin, $app_id = null)
    {
        $sql = /** @lang text */
            "SELECT * FROM ".$this->table." WHERE app_id='".$this->getAppId($app_id)."' AND order_id=i:order_id AND plugin=s:plugin
            AND (type='".waPayment::OPERATION_AUTH_ONLY."' OR type='".waPayment::OPERATION_AUTH_CAPTURE
            ."') AND state='CAPTURED' AND result=1 ORDER BY create_datetime DESC";
        $result = $this->query($sql, array('order_id'=>$order_id, 'plugin'=>$plugin))->fetchAll();
        return $result;
    }

    /**
     * @param $order_id
     * @param string|null $app_id
     * @return array
     */
    public function getCancelable($order_id, $app_id = null)
    {
        $sql = /** @lang text */
            "SELECT * FROM ".$this->table." WHERE app_id='".$this->getAppId($app_id)."' AND order_id=i:order_id AND state='"
            .waPayment::STATE_AUTH."' AND result=1 ORDER BY create_datetime DESC";
        $result = $this->query($sql, array('order_id'=>$order_id))->fetchAll();
        return $result;
    }

    /**
     * @param $order_id
     * @param $state
     * @param string|null $app_id
     * @return bool|resource
     */
    public function updateStateByOrder($order_id, $state, $app_id = null)
    {
        $sql = "UPDATE ".$this->table." SET state=s:state WHERE app_id='".$this->getAppId($app_id)."' AND order_id=s:order_id";
        $result = $this->exec($sql, array('order_id'=>$order_id, 'state'=>$state));
        return $result;
    }

    /**
     * @param string|null $app_id
     * @return string
     */
    protected function getAppId($app_id = null)
    {
        return $this->escape($app_id ? $app_id : wa()->getApp());
    }
}
