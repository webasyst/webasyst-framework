<?php

$mod = new waModel();
try {
    $mod->exec("SELECT fav_sort FROM wa_region LIMIT 0");
} catch (waDbException $e) {
    $mod->exec("ALTER TABLE `wa_region` ADD `fav_sort` INT NULL DEFAULT NULL");
}

try {
    $mod->exec("SELECT fav_sort FROM wa_country LIMIT 0");
} catch (waDbException $e) {
    $mod->exec("ALTER TABLE `wa_country` ADD `fav_sort` INT NULL DEFAULT NULL");
}

