<?php

$model = new waModel();

try {
    $model->exec("SELECT update_datetime FROM `blog_post` WHERE 0");
} catch (waException $e) {
    $model->exec("ALTER TABLE `blog_post` ADD COLUMN `update_datetime` datetime NULL");
}