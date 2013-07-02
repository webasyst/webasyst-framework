DROP TABLE IF EXISTS `contacts_rights`;
CREATE TABLE IF NOT EXISTS `contacts_rights` (
  `group_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `writable` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`group_id`,`category_id`),
  KEY `list_id` (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `contacts_history`;
CREATE TABLE IF NOT EXISTS `contacts_history` (
 `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 `type` varchar(20) NOT NULL,
 `name` varchar(255) NOT NULL,
 `hash` text NOT NULL,
 `contact_id` bigint(20) unsigned NOT NULL,
 `position` int(11) unsigned NOT NULL DEFAULT '0',
 `accessed` DATETIME DEFAULT NULL,
 `cnt` INT NOT NULL DEFAULT  '-1',
 PRIMARY KEY (`id`),
 KEY `contact_id` (`contact_id`),
 KEY `accessed` (`contact_id`,`accessed`),
 KEY `hash` (`contact_id`,`hash`(24)),
 KEY `position` (`contact_id`,`position`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
