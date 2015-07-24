<?php

$model = new waModel();


// rename create_contact_id to contact_id
try {
    $model->exec('SELECT create_contact_id FROM wa_widget');
    $model->exec('ALTER TABLE `wa_widget` CHANGE `create_contact_id` `contact_id` INT(11) NOT NULL');
} catch (waDbException $e) {
    if ($e->getCode() != 1054) {
        throw $e;
    }
}

// rename code to widget
try {
    $model->exec('SELECT code FROM wa_widget');
    $model->exec('ALTER TABLE `wa_widget` CHANGE `code` `widget` VARCHAR(32) NOT NULL');
} catch (waDbException $e) {
    if ($e->getCode() != 1054) {
        throw $e;
    }
}

try {
    $model->exec('SELECT locale FROM wa_widget');
    $model->exec('ALTER TABLE `wa_widget` DROP `locale`');
} catch (waDbException $e) {
}

try {
    $model->exec('SELECT block FROM wa_widget');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE `wa_widget` ADD `block` INT NOT NULL ');
}

try {
    $model->exec('SELECT sort FROM wa_widget');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE `wa_widget` ADD `sort` INT NOT NULL ');
}

try {
    $model->exec('SELECT size FROM wa_widget');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE `wa_widget` ADD `size` CHAR(3) NOT NULL ');
}

try {
    $model->exec('SELECT create_datetme FROM wa_widget');
    $model->exec('ALTER TABLE `wa_widget` CHANGE `create_datetme` `create_datetime` DATETIME NOT NULL;');
} catch (waDbException $e) {
    if ($e->getCode() != 1054) {
        throw $e;
    }
}

try {
    $model->exec('ALTER TABLE wa_widget DROP INDEX code');
} catch (waDbException $e) {
}

$model->exec("CREATE TABLE IF NOT EXISTS `wa_widget_params` (
  `widget_id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL,
  `value` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8");