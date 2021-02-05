<?php

$model = new waModel();

try {
    $model->exec("SELECT `name` FROM `wa_contact_settings` WHERE 0");
    $model->exec("ALTER TABLE `wa_contact_settings` MODIFY `name` VARCHAR(64) NOT NULL");
} catch (Exception $e) {
}
