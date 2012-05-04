DROP TABLE IF EXISTS `blog_favorite`;
CREATE TABLE IF NOT EXISTS `blog_favorite` (
  `contact_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (  `contact_id` ,  `post_id` )
) ENGINE=MyISAM DEFAULT CHARSET=utf8;