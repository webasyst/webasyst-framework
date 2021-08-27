<?php

$model = new waModel();

try {
    $model->query('SELECT `part_number` FROM `wa_transaction` WHERE 0');
} catch (waDbException $e) {
    $query = "ALTER TABLE `wa_transaction` ADD `part_number` INT(11) NOT NULL DEFAULT '0' AFTER `order_id`";
    $model->exec($query);
}



