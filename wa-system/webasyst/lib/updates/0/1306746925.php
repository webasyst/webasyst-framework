<?php 

$model = new waModel();
$model->exec("CREATE TABLE IF NOT EXISTS `wa_contact_data_text` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) NOT NULL,
  `field` varchar(32) NOT NULL,
  `ext` varchar(32) NOT NULL,
  `value` text NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_field_sort` (`contact_id`,`field`,`sort`),
  KEY `contact_id` (`contact_id`),
  KEY `field` (`field`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8");