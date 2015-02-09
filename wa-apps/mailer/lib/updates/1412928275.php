<?php

$m = new waModel();

try {
    $m->exec("
        ALTER TABLE `mailer_sender_params` CHANGE `value` `value` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
    ");
} catch (waDbException $e) {
}
