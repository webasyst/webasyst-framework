<?php

$model = new waModel();
try {
    $model->exec("ALTER TABLE site_domain ADD  style VARCHAR(255) NOT NULL DEFAULT ''");
} catch (waDbException $e) {
    if ($e->getCode() != 1060) {
        throw $e;
    }
}