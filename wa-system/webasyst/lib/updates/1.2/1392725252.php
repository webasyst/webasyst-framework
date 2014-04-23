<?php

$model = new waModel();

// drop wa_log.count
try {
    $model->query("SELECT `count` FROM wa_log WHERE 0");
    $model->exec("ALTER TABLE wa_log DROP `count`");
} catch (waDbException $e) {
}

// add wa_log.subject_contact_id
try {
    $model->query("SELECT subject_contact_id FROM wa_log WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE wa_log ADD subject_contact_id INT(11) NULL DEFAULT NULL AFTER action");
}

// change id to bigint(20)
$model->exec("ALTER TABLE wa_log CHANGE id id BIGINT(20) NOT NULL AUTO_INCREMENT");