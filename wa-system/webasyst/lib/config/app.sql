DROP TABLE IF EXISTS `wa_contact`;
CREATE TABLE IF NOT EXISTS `wa_contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `firstname` varchar(50) NOT NULL DEFAULT '',
  `middlename` varchar(50) NOT NULL DEFAULT '',
  `lastname` varchar(50) NOT NULL DEFAULT '',
  `title` varchar(50) NOT NULL DEFAULT '',
  `company` varchar(150) NOT NULL DEFAULT '',
  `company_contact_id` int(11) NOT NULL DEFAULT '0',
  `is_company` tinyint(1) NOT NULL DEFAULT '0',
  `is_user` tinyint(1) NOT NULL DEFAULT '0',
  `login` varchar(32) DEFAULT NULL,
  `password` varchar(32) NOT NULL DEFAULT '',
  `last_datetime` datetime DEFAULT NULL,
  `sex` enum('m','f') DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `about` text,
  `photo` INT UNSIGNED NOT NULL DEFAULT  '0',
  `create_datetime` datetime NOT NULL,
  `create_app_id` varchar(32) NOT NULL DEFAULT '',
  `create_method` varchar(32) NOT NULL DEFAULT '',
  `create_contact_id` int(11) NOT NULL DEFAULT '0',
  `locale` varchar(8) NOT NULL DEFAULT '',
  `timezone` varchar(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `name` (`name`),
  KEY `id_name` (`id`,`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_contact_categories`;
CREATE TABLE IF NOT EXISTS `wa_contact_categories` (
  `category_id` int(11) NOT NULL,
  `contact_id` int(11) NOT NULL,
  PRIMARY KEY (`category_id`,`contact_id`),
  KEY `contact_id` (`contact_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_contact_category`;
CREATE TABLE IF NOT EXISTS `wa_contact_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `system_id` varchar(64) DEFAULT NULL,
  `cnt` INT NOT NULL DEFAULT  '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_id` (`system_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_contact_data`;
