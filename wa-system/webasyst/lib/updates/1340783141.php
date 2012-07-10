<?php

$mod = new waModel();

// Add new 'sex' field to wa_contacts
try {
    $mod->exec("SELECT sex FROM wa_contact LIMIT 0");
} catch (Exception $e) {
    $mod->exec("ALTER TABLE wa_contact ADD sex ENUM('m', 'f') NULL DEFAULT NULL AFTER title");
}

// Add waContact field to custom fields order, if needed
$flds = waContactFields::getAll('person');
if (!isset($flds['sex'])) {
    // Insert 'sex' field before 'company'
    $i = 0;
    foreach($flds as $k => $v) {
        if ($k === 'company') {
            break;
        }
        $i++;
    }
    waContactFields::enableField('sex', 'person', $i);
}

