<?php

$sql = "CREATE TABLE IF NOT EXISTS `wa_contact_tokens` (
  `contact_id` int(11) NOT NULL,
  `client_id` varchar(32) NOT NULL,
  `token` varchar(32) NOT NULL,
  `create_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires` int(11) NOT NULL,
  PRIMARY KEY (`token`),
  UNIQUE KEY `contact_client` (`contact_id`,`client_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

$model = new waModel();
$model->exec($sql);