<?php

$m = new waModel();

try {
    $m->query("SELECT `tries` FROM `wa_verification_channel_assets` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `wa_verification_channel_assets` ADD COLUMN `tries` INT(11) NOT NULL DEFAULT 0");
}
