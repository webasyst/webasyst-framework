<?php

(new waModel())->exec("CREATE TABLE IF NOT EXISTS `site_variable` (
  `id` varchar(64) NOT NULL,
  `content` mediumtext NOT NULL,
  `create_datetime` datetime NOT NULL,
  `description` text NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8");
