<?php

$m = new waModel();

try {
    $m->query("SELECT `contact_id` FROM `wa_verification_channel_assets` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `wa_verification_channel_assets` DROP INDEX `channel_address_name`");
    $m->exec("ALTER TABLE `wa_verification_channel_assets` ADD COLUMN `contact_id` INT(11) NOT NULL DEFAULT 0 AFTER `address`");
    $m->exec("ALTER TABLE `wa_verification_channel_assets` ADD UNIQUE INDEX `channel_address_name` (`channel_id`, `address`, `contact_id`, `name`)");
}
