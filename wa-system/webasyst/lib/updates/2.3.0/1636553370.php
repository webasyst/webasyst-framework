<?php

$model = new waModel();

try {
    $model->query("SELECT `value` FROM `wa_app_settings` WHERE 0");
    $model->exec("ALTER TABLE `wa_app_settings` MODIFY COLUMN `value` MEDIUMTEXT NOT NULL");
} catch (waException $e) {

}


