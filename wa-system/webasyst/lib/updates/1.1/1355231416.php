<?php

$mod = new waModel();
try {
    $mod->exec("SELECT * FROM wa_region LIMIT 0");
} catch (waDbException $e) {
    $mod->exec("CREATE TABLE IF NOT EXISTS `wa_region` (
                 `country_iso3` varchar(3) NOT NULL,
                 `code` varchar(8) NOT NULL,
                 `name` varchar(255) NOT NULL,
                 PRIMARY KEY (`country_iso3`,`code`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
}

