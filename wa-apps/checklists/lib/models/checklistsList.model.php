<?php

class checklistsListModel extends waModel
{
    protected $table = 'checklists_list';
    protected $items_table = 'checklists_list_items';

    public function getAll($key = null, $normalize = false)
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY sort, name";
        return $this->query($sql)->fetchAll($key, $normalize);
    }

    /** Get lists available for current user.
      * @return array id => db row */
    public function getAllowed()
    {
        $lists = $this->getAll('id');

        $admin = wa()->getUser()->getRights('checklists', 'backend') > 1;
        if (!$admin) {
            $available = wa()->getUser()->getRights('checklists', 'list.%');
        }

        foreach($lists as $id => &$list) {
            if (!$admin && (!isset($available[$id]) || !$available[$id])) {
                unset($lists[$id]);
                continue;
            }
        }

        return $lists;
    }

    /** Set count of the list with given id to match number of unchecked items in the list */
    public function updateCount($id)
    {
        $sql = "SELECT COUNT(*) FROM {$this->items_table} WHERE list_id=:id AND done IS NULL";
        $count = $this->query($sql, array('id' => $id))->fetchField();
        return $this->updateById($id, array('count' => $count));
    }

    /** Increase `sort` by 1 where `sort` <= $sort */
    public function moveApart($sort)
    {
        $sql = "UPDATE {$this->table} SET sort=sort+1 WHERE sort >= i:sort";
        return $this->exec($sql, array(
            'sort' => $sort,
        ));
    }
}

