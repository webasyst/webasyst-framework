<?php

$model = new waModel();

try {

    $parent_ids = array(0);
    $statuses = array(
        0 => 1
    );
    $update = array();
    while ($parent_ids) {
        $sql = "SELECT * FROM `photos_album` WHERE parent_id IN (".implode(',', $parent_ids).") ORDER BY parent_id, sort";
        $parent_ids = array();
        foreach ($model->query($sql) as $item) {
            if ($statuses[$item['parent_id']] <= 0 && $item['status'] == 1) { // change status from public to private + gen hash
                $update[] = $item['id'];
                $statuses[$item['id']] = 0;
            } else {
                $statuses[$item['id']] = $item['status'];
            }
            $parent_ids[] = $item['id'];
        }
    }

    if (!empty($update)) {
        foreach ($update as $id) {
            $model->query("UPDATE `photos_album` SET status = 0, full_url = NULL, hash = '".md5(uniqid(time(), true))."' WHERE id = $id");
        }
    }

} catch (waDbException $e) {
    if (class_exists('waLog')) {
        waLog::log(basename(__FILE__).': '.$e->getMessage(),'photos-update.log');
    }
}