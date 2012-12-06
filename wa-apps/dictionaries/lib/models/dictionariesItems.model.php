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

    public function getCountByList($list_id)
    {
        $sql = "SELECT COUNT(*) AS count FROM {$this->table} WHERE dictionary_id=i:dictionary_id ORDER BY sort";
        return $this->query($sql, array(
            'dictionary_id' => $list_id,
        ))->fetch('count');
    }

    public function getSortedByList($list_id, $callbackParams)
    {

	$whereClause = "";
	if ($callbackParams['search'] == 'true') {
		$whereClause .= " AND ".$callbackParams['searchField']." LIKE '%".$callbackParams['searchString']."%' ";

	}

        $sql = "SELECT * FROM {$this->table} WHERE dictionary_id=i:dictionary_id ".$whereClause." ORDER BY ".$callbackParams['sidx']." ".$callbackParams['sord']." LIMIT i:start, i:limit";
        return $this->query($sql, array(
            'dictionary_id' 	=> $list_id,
            'start' 		=> $callbackParams['start'],
            'limit' 		=> $callbackParams['limit'],
        ))->fetchAll();
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

