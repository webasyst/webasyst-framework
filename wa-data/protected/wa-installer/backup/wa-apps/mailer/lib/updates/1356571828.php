<?php

$mod = new waModel();

try {
    $mod->exec('ALTER TABLE `mailer_message_log` ADD INDEX `contact_id` (`contact_id`)');
} catch (waDbException $e) {
}

