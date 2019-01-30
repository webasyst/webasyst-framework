<?php

$m = new waModel();

try {
    $m->query("SELECT ip FROM `wa_login_log` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `wa_login_log` ADD COLUMN ip VARCHAR(45) NULL DEFAULT NULL AFTER `datetime_out`");
}