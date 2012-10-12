DROP TABLE IF EXISTS `photos_comment`;
CREATE TABLE IF NOT EXISTS `photos_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `left` int(11) DEFAULT NULL,
  `right` int(11) DEFAULT NULL,
  `depth` int(11) NOT NULL DEFAULT '0',
  `parent` int(11) NOT NULL DEFAULT '0',
  `photo_id` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `status` ENUM ('approved','deleted') NOT NULL DEFAULT 'approved',
  `text` text NOT NULL,
  `contact_id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `site` varchar(100) DEFAULT NULL,
  `auth_provider` varchar(100) DEFAULT NULL,
  `ip`  int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  KEY `photo_id` (`photo_id`),
  KEY `parent` (`parent`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