CREATE TABLE IF NOT EXISTS `wa_contact_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) NOT NULL,
  `field` varchar(32) NOT NULL,
  `ext` varchar(32) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_field_sort` (`contact_id`,`field`,`sort`),
  KEY `contact_id` (`contact_id`),
  KEY `value` (`value`),
  KEY `field` (`field`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_contact_emails`;
CREATE TABLE IF NOT EXISTS `wa_contact_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ext` varchar(32) NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT '0',
  `status` ENUM(  'unknown',  'confirmed',  'unconfirmed',  'unavailable' ) NOT NULL DEFAULT  'unknown',
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_sort` (`contact_id`,`sort`),
  KEY `email` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_contact_rights`;
CREATE TABLE IF NOT EXISTS `wa_contact_rights` (
  `group_id` int(11) NOT NULL,
  `app_id` varchar(32) NOT NULL,
  `name` varchar(64) NOT NULL,
  `value` int(11) NOT NULL,
  PRIMARY KEY (`group_id`,`app_id`,`name`),
  KEY `name_value` (`name`,`value`,`group_id`,`app_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_contact_settings`;
CREATE TABLE IF NOT EXISTS `wa_contact_settings` (
  `contact_id` int(11) NOT NULL,
  `app_id` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(32) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`contact_id`,`app_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_country`;
CREATE TABLE IF NOT EXISTS `wa_country` (
  `name` varchar(255) NOT NULL,
  `iso3letter` varchar(3) NOT NULL,
  `iso2letter` varchar(2) NOT NULL,
  `isonumeric` varchar(3) NOT NULL,
  `locale` varchar(5) NOT NULL,
  PRIMARY KEY (`locale`,`iso2letter`),
  UNIQUE KEY `iso3letter` (`iso3letter`,`locale`),
  UNIQUE KEY `isonumeric` (`isonumeric`,`locale`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `wa_country` (`name`, `iso3letter`, `iso2letter`, `isonumeric`, `locale`) VALUES
('Aruba', 'abw', 'aw', '533', 'en_US'),
('Afghanistan', 'afg', 'af', '004', 'en_US'),
('Angola', 'ago', 'ao', '024', 'en_US'),
('Anguilla', 'aia', 'ai', '660', 'en_US'),
('Åland Islands', 'ala', 'ax', '248', 'en_US'),
('Albania', 'alb', 'al', '008', 'en_US'),
('Andorra', 'and', 'ad', '020', 'en_US'),
('Netherlands Antilles', 'ant', 'an', '530', 'en_US'),
('United Arab Emirates', 'are', 'ae', '784', 'en_US'),
('Argentina', 'arg', 'ar', '032', 'en_US'),
('Armenia', 'arm', 'am', '051', 'en_US'),
('American Samoa', 'asm', 'as', '016', 'en_US'),
('French Southern Territories', 'atf', 'tf', '260', 'en_US'),
('Antigua and Barbuda', 'atg', 'ag', '028', 'en_US'),
('Australia', 'aus', 'au', '036', 'en_US'),
('Austria', 'aut', 'at', '040', 'en_US'),
('Azerbaijan', 'aze', 'az', '031', 'en_US'),
('Burundi', 'bdi', 'bi', '108', 'en_US'),
('Belgium', 'bel', 'be', '056', 'en_US'),
('Benin', 'ben', 'bj', '204', 'en_US'),
('Burkina Faso', 'bfa', 'bf', '854', 'en_US'),
('Bangladesh', 'bgd', 'bd', '050', 'en_US'),
('Bulgaria', 'bgr', 'bg', '100', 'en_US'),
('Bahrain', 'bhr', 'bh', '048', 'en_US'),
('Bahamas', 'bhs', 'bs', '044', 'en_US'),
('Bosnia and Herzegovina', 'bih', 'ba', '070', 'en_US'),
('Belarus', 'blr', 'by', '112', 'en_US'),
('Belize', 'blz', 'bz', '084', 'en_US'),
('Bermuda', 'bmu', 'bm', '060', 'en_US'),
('Bolivia, Plurinational State of', 'bol', 'bo', '068', 'en_US'),
('Brazil', 'bra', 'br', '076', 'en_US'),
('Barbados', 'brb', 'bb', '052', 'en_US'),
('Brunei Darussalam', 'brn', 'bn', '096', 'en_US'),
('Bhutan', 'btn', 'bt', '064', 'en_US'),
('Bouvet Island', 'bvt', 'bv', '074', 'en_US'),
('Botswana', 'bwa', 'bw', '072', 'en_US'),
('Central African Republic', 'caf', 'cf', '140', 'en_US'),
('Canada', 'can', 'ca', '124', 'en_US'),
('Cocos (Keeling) Islands', 'cck', 'cc', '166', 'en_US'),
('Switzerland', 'che', 'ch', '756', 'en_US'),
('Chile', 'chl', 'cl', '152', 'en_US'),
('China', 'chn', 'cn', '156', 'en_US'),
('Côte d''Ivoire', 'civ', 'ci', '384', 'en_US'),
('Cameroon', 'cmr', 'cm', '120', 'en_US'),
('Congo, the Democratic Republic of the', 'cod', 'cd', '180', 'en_US'),
('Congo', 'cog', 'cg', '178', 'en_US'),
('Cook Islands', 'cok', 'ck', '184', 'en_US'),
('Colombia', 'col', 'co', '170', 'en_US'),
('Comoros', 'com', 'km', '174', 'en_US'),
('Cape Verde', 'cpv', 'cv', '132', 'en_US'),
('Costa Rica', 'cri', 'cr', '188', 'en_US'),
('Cuba', 'cub', 'cu', '192', 'en_US'),
('Christmas Island', 'cxr', 'cx', '162', 'en_US'),
('Cayman Islands', 'cym', 'ky', '136', 'en_US'),
('Cyprus', 'cyp', 'cy', '196', 'en_US'),
('Czech Republic', 'cze', 'cz', '203', 'en_US'),
('Germany', 'deu', 'de', '276', 'en_US'),
('Djibouti', 'dji', 'dj', '262', 'en_US'),
('Dominica', 'dma', 'dm', '212', 'en_US'),
('Denmark', 'dnk', 'dk', '208', 'en_US'),
('Dominican Republic', 'dom', 'do', '214', 'en_US'),
('Algeria', 'dza', 'dz', '012', 'en_US'),
('Ecuador', 'ecu', 'ec', '218', 'en_US'),
('Egypt', 'egy', 'eg', '818', 'en_US'),
('Eritrea', 'eri', 'er', '232', 'en_US'),
('Western Sahara', 'esh', 'eh', '732', 'en_US'),
('Spain', 'esp', 'es', '724', 'en_US'),
('Estonia', 'est', 'ee', '233', 'en_US'),
('Ethiopia', 'eth', 'et', '231', 'en_US'),
('Finland', 'fin', 'fi', '246', 'en_US'),
('Fiji', 'fji', 'fj', '242', 'en_US'),
('Falkland Islands (Malvinas)', 'flk', 'fk', '238', 'en_US'),
('France', 'fra', 'fr', '250', 'en_US'),
('Faroe Islands', 'fro', 'fo', '234', 'en_US'),
('Micronesia, Federated States of', 'fsm', 'fm', '583', 'en_US'),
('Gabon', 'gab', 'ga', '266', 'en_US'),
('United Kingdom', 'gbr', 'gb', '826', 'en_US'),
('Georgia', 'geo', 'ge', '268', 'en_US'),
('Ghana', 'gha', 'gh', '288', 'en_US'),
('Gibraltar', 'gib', 'gi', '292', 'en_US'),
('Guinea', 'gin', 'gn', '324', 'en_US'),
('Guadeloupe', 'glp', 'gp', '312', 'en_US'),
('Gambia', 'gmb', 'gm', '270', 'en_US'),
('Guinea-Bissau', 'gnb', 'gw', '624', 'en_US'),
('Equatorial Guinea', 'gnq', 'gq', '226', 'en_US'),
('Greece', 'grc', 'gr', '300', 'en_US'),
('Grenada', 'grd', 'gd', '308', 'en_US'),
('Greenland', 'grl', 'gl', '304', 'en_US'),
('Guatemala', 'gtm', 'gt', '320', 'en_US'),
('French Guiana', 'guf', 'gf', '254', 'en_US'),
('Guam', 'gum', 'gu', '316', 'en_US'),
('Guyana', 'guy', 'gy', '328', 'en_US'),
('Hong Kong', 'hkg', 'hk', '344', 'en_US'),
('Heard Island and McDonald Islands', 'hmd', 'hm', '334', 'en_US'),
('Honduras', 'hnd', 'hn', '340', 'en_US'),
('Croatia', 'hrv', 'hr', '191', 'en_US'),
('Haiti', 'hti', 'ht', '332', 'en_US'),
('Hungary', 'hun', 'hu', '348', 'en_US'),
('Indonesia', 'idn', 'id', '360', 'en_US'),
('India', 'ind', 'in', '356', 'en_US'),
('British Indian Ocean Territory', 'iot', 'io', '086', 'en_US'),
('Ireland', 'irl', 'ie', '372', 'en_US'),
('Iran, Islamic Republic of', 'irn', 'ir', '364', 'en_US'),
('Iraq', 'irq', 'iq', '368', 'en_US'),
('Iceland', 'isl', 'is', '352', 'en_US'),
('Israel', 'isr', 'il', '376', 'en_US'),
('Italy', 'ita', 'it', '380', 'en_US'),
('Jamaica', 'jam', 'jm', '388', 'en_US'),
('Jordan', 'jor', 'jo', '400', 'en_US'),
('Japan', 'jpn', 'jp', '392', 'en_US'),
('Kazakhstan', 'kaz', 'kz', '398', 'en_US'),
('Kenya', 'ken', 'ke', '404', 'en_US'),
('Kyrgyzstan', 'kgz', 'kg', '417', 'en_US'),
('Cambodia', 'khm', 'kh', '116', 'en_US'),
('Kiribati', 'kir', 'ki', '296', 'en_US'),
('Saint Kitts and Nevis', 'kna', 'kn', '659', 'en_US'),
('Korea, Republic of', 'kor', 'kr', '410', 'en_US'),
('Kuwait', 'kwt', 'kw', '414', 'en_US'),
('Lao People''s Democratic Republic', 'lao', 'la', '418', 'en_US'),
('Lebanon', 'lbn', 'lb', '422', 'en_US'),
('Liberia', 'lbr', 'lr', '430', 'en_US'),
('Libyan Arab Jamahiriya', 'lby', 'ly', '434', 'en_US'),
('Saint Lucia', 'lca', 'lc', '662', 'en_US'),
('Liechtenstein', 'lie', 'li', '438', 'en_US'),
('Sri Lanka', 'lka', 'lk', '144', 'en_US'),
('Lesotho', 'lso', 'ls', '426', 'en_US'),
('Lithuania', 'ltu', 'lt', '440', 'en_US'),
('Luxembourg', 'lux', 'lu', '442', 'en_US'),
('Latvia', 'lva', 'lv', '428', 'en_US'),
('Macao', 'mac', 'mo', '446', 'en_US'),
('Morocco', 'mar', 'ma', '504', 'en_US'),
('Monaco', 'mco', 'mc', '492', 'en_US'),
('Moldova, Republic of', 'mda', 'md', '498', 'en_US'),
('Madagascar', 'mdg', 'mg', '450', 'en_US'),
('Maldives', 'mdv', 'mv', '462', 'en_US'),
('Mexico', 'mex', 'mx', '484', 'en_US'),
('Marshall Islands', 'mhl', 'mh', '584', 'en_US'),
('Macedonia, the former Yugoslav Republic of', 'mkd', 'mk', '807', 'en_US'),
('Mali', 'mli', 'ml', '466', 'en_US'),
('Malta', 'mlt', 'mt', '470', 'en_US'),
('Myanmar', 'mmr', 'mm', '104', 'en_US'),
('Montenegro', 'mne', 'me', '499', 'en_US'),
('Mongolia', 'mng', 'mn', '496', 'en_US'),
('Northern Mariana Islands', 'mnp', 'mp', '580', 'en_US'),
('Mozambique', 'moz', 'mz', '508', 'en_US'),
('Mauritania', 'mrt', 'mr', '478', 'en_US'),
('Montserrat', 'msr', 'ms', '500', 'en_US'),
('Martinique', 'mtq', 'mq', '474', 'en_US'),
('Mauritius', 'mus', 'mu', '480', 'en_US'),
('Malawi', 'mwi', 'mw', '454', 'en_US'),
('Malaysia', 'mys', 'my', '458', 'en_US'),
('Mayotte', 'myt', 'yt', '175', 'en_US'),
('Namibia', 'nam', 'na', '516', 'en_US'),
('New Caledonia', 'ncl', 'nc', '540', 'en_US'),
('Niger', 'ner', 'ne', '562', 'en_US'),
('Norfolk Island', 'nfk', 'nf', '574', 'en_US'),
('Nigeria', 'nga', 'ng', '566', 'en_US'),
('Nicaragua', 'nic', 'ni', '558', 'en_US'),
('Niue', 'niu', 'nu', '570', 'en_US'),
('Netherlands', 'nld', 'nl', '528', 'en_US'),
('Norway', 'nor', 'no', '578', 'en_US'),
('Nepal', 'npl', 'np', '524', 'en_US'),
('Nauru', 'nru', 'nr', '520', 'en_US'),
('New Zealand', 'nzl', 'nz', '554', 'en_US'),
('Oman', 'omn', 'om', '512', 'en_US'),
('Pakistan', 'pak', 'pk', '586', 'en_US'),
('Panama', 'pan', 'pa', '591', 'en_US'),
('Pitcairn', 'pcn', 'pn', '612', 'en_US'),
('Peru', 'per', 'pe', '604', 'en_US'),
('Philippines', 'phl', 'ph', '608', 'en_US'),
('Palau', 'plw', 'pw', '585', 'en_US'),
('Papua New Guinea', 'png', 'pg', '598', 'en_US'),
('Poland', 'pol', 'pl', '616', 'en_US'),
('Puerto Rico', 'pri', 'pr', '630', 'en_US'),
('Korea, Democratic People''s Republic of', 'prk', 'kp', '408', 'en_US'),
('Portugal', 'prt', 'pt', '620', 'en_US'),
('Paraguay', 'pry', 'py', '600', 'en_US'),
('Palestinian Territory, Occupied', 'pse', 'ps', '275', 'en_US'),
('French Polynesia', 'pyf', 'pf', '258', 'en_US'),
('Qatar', 'qat', 'qa', '634', 'en_US'),
('Réunion', 'reu', 're', '638', 'en_US'),
('Romania', 'rou', 'ro', '642', 'en_US'),
('Russian Federation', 'rus', 'ru', '643', 'en_US'),
('Rwanda', 'rwa', 'rw', '646', 'en_US'),
('Saudi Arabia', 'sau', 'sa', '682', 'en_US'),
('Sudan', 'sdn', 'sd', '736', 'en_US'),
('Senegal', 'sen', 'sn', '686', 'en_US'),
('Singapore', 'sgp', 'sg', '702', 'en_US'),
('South Georgia and the South Sandwich Islands', 'sgs', 'gs', '239', 'en_US'),
('Saint Helena, Ascension and Tristan da Cunha', 'shn', 'sh', '654', 'en_US'),
('Svalbard and Jan Mayen', 'sjm', 'sj', '744', 'en_US'),
('Solomon Islands', 'slb', 'sb', '090', 'en_US'),
('Sierra Leone', 'sle', 'sl', '694', 'en_US'),
('El Salvador', 'slv', 'sv', '222', 'en_US'),
('San Marino', 'smr', 'sm', '674', 'en_US'),
('Somalia', 'som', 'so', '706', 'en_US'),
('Saint Pierre and Miquelon', 'spm', 'pm', '666', 'en_US'),
('Serbia', 'srb', 'rs', '688', 'en_US'),
('Sao Tome and Principe', 'stp', 'st', '678', 'en_US'),
('Suriname', 'sur', 'sr', '740', 'en_US'),
('Slovakia', 'svk', 'sk', '703', 'en_US'),
('Slovenia', 'svn', 'si', '705', 'en_US'),
('Sweden', 'swe', 'se', '752', 'en_US'),
('Swaziland', 'swz', 'sz', '748', 'en_US'),
('Seychelles', 'syc', 'sc', '690', 'en_US'),
('Syrian Arab Republic', 'syr', 'sy', '760', 'en_US'),
('Turks and Caicos Islands', 'tca', 'tc', '796', 'en_US'),
('Chad', 'tcd', 'td', '148', 'en_US'),
('Togo', 'tgo', 'tg', '768', 'en_US'),
('Thailand', 'tha', 'th', '764', 'en_US'),
('Tajikistan', 'tjk', 'tj', '762', 'en_US'),
('Tokelau', 'tkl', 'tk', '772', 'en_US'),
('Turkmenistan', 'tkm', 'tm', '795', 'en_US'),
('Timor-Leste', 'tls', 'tl', '626', 'en_US'),
('Tonga', 'ton', 'to', '776', 'en_US'),
('Trinidad and Tobago', 'tto', 'tt', '780', 'en_US'),
('Tunisia', 'tun', 'tn', '788', 'en_US'),
('Turkey', 'tur', 'tr', '792', 'en_US'),
('Tuvalu', 'tuv', 'tv', '798', 'en_US'),
('Taiwan, Province of China', 'twn', 'tw', '158', 'en_US'),
('Tanzania, United Republic of', 'tza', 'tz', '834', 'en_US'),
('Uganda', 'uga', 'ug', '800', 'en_US'),
('Ukraine', 'ukr', 'ua', '804', 'en_US'),
('United States Minor Outlying Islands', 'umi', 'um', '581', 'en_US'),
('Uruguay', 'ury', 'uy', '858', 'en_US'),
('United States', 'usa', 'us', '840', 'en_US'),
('Uzbekistan', 'uzb', 'uz', '860', 'en_US'),
('Holy See (Vatican City State)', 'vat', 'va', '336', 'en_US'),
('Saint Vincent and the Grenadines', 'vct', 'vc', '670', 'en_US'),
('Venezuela, Bolivarian Republic of', 'ven', 've', '862', 'en_US'),
('Virgin Islands, British', 'vgb', 'vg', '092', 'en_US'),
('Virgin Islands, U.S.', 'vir', 'vi', '850', 'en_US'),
('Viet Nam', 'vnm', 'vn', '704', 'en_US'),
('Vanuatu', 'vut', 'vu', '548', 'en_US'),
('Wallis and Futuna', 'wlf', 'wf', '876', 'en_US'),
('Samoa', 'wsm', 'ws', '882', 'en_US'),
('Yemen', 'yem', 'ye', '887', 'en_US'),
('South Africa', 'zaf', 'za', '710', 'en_US'),
('Zambia', 'zmb', 'zm', '894', 'en_US'),
('Zimbabwe', 'zwe', 'zw', '716', 'en_US');

DROP TABLE IF EXISTS `wa_group`;
CREATE TABLE IF NOT EXISTS `wa_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `cnt` INT NOT NULL DEFAULT  '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_log`;
CREATE TABLE IF NOT EXISTS `wa_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_id` varchar(32) NOT NULL,
  `contact_id` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `action` varchar(255) NOT NULL,
  `count` int(11) DEFAULT NULL,
  `params` TEXT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_login_log`;
CREATE TABLE IF NOT EXISTS `wa_login_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) NOT NULL,
  `datetime_in` datetime NOT NULL,
  `datetime_out` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_transaction`;
CREATE TABLE IF NOT EXISTS `wa_transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin` varchar(50) NOT NULL,
  `app_id` varchar(50) NOT NULL,
  `merchant_id` varchar(50) DEFAULT NULL,
  `native_id` varchar(255) NOT NULL,
  `create_datetime` datetime NOT NULL,
  `update_datetime` datetime NOT NULL,
  `type` varchar(20) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `order_id` varchar(50) DEFAULT NULL,
  `customer_id` varchar(50) DEFAULT NULL,
  `result` varchar(20) NOT NULL,
  `error` varchar(255) DEFAULT NULL,
  `state` varchar(20) DEFAULT NULL,
  `view_data` text,
  `amount` FLOAT NULL,
  `currency_id` VARCHAR(3) NULL, 
  PRIMARY KEY (`id`),
  KEY `plugin` (`plugin`),
  KEY `app_id` (`app_id`),
  KEY `merchant_id` (`merchant_id`),
  KEY `transaction_native_id` (`native_id`),
  KEY `parent_id` (`parent_id`),
  KEY `order_id` (`order_id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_transaction_data`;
CREATE TABLE IF NOT EXISTS `wa_transaction_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `field_id` varchar(50) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `field_id` (`field_id`),
  KEY `value` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_user_groups`;
CREATE TABLE IF NOT EXISTS `wa_user_groups` (
  `contact_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`contact_id`,`group_id`),
  KEY `group_id` (`group_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_widget`;
CREATE TABLE IF NOT EXISTS `wa_widget` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL,
  `name` varchar(255) NOT NULL,
  `create_contact_id` int(11) NOT NULL,
  `create_datetme` datetime NOT NULL,
  `app_id` varchar(32) NOT NULL,
  `locale` varchar(16) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`,`app_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_app_settings`;
CREATE TABLE IF NOT EXISTS `wa_app_settings` (
  `app_id` varchar(64) NOT NULL,
  `name` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`app_id`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_announcement`;
CREATE TABLE IF NOT EXISTS `wa_announcement` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_id` varchar(32) NOT NULL,
  `text` text NOT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `app_datetime` (`datetime`,`app_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `wa_contact_data_text`;
CREATE TABLE IF NOT EXISTS `wa_contact_data_text` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(11) NOT NULL,
  `field` varchar(32) NOT NULL,
  `ext` varchar(32) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `contact_field_sort` (`contact_id`,`field`,`sort`),
  KEY `contact_id` (`contact_id`),
  KEY `field` (`field`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `wa_contact_tokens`;
CREATE TABLE IF NOT EXISTS `wa_contact_tokens` (
  `contact_id` int(11) NOT NULL,
  `client_id` varchar(32) NOT NULL,
  `token` varchar(32) NOT NULL,
  `create_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires` int(11) NOT NULL,
  PRIMARY KEY (`token`),
  UNIQUE KEY `contact_client` (`contact_id`,`client_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
