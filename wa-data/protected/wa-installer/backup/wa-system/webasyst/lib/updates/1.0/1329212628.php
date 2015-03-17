<?php

/**
 * Adding field params TEXT NOT NULL to table wa_log 
 */

$model = new waModel();
try {
    // check that field params is already exists
    $model->exec("SELECT params FROM wa_log WHERE 0");
    $model->exec("UPDATE wa_log SET params = '' WHERE params IS NULL");
    $model->exec("ALTER TABLE wa_log CHANGE params params TEXT NULL");
} catch (waDbException $e) {
    // add field params to table wa_log 
    $model->exec("ALTER TABLE wa_log ADD params TEXT NULL");
}