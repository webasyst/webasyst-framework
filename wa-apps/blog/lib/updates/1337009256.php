<?php
$sqls = array();
$sqls[] = 'ALTER TABLE  `blog_comment` CHANGE  `email`  `email` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL';
$sqls[] = 'ALTER TABLE  `blog_comment` CHANGE  `name`  `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL';
$sqls[] = 'ALTER TABLE  `blog_comment` CHANGE  `site`  `site` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL';
$sqls[] = 'ALTER TABLE  `blog_post` CHANGE  `text`  `text` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';

$model = new waModel();
foreach ($sqls as $sql) {
    try {
        $model->exec($sql);
    }catch(Exception $ex) {
        if (class_exists('waLog')) {
            waLog::log(basename(__FILE__).': '.$ex->getMessage(),'blog-update.log');
        }
    }
}