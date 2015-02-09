<?php

$mod = new waModel();
try {
    $mod->exec('SELECT error_class FROM mailer_message_log LIMIT 0');
} catch (waDbException $e) {
    $mod->exec('ALTER TABLE mailer_message_log ADD error_class VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
}

