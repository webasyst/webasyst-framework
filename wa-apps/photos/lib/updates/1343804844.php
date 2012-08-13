<?php

$model = new waModel();

try {
    /**
     * restore albums
     * */

    // if albums are unbined to groups
    $sql = "SELECT a.id FROM `photos_album` a LEFT JOIN `photos_album_rights` ar ON a.id = ar.album_id WHERE ar.album_id IS NULL";
    $ids = array_keys($model->query($sql)->fetchAll('id'));
    if ($ids) {
        foreach ($ids as $id) {
            $sql = "INSERT INTO `photos_album_rights` (album_id, group_id) VALUES (".$id.", -1)";
            $model->exec($sql);
        }
        $sql = "UPDATE `photos_album` SET status = -1 WHERE id IN (".implode(',', $ids).")";
        $model->exec($sql);
    }

    // if children-parent chains to root are broken and albums are hanging

    $all_ids = array();
    $parent_id = array(0);
    $result = true;
    while ($parent_id) {
        $sql = "SELECT id, parent_id FROM `photos_album` WHERE parent_id IN (".implode(',', $parent_id).")";
        if ($result = $model->query($sql)) {
            $parent_id = array();
            foreach ($result as $item) {
                $parent_id[] = $item['id'];
                $all_ids[] = $item['id'];
            }
            $parent_id = array_unique($parent_id);
        }
    }
    $all_ids = array_unique($all_ids);

    $sql = "SELECT id FROM `photos_album` WHERE id NOT IN (".implode(',', $all_ids).")";
    $hanging_ids = array_keys($model->query($sql)->fetchAll('id'));
    if ($hanging_ids) {
        // bind to root
        $sql = "UPDATE `photos_album` SET parent_id = 0 WHERE id IN (".implode(',', $hanging_ids).")";
        $model->query($sql);
    }
} catch (waDbException $e) {
    if (class_exists('waLog')) {
        waLog::log(basename(__FILE__).': '.$e->getMessage(),'photos-update.log');
    }
}