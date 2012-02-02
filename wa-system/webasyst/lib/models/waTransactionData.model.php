<?php

class waTransactionDataModel extends waModel
{
    protected $table = 'wa_transaction_data';

    public function addGroup($transaction_id, $data)
    {
        $values = '';
        foreach ($data as $field_id=>$value) {
            $values .= "(".(int)$transaction_id.", '".$this->escape($field_id)."', '".$this->escape($value)."'), ";
        }
        $values = substr($values, 0, -2);

        $sql = "INSERT INTO ".$this->table." (transaction_id, field_id, value) VALUES $values";
        return $this->exec($sql);
    }

    /**
     * Get raw data for transactions group by transaction IDs
     * @param array $transaction_ids
     * @return array transactions raw data
     */
    public function getGroup($transaction_ids)
    {
        $ids = '';
        if (is_array($transaction_ids)) {
            foreach ($transaction_ids as $id) {
                $ids .= "'".(int)$id."',";
            }
            $ids = substr($ids, 0, -1);
        } else {
            $ids = "'".(int)$transaction_ids."'";
        }
        $sql = "SELECT * FROM ".$this->table." WHERE transaction_id IN ($ids)";
        return $this->query($sql)->fetchAll();
    }

    public function getRawData($transaction_id)
    {
        $sql = "SELECT * FROM ".$this->table." WHERE transaction_id=s:transaction_id";
        return $this->query($sql, array('transaction_id'=>$transaction_id))->fetchAll('field_id');
    }
    
}

