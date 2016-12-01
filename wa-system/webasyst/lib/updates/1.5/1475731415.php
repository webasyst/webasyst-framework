<?php

$sql = "CREATE TABLE IF NOT EXISTS `wa_app_tokens` (
            `contact_id` INT(11) NULL DEFAULT NULL,
            `app_id` VARCHAR(32) NOT NULL,
            `type` VARCHAR(32) NOT NULL,
            `create_datetime` DATETIME NOT NULL,
            `expire_datetime` DATETIME NULL DEFAULT NULL,
            `token` VARCHAR(32) NOT NULL,
            `data` TEXT NULL DEFAULT NULL,

            INDEX `app` (`app_id`),
            INDEX `contact` (`contact_id`),
            INDEX `expire` (`expire_datetime`),
            UNIQUE `token` (`token`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

$model = new waModel();
$model->exec($sql);
