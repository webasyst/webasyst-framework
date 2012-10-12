<?php

$model = new waModel();

try {
    $model->exec("SELECT `sort` FROM `checklists_list` WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE `checklists_list` ADD `sort` INT NOT NULL DEFAULT '0', ADD INDEX (`sort`)");
}

