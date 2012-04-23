<?php 

$model = new waModel();
try {
    $model->exec("ALTER TABLE wa_contact_rights ADD INDEX name_value (name, value, group_id, app_id)");
} catch (Exception $e) {}
try {
    $model->exec("ALTER TABLE wa_announcement DROP INDEX `datetime`");
} catch (Exception $e) {}
try {
$model->exec("ALTER TABLE wa_announcement ADD INDEX datetime_app (datetime, app_id)");
} catch (Exception $e) {}