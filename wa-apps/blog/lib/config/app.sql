DROP TABLE IF EXISTS `blog_blog`;
CREATE TABLE IF NOT EXISTS `blog_blog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL,
  `status` ENUM ('public','private') NOT NULL DEFAULT 'public',
  `icon` varchar(255) NOT NULL DEFAULT '',
  `color` varchar(50) NOT NULL DEFAULT '',
  `qty` int(11) NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `sort` (`sort`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `blog_comment`;
CREATE TABLE IF NOT EXISTS `blog_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `left` int(11) DEFAULT NULL,
  `right` int(11) DEFAULT NULL,
  `depth` int(11) NOT NULL DEFAULT '0',
  `parent` int(11) NOT NULL DEFAULT '0',
  `post_id` int(11) NOT NULL,
  `blog_id` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `status` ENUM ('approved','deleted') NOT NULL DEFAULT 'approved',
  `text` text NOT NULL,
  `contact_id` int(11) NOT NULL,
  `name` VARCHAR( 255 ) DEFAULT NULL,
  `email` VARCHAR( 255 ) DEFAULT NULL,
  `site` VARCHAR( 255 ) DEFAULT NULL,
  `auth_provider` varchar(100) DEFAULT NULL,
  `ip`  int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  KEY `post_id` (`post_id`),
  KEY `parent` (`parent`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `blog_post`;
CREATE TABLE IF NOT EXISTS `blog_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_id` int(11) NOT NULL DEFAULT '1',
  `contact_id` int(11) NOT NULL,
  `contact_name` VARCHAR( 150 ) DEFAULT  '',
  `datetime` datetime DEFAULT NULL,
  `title` varchar(255) NOT NULL DEFAULT  '',
  `status` ENUM ('draft', 'deadline', 'scheduled', 'published') NOT NULL DEFAULT 'draft',
  `text` MEDIUMTEXT NOT NULL,
  `text_before_cut` text DEFAULT NULL,
  `cut_link_label` varchar(255) DEFAULT NULL,
  `url` varchar(255) NOT NULL DEFAULT '',
  `comments_allowed` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  KEY `blog` (  `status` ,  `blog_id`,  `datetime` )
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `blog_post_params`;
CREATE TABLE IF NOT EXISTS `blog_post_params` (
  `post_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`post_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `blog_page`;
CREATE TABLE IF NOT EXISTS `blog_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `route` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT '',
  `url` varchar(255) DEFAULT NULL,
  `full_url` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `create_datetime` datetime NOT NULL,
  `update_datetime` datetime NOT NULL,
  `create_contact_id` int(11) NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `blog_page_params`;
CREATE TABLE IF NOT EXISTS `blog_page_params` (
  `page_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`page_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
