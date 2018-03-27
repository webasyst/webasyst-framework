<?php

$model = new waModel();
$model->exec("ALTER TABLE site_page CHANGE content content LONGTEXT NOT NULL");