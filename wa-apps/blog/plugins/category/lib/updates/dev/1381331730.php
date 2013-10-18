<?php
$sqls = array();
$sqls[] = 'ALTER TABLE  `blog_category` ADD  `sort` INT NOT NULL DEFAULT 0';
$sqls[] = 'ALTER TABLE  `blog_category` ADD  `url` varchar(255)';
$sqls[] = 'ALTER TABLE  `blog_category` ADD INDEX `sort` (  `sort` )';
$sqls[] = 'ALTER TABLE  `blog_category` ADD UNIQUE INDEX `url` (  `url` )';
$sqls[] = 'UPDATE `blog_category` SET `sort`=`id`';
//EOF

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