<?php

$model = new waModel();
try {
    $model->query("SELECT source FROM photos_photo WHERE 0");
} catch (waException $e) {
    $model->exec("ALTER TABLE photos_photo ADD COLUMN source VARCHAR(32) NOT NULL DEFAULT 'backend'");
}

