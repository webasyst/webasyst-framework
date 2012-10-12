<?php

$model = new waModel();

$table_name = 'photos_comment';

try {
    $model->query("ALTER TABLE `$table_name` ADD `ip` int(11) NULL DEFAULT NULL AFTER `site`");
    $model->query("ALTER TABLE `$table_name` ADD `auth_provider` VARCHAR(100) NULL DEFAULT NULL AFTER `site`");
} catch (waDbException $e) {
    if (class_exists('waLog')) {
        waLog::log(basename(__FILE__).': '.$e->getMessage(), 'photos-update.log');
    }
}