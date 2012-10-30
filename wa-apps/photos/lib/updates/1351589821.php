<?php

$model = new waModel();

try {
    $update = array_keys($model->query("SELECT * FROM `photos_album` WHERE status <= 0 AND hash = ''")->fetchAll('id'));
    if (!empty($update)) {
        foreach ($update as $id) {
            $model->query("UPDATE `photos_album` SET full_url = NULL, hash = '".md5(uniqid(time(), true))."' WHERE id = $id");
        }
    }

} catch (waDbException $e) {
    if (class_exists('waLog')) {
        waLog::log(basename(__FILE__).': '.$e->getMessage(),'photos-update.log');
    }
}