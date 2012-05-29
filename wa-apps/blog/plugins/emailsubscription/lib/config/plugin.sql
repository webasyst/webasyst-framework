DROP TABLE IF EXISTS `blog_emailsubscription`;
CREATE TABLE IF NOT EXISTS `blog_emailsubscription` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_contact` (`blog_id`, `contact_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `blog_emailsubscription_log`;
CREATE TABLE IF NOT EXISTS `blog_emailsubscription_log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `datetime` datetime DEFAULT NULL,
  `status` smallint(6) NOT NULL DEFAULT '0',
  `error` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_email` (`post_id`,`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;