<?php
$model = new waModel();
try {
    $model->query("SELECT app_id FROM photos_photo WHERE 0");
} catch (waException $e) {
    $model->exec("ALTER TABLE `photos_photo` ADD `app_id` VARCHAR(64) NULL DEFAULT NULL, ADD INDEX `app_id` (`app_id`)");
}

