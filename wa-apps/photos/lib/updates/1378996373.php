<?php

$model = new waModel();

$sql = "ALTER TABLE `photos_photo` CHANGE rate rate DECIMAL(3,2) NOT NULL DEFAULT 0";
$model->exec($sql);
