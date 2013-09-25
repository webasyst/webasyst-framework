<?php

$model = new waModel();
try {
    $model->query("SELECT moderation FROM `photos_photo` WHERE 0");
    $model->exec("ALTER TABLE `photos_photo` DROP COLUMN moderation");
} catch (waException $e) {
}

try {
    $model->query("SELECT votes_count FROM `photos_photo` WHERE 0");
    $model->exec("ALTER TABLE `photos_photo` DROP COLUMN votes_count");
} catch (waException $e) {
}

