<?php

// old meta-up reincarnation because of db.php has not been updates to time meta up first created

$model = new waModel();

try {
    $model->exec("SELECT default_status FROM wa_contact_calendars LIMIT 0");
} catch (Exception $e) {
    $model->exec("ALTER TABLE wa_contact_calendars ADD default_status VARCHAR(255) NULL");
}
