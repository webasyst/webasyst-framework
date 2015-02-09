<?php

$mod = new waModel();

try {
    $mod->exec('ALTER TABLE `mailer_message_log` ADD INDEX `message_id_status` (`message_id`, `status`)');
} catch (waDbException $e) {
}

