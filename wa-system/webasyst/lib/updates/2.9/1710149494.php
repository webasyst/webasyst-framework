<?php

$m = new waModel();
try {
    $m->query("SELECT `contact_id` FROM `wa_announcement` WHERE 0");
} catch (waDbException $e) {
    $sql = "ALTER TABLE `wa_announcement`
                ADD `type` VARCHAR(32) NULL DEFAULT NULL AFTER `app_id`,
                ADD `contact_id` INT NULL DEFAULT NULL AFTER `type`,
                ADD `ttl_datetime` DATETIME NULL DEFAULT NULL AFTER `datetime`,
                ADD `is_pinned` TINYINT NOT NULL DEFAULT '0' AFTER `ttl_datetime`,
                ADD `access` ENUM('all', 'limited') NOT NULL DEFAULT 'all' AFTER `is_pinned`,
                ADD `data` TEXT NULL DEFAULT NULL AFTER `text`";
    $m->exec($sql);
}

$_installer = new webasystInstaller();
$_installer->createTable('wa_announcement_rights');
