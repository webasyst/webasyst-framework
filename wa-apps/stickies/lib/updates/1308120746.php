<?php

// strict mode mysql
$sql = array();
$sql[] = "ALTER TABLE `stickies_sheet` 
CHANGE `background_id` `background_id` varchar(10) DEFAULT ''";
$sql[] = "ALTER TABLE `stickies_sticky` 
CHANGE `size_width` `size_width` int(11) NOT NULL DEFAULT 0,
CHANGE `size_height` `size_height` int(11) NOT NULL DEFAULT 0,
CHANGE `position_top` `position_top` int(11) NOT NULL DEFAULT 0,
CHANGE `position_left` `position_left` int(11) NOT NULL DEFAULT 0,
CHANGE `color` `color` varchar(16) NOT NULL DEFAULT '',
CHANGE `font_size` `font_size` int NOT NULL DEFAULT 0";

$model = new waModel();
foreach ($sql as $q) {
	$model->exec($q);
}
