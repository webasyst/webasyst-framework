<?php

class waContactFieldValuesModel extends waModel
{
    protected $table = 'wa_contact_field_values';

    public function getInfo($field)
    {
        $field = $this->escape($field);

        $data = array();
        $parent = null;

        foreach ($this->query(
                "SELECT * FROM `{$this->table}` WHERE field = '$field' ORDER BY parent_value, sort")
            as $item)
        {
            if ($parent !== $item['parent_value']) {
                $p = &$data[];
                $p = array(
                    'field' => $item['parent_field'],
                    'value' => $item['parent_value'],
                    'children' => array()
                );
                $parent = $item['parent_value'];
            }
            $p['children'][] = array(
                'id' => $item['id'], 'field' => $item['field'], 'value' => $item['value'], 'sort' => $item['sort']
            );
        }
        return $data;
    }

    public function save($data)
    {
        if (!empty($data['update'])) {
            foreach ($this->getByField('id', array_keys($data['update']), 'id')
                as $id => $old_item
            )
            {
                $item = $data['update'][$id];
                $diff = array_diff_assoc($item, $old_item);
                if ($diff) {
                    if (isset($diff['value']) && !$diff['value']) {
                        $this->deleteById($id);
                    }
                    $this->updateById($id, $diff);
                }
                unset($data['update'][$id]);
            }
            if (!empty($data['update'])) {
                $data['add'] = array_merge(isset($data['add']) ? $data['add'] : array(), $data['update']);
            }
        }
        if (!empty($data['add'])) {
            return $this->multipleInsert($data['add']);
        }
        return true;
    }

    public function changeField($old_field, $new_field)
    {
        $sql = "UPDATE {$this->table}
                SET field=REPLACE(field, ?, ?)
                WHERE field LIKE '%".$this->escape($old_field, 'like')."'";
        $this->exec($sql, $old_field, $new_field);
    }
}

