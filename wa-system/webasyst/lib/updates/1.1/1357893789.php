<?php

$model = new waModel();
$model->exec("CREATE TABLE IF NOT EXISTS `wa_contact_field_values` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_field` varchar(64) NOT NULL,
  `parent_value` varchar(255) NOT NULL,
  `field` varchar(64) NOT NULL,
  `value` varchar(255) NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parent_field` (`parent_field`,`parent_value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");

