<?php

$model = new waModel();

$sql = "ALTER TABLE `photos_album` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL";
try {
    $model->query($sql);
} catch (waDbException $ex) {
    if(class_exists('waLog')) {
        waLog::log(basename(__FILE__).': '.$ex->getMessage(),'photos-update.log');
    }
}