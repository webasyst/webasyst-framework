<?php

$m = new waGroupModel();

try {
    $m->query("SELECT icon FROM `wa_group` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `wa_group` ADD COLUMN `icon` VARCHAR(255) NULL DEFAULT NULL");
}

try {
    $m->query("SELECT sort FROM `wa_group` WHERE 0");
} catch (waDbException $e) {
    $m->exec("ALTER TABLE `wa_group` ADD COLUMN `sort` INT(11) NULL DEFAULT NULL");
}

$sort = 0;
$res = $m->query("SELECT * FROM `wa_group` ORDER BY name");
foreach ($res as $item) {
    $m->updateById($item['id'], array('sort' => $sort++));
}