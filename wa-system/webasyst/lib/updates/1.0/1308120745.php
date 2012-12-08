<?php

// strict mode mysql

$sql = array();
$sql[] = "ALTER TABLE `wa_contact` 
CHANGE `firstname` `firstname` VARCHAR( 50 ) NOT NULL DEFAULT '',
CHANGE `lastname` `lastname` VARCHAR( 50 ) NOT NULL DEFAULT  '',
CHANGE `middlename` `middlename` VARCHAR( 50 ) NOT NULL DEFAULT '',
CHANGE `title` `title` VARCHAR( 50 ) NOT NULL DEFAULT '',
CHANGE `company` `company` varchar(150) NOT NULL DEFAULT '',
CHANGE `company_contact_id` `company_contact_id` int(11) NOT NULL DEFAULT '0',
CHANGE `is_company` `is_company` tinyint(1) NOT NULL DEFAULT '0',
CHANGE `is_user` `is_user` tinyint(1) NOT NULL DEFAULT '0',
CHANGE `password` `password` varchar(32) NOT NULL DEFAULT '',
CHANGE `create_app_id` `create_app_id` varchar(32) NOT NULL DEFAULT '',
CHANGE `create_method` `create_method` varchar(32) NOT NULL DEFAULT '',
CHANGE `create_contact_id` `create_contact_id` int(11) NOT NULL DEFAULT '0',
CHANGE `locale` `locale` varchar(8) NOT NULL DEFAULT '',
CHANGE `timezone` `timezone` varchar(64) NOT NULL DEFAULT '',
CHANGE `last_datetime` `last_datetime` datetime DEFAULT NULL,
CHANGE `birthday` `birthday` DATE DEFAULT NULL";
$sql[] = "UPDATE wa_contact SET last_datetime = NULL WHERE last_datetime = '0000-00-00 00:00:00'";
$sql[] = "UPDATE wa_contact SET birthday = NULL WHERE birthday = '0000-00-00'";

$sql[] = "ALTER TABLE `wa_contact_data` CHANGE `ext` `ext` varchar(32) NOT NULL DEFAULT '',
CHANGE `sort` `sort` int(11) NOT NULL DEFAULT '0'";

$sql[] = "ALTER TABLE `wa_contact_emails` CHANGE `ext` `ext` varchar(32) NOT NULL DEFAULT '',
CHANGE `sort` `sort` int(11) NOT NULL DEFAULT '0'";

$sql[] = "ALTER TABLE `wa_contact_data_text` CHANGE `ext` `ext` varchar(32) NOT NULL DEFAULT '',
CHANGE `sort` `sort` int(11) NOT NULL DEFAULT '0'";

$model = new waModel();
foreach ($sql as $q) {
    $model->exec($q);
}
