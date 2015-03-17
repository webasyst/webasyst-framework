<?php

$mod = new waModel();
$mod->exec("
CREATE TABLE IF NOT EXISTS `mailer_draft_recipients` (
 `message_id` bigint(20) unsigned NOT NULL,
 `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
 `email` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
 `contact_id` bigint(20) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8
");
