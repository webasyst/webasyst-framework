<?php

$m = new waModel();

try {
    $m->query("SELECT `scope` FROM `wa_push_subscribers` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `wa_push_subscribers` ADD COLUMN `scope` VARCHAR(255) NULL DEFAULT NULL AFTER `contact_id`");
}
