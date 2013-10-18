<?php

$model = new waModel();

try {
    $model->exec("SELECT meta_title FROM `blog_post` WHERE 0");
} catch (waException $e) {
    $model->exec("ALTER TABLE `blog_post` ADD COLUMN `meta_title` VARCHAR(255) NULL DEFAULT NULL");
}

try {
    $model->exec("SELECT meta_keywords FROM `blog_post` WHERE 0");
} catch (waException $e) {
    $model->exec("ALTER TABLE `blog_post` ADD COLUMN `meta_keywords` TEXT NULL DEFAULT NULL");
}

try {
    $model->exec("SELECT meta_description FROM `blog_post` WHERE 0");
} catch (waException $e) {
    $model->exec("ALTER TABLE `blog_post` ADD COLUMN `meta_description` TEXT NULL DEFAULT NULL");
}