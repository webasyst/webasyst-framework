<?php

// add type "location" (for offices)
$model = new waModel();

try {
    $model->exec("SELECT type, description FROM wa_group LIMIT 0");
} catch (Exception $e) {
    $model->exec("ALTER TABLE wa_group
        ADD type ENUM('group', 'location') NOT NULL DEFAULT 'group',
        ADD description TEXT
    ");
}

$model->exec("CREATE TABLE IF NOT EXISTS `wa_contact_calendars` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `bg_color` varchar(7) DEFAULT NULL,
    `font_color` varchar(7) DEFAULT NULL,
    `sort` INT NOT NULL DEFAULT '0',
    `is_limited` TINYINT NOT NULL DEFAULT '0',
    `default_status` VARCHAR(255) NULL,
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

$model->exec("CREATE TABLE IF NOT EXISTS `wa_contact_events` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `uid` VARCHAR(255) NULL,
    `create_datetime` DATETIME NOT NULL,
    `update_datetime` DATETIME NOT NULL,
    `contact_id` INT NOT NULL,
    `calendar_id` INT NOT NULL,
    `summary` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `location` VARCHAR(255) NULL,
    `start` DATETIME NOT NULL,
    `end` DATETIME NOT NULL,
    `is_allday` TINYINT NOT NULL DEFAULT '0',
    `is_status` TINYINT NOT NULL DEFAULT '0',
    `sequence` INT NOT NULL DEFAULT '0',
    `summary_type` VARCHAR(20) NULL,
    PRIMARY KEY (`id`),
    INDEX `uid` (`uid`),
    INDEX `contact_id` (`contact_id`),
    INDEX `calendar_id` (`calendar_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");
