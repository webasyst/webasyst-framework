<?php

$model = new waModel();

$model->exec("ALTER TABLE  `wa_contact` CHANGE  `photo`  `photo` INT UNSIGNED NOT NULL DEFAULT  '0'");
$model->exec("ALTER TABLE  `wa_contact_category` CHANGE  `system_id`  `system_id` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL");

// Antarctica, Saint Barthélemy, Guernsey, Isle of Man, Jersey, Saint Martin (French part)
$model->exec("DELETE FROM wa_country WHERE isonumeric IN ('010','652','831','833','832','663')");

// EOF
