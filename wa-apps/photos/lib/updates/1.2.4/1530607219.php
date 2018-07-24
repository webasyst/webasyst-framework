<?php

$model = new waModel();

try {
    $model->exec("ALTER TABLE photos_page MODIFY content mediumtext NOT NULL");
} catch (waException $e) {

}