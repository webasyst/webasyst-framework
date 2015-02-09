<?php

class blogContactsMergeHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $master_id = $params['id'];
        $merge_ids = $params['contacts'];

        $m = new waModel();

        foreach(array(
            array('blog_comment', 'contact_id'),
        ) as $pair)
        {
            list($table, $field) = $pair;
            $sql = "UPDATE $table SET $field = :master WHERE $field in (:ids)";
            $m->exec($sql, array('master' => $master_id, 'ids' => $merge_ids));
        }

        return null;
    }
}

