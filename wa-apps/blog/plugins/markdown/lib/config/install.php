<?php

$model = new waModel();

try {
    $model->query("SELECT text_markdown FROM blog_post WHERE 0");
} catch (waException $e) {
    $model->exec("ALTER TABLE blog_post ADD COLUMN text_markdown MEDIUMTEXT NULL");
}

