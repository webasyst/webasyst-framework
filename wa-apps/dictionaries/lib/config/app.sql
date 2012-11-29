DROP TABLE IF EXISTS `dictionaries`;
CREATE TABLE IF NOT EXISTS `dictionaries` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `color_class` VARCHAR(32) NOT NULL DEFAULT 'c-white',
        `icon` VARCHAR(255) NOT NULL DEFAULT 'notebook',
        `count` INT NOT NULL DEFAULT '0',
        `sort` INT NOT NULL DEFAULT '0',
        INDEX (`sort`)
) ENGINE = MYISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `dictionaries_items`;
CREATE TABLE `dictionaries_items` (
        `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
        `dictionary_id` INT(11) UNSIGNED NOT NULL ,
	`name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
	`value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	`desc` varchar(1024) COLLATE utf8_unicode_ci NOT NULL,
	`visible` tinyint(4) NOT NULL,
	`sort` int(11) NOT NULL DEFAULT '0',
PRIMARY KEY (`id`),
KEY `name` (`name`)
) ENGINE=MYISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;