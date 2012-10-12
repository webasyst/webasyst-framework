<?php

$model = new waModel();

try {
    $model->exec("ALTER TABLE `checklists_list` CHANGE `color_class` `color_class` VARCHAR(32) NOT NULL DEFAULT 'c-white'");
} catch (waDbException $e) {}

