-- sheet table
DROP TABLE IF EXISTS `stickies_sheet`;
CREATE TABLE IF NOT EXISTS `stickies_sheet` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL,
  `sort` int(11) NOT NULL,
  `background_id` varchar(10) DEFAULT '',
  `create_datetime` datetime NOT NULL,
  `creator_contact_id` int(11),
  `qty` int(11) DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- sticky table
DROP TABLE IF EXISTS `stickies_sticky`;
CREATE TABLE IF NOT EXISTS `stickies_sticky` (
  `id` int(11) NOT NULL auto_increment,
  `sheet_id` int(11) NOT NULL,
  `content` TEXT,
  `creator_contact_id` int(11),
  `create_datetime` datetime NOT NULL,
  `update_datetime` datetime NOT NULL,
  `size_width` int(11) NOT NULL DEFAULT 0,
  `size_height` int(11) NOT NULL DEFAULT 0,
  `position_top` int(11) NOT NULL DEFAULT 0,
  `position_left` int(11) NOT NULL DEFAULT 0,
  `color` varchar(16) NOT NULL DEFAULT '',
  `font_size` int NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY `sheet_id` (`sheet_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
