<?php

$mod = new waModel();
try {
    $mod->exec('SELECT name FROM mailer_message_recipients LIMIT 0');
} catch (waDbException $e) {
    $mod->exec('ALTER TABLE `mailer_message_recipients` ADD `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `message_id`');
}
try {
    $mod->exec('SELECT `group` FROM mailer_message_recipients LIMIT 0');
} catch (waDbException $e) {
    $mod->exec('ALTER TABLE `mailer_message_recipients` ADD `group` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `message_id`');
}
try {
    $mod->exec('SELECT `count` FROM mailer_message_recipients LIMIT 0');
} catch (waDbException $e) {
    $mod->exec('ALTER TABLE `mailer_message_recipients` ADD `count` INT(11) NOT NULL DEFAULT 0');
}
