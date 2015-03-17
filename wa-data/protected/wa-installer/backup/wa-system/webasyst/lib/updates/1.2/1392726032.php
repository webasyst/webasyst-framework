<?php

$model = new waModel();

// Add new 'jobtitle' field to wa_contacts
try {
    $model->exec("SELECT jobtitle FROM wa_contact LIMIT 0");
} catch (Exception $e) {
    $model->exec("ALTER TABLE wa_contact ADD jobtitle VARCHAR(50) NOT NULL DEFAULT '' AFTER company");
}
