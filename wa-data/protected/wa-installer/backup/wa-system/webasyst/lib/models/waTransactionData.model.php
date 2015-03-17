<?php

class waTransactionDataModel extends waModel
{
    protected $table = 'wa_transaction_data';

    public function addGroup($transaction_id, $data)
    {
        $values = array();
        foreach ($data as $field_id => $value) {
            $values[] = array(
                'transaction_id' => $transaction_id,
                'field_id'       => $field_id,
                'value'          => $value,
            );
        }
        return $this->multipleInsert($values);
    }

    /**
     * Get raw data for transactions group by transaction IDs
     * @param array $transaction_ids
     * @return array transactions raw data
     */
    public function getGroup($transaction_ids)
    {
        return $this->getByField('transaction_id', $transaction_ids, 'field_id');
    }

}
