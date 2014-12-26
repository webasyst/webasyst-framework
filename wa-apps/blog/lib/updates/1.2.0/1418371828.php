<?php
$model = new waModel();
try {
    $model->exec("SELECT `album_id` FROM `blog_post` WHERE 0");
} catch (waException $e) {
    $model->exec("ALTER TABLE `blog_post` ADD `album_id` INT NULL DEFAULT NULL , ADD `album_link_type` ENUM('blog', 'photos') NULL DEFAULT NULL");
}
