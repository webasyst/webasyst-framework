DROP TABLE IF EXISTS `photos_photo`;
CREATE TABLE IF NOT EXISTS `photos_photo` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `description` text NULL,
  `ext` varchar(10) default NULL,
  `size` int(11) default NULL,
  `type` varchar(50) default NULL,
  `rate` tinyint(1) NOT NULL DEFAULT '0',
  `width` int(5) NOT NULL default '0',
  `height` int(5) NOT NULL default '0',
  `contact_id` int(11) NOT NULL,
  `upload_datetime` datetime NOT NULL,
  `edit_datetime` datetime DEFAULT NULL,
  `status` SMALLINT(6) NOT NULL DEFAULT '0',
  `hash` VARCHAR(32) NOT NULL DEFAULT '',
  `url` varchar(255) DEFAULT NULL,
  `parent_id` INT(11) NOT NULL DEFAULT '0',
  `stack_count` INT(11) NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `url` (`url`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `photos_photo_tags`;
CREATE TABLE IF NOT EXISTS `photos_photo_tags` (
  `photo_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`photo_id`,`tag_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `photos_photo_exif`;
CREATE TABLE IF NOT EXISTS `photos_photo_exif` (
  `photo_id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`photo_id`,`name`),
  KEY `exif` (`name`, `value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `photos_tag`;
CREATE TABLE IF NOT EXISTS `photos_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `photos_album`;
CREATE TABLE IF NOT EXISTS `photos_album` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `type` int(1) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL,
  `note` varchar(255) NULL DEFAULT NULL,
  `description` text,
  `hash` VARCHAR(32) NOT NULL DEFAULT '',
  `url` varchar(255) DEFAULT NULL,
  `full_url` varchar(255) DEFAULT NULL,
  `status` smallint(6) NOT NULL DEFAULT '0',
  `conditions` text,
  `create_datetime` datetime NOT NULL,
  `contact_id` int(11) NOT NULL,
  `thumb` int(11) NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`parent_id`, `url`),
  UNIQUE KEY `full_url` (`full_url`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `photos_album_params`;
CREATE TABLE IF NOT EXISTS `photos_album_params` (
  `album_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`album_id`, `name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `photos_album_count`;
CREATE TABLE IF NOT EXISTS `photos_album_count` (
  `album_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY (`album_id`,`contact_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `photos_album_photos`;
CREATE TABLE IF NOT EXISTS `photos_album_photos` (
  `album_id` int(11) NOT NULL,
  `photo_id` int(11) NOT NULL,
  `sort` int(11) NOT NULL default '0',
  PRIMARY KEY (`album_id`,`photo_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `photos_album_rights`;
CREATE TABLE IF NOT EXISTS `photos_album_rights` (
  `group_id` int(11) NOT NULL,
  `album_id` int(11) NOT NULL,
  PRIMARY KEY (`group_id`,`album_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `photos_photo_rights`;
CREATE TABLE IF NOT EXISTS `photos_photo_rights` (
  `group_id` int(11) NOT NULL,
  `photo_id` int(11) NOT NULL,
  PRIMARY KEY (`group_id`,`photo_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `photos_page`;
CREATE TABLE IF NOT EXISTS `photos_page` (
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

DROP TABLE IF EXISTS `photos_page_params`;
CREATE TABLE IF NOT EXISTS `photos_page_params` (
  `page_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`page_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;  
