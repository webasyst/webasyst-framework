<?php

class checklistsListItemsModel extends waModel
{
    protected $table = 'checklists_list_items';

    public function getByList($list_id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE list_id=i:list_id ORDER BY sort";
        return $this->query($sql, array(
            'list_id' => $list_id,
        ))->fetchAll('id');
    }

    /** Increase `sort` by 1 where `sort` <= $sort and `list_id` = $list_id */
    public function moveApart($list_id, $sort)
    {
        $sql = "UPDATE {$this->table} SET sort=sort+1 WHERE list_id=i:list_id AND sort >= i:sort";
        return $this->exec($sql, array(
            'list_id' => $list_id,
            'sort' => $sort,
        ));
    }
}

