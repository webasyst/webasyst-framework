<?php

$model = new waModel();
$model->exec("ALTER TABLE `wa_contact_category` CHANGE `system_id` `system_id` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL");

// EOF