<?php

$model = new waModel();

try {
    $model->query("SELECT text_markdown FROM blog_post WHERE 0");
    $model->exec("ALTER TABLE blog_post ADD COLUMN text_markdown MEDIUMTEXT NOT NULL");
} catch (waException $e) {
}