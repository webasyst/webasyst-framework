<?php
$model = new waModel();
try {
    $model->exec("SELECT `datetime` FROM `wa_user_groups` LIMIT 0");
} catch (Exception $e) {
    $model->exec("ALTER TABLE `wa_user_groups` ADD `datetime` DATETIME NULL DEFAULT NULL");
}