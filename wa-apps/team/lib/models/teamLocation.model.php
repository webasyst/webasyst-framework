<?php

class teamLocationModel extends waModel
{
    protected $table = 'team_location';
    protected $id = 'group_id';

    public function add($group_id, $data)
    {
        $data = (array) $data;
        if (isset($data['group_id'])) {
            unset($data['group_id']);
        }
        foreach ($data as $field => $value) {
            if (!$this->fieldExists($field)) {
                unset($data[$field]);
            }
        }
        $is_all_empty = true;
        foreach ($data as $field => $value) {
            $is_empty = (is_string($value) && strlen($value) <= 0) || empty($value);
            if (!$is_empty) {
                $is_all_empty = false;
                break;
            }
        }
        if ($is_all_empty) {
            $this->deleteById($group_id);
            return;
        }

        $data['group_id'] = $group_id;
        $this->insert($data);

    }
}
