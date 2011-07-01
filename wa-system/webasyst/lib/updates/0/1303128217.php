<?php 

$model = new waModel();
$model->exec("CREATE TABLE IF NOT EXISTS `wa_app_settings` (
  `app_id` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`app_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");