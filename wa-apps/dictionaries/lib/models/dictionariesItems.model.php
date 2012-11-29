<?php

class dictionariesItemsModel extends waModel
{
    protected $table = 'dictionaries_items';

    public function getByList($list_id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE dictionary_id=i:dictionary_id ORDER BY sort";
        return $this->query($sql, array(
            'dictionary_id' => $list_id,
        ))->fetchAll('id');
    }

    /** Increase `sort` by 1 where `sort` <= $sort and `dictionary_id` = $list_id */
    public function moveApart($list_id, $sort)
    {
        $sql = "UPDATE {$this->table} SET sort=sort+1 WHERE dictionary_id=i:dictionary_id AND sort >= i:sort";
        return $this->exec($sql, array(
            'dictionary_id' => $list_id,
            'sort' => $sort,
        ));
    }
}

