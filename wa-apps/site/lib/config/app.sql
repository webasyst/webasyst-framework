DROP TABLE IF EXISTS `site_block`;
CREATE TABLE IF NOT EXISTS `site_block` (
  `id` varchar(64) NOT NULL,
  `description` text NOT NULL,
  `content` text NOT NULL,
  `create_datetime` datetime NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `site_domain`;
CREATE TABLE IF NOT EXISTS `site_domain` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(128) NOT NULL DEFAULT '',
  `style` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `site_page`;
CREATE TABLE IF NOT EXISTS `site_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `domain_id` int(11) NOT NULL,
  `route` varchar(64) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) DEFAULT NULL,
  `full_url` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `create_datetime` datetime NOT NULL,
  `update_datetime` datetime NULL DEFAULT NULL,
  `create_contact_id` int(11) NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `url` (`domain_id` ,  `route` ,  `full_url`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `site_page_params`;
CREATE TABLE IF NOT EXISTS `site_page_params` (
  `page_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`page_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
