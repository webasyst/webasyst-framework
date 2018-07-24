<?php

$model = new waModel();

try {
    $model->exec("ALTER TABLE blog_page MODIFY content mediumtext");
} catch (waException $e) {

}