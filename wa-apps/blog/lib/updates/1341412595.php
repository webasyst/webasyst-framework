<?php
/**
 * @name 1341412595.php
 */
$sqls = array();
$sqls[] = 'ALTER TABLE  `blog_post` DROP INDEX  `blog_id`';
$sqls[] = 'ALTER TABLE  `blog_post` DROP INDEX  `status`';
$sqls[] = 'ALTER TABLE  `blog_post` ADD INDEX `blog` (  `status` ,  `blog_id`,  `datetime` )';


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