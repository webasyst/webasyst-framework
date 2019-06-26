<?php

$m = new waModel();
$sql = "UPDATE `wa_region` SET `name` = 'Georgia State' WHERE `country_iso3` = 'usa' AND `code` = 'GA' AND `name` = 'Georgia'";

try {
    $m->exec($sql);
} catch (waException $e) {}