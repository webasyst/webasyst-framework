<?php

$model = new waModel();

$model->exec("CREATE TABLE IF NOT EXISTS `wa_dashboard` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(255) NOT NULL,
 `hash` VARCHAR(32) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

try {
    $model->exec("SELECT dashboard_id FROM wa_widget LIMIT 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE `wa_widget` ADD `dashboard_id` INT(11) NULL DEFAULT NULL AFTER `contact_id`");
}

