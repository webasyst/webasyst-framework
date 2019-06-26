<?php

$m = new waModel();

try {
    $m->query("SELECT `last_use_datetime` FROM `wa_api_tokens` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `wa_api_tokens` ADD COLUMN `last_use_datetime` DATETIME NULL DEFAULT NULL AFTER `create_datetime`");
}
