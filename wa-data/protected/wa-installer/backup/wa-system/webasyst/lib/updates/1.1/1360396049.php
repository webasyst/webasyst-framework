<?php

$model = new waModel();

try {
    $model->exec('SELECT app_id FROM wa_contact_category LIMIT 0');
} catch (waDbException $e) {
    $model->exec("ALTER TABLE `wa_contact_category`
                  ADD `app_id` VARCHAR(32) NULL DEFAULT NULL AFTER `system_id`,
                  ADD `icon` VARCHAR(255) NULL DEFAULT NULL AFTER `app_id`");
}

