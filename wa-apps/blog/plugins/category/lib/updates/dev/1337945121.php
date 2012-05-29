<?php
$sqls = array();
$sql[] = 'ALTER TABLE  `blog_category` ADD  `sort` INT NOT NULL DEFAULT 0';
$sql[] = 'ALTER TABLE  `blog_category` ADD  `url` varchar(255)';
$sql[] = 'ALTER TABLE  `blog_category` ADD INDEX `sort` (  `sort` )';
$sql[] = 'ALTER TABLE  `blog_category` ADD UNIQUE INDEX `url` (  `url` )';
$sql[] = 'UPDATE `blog_category` SET `sort`=`id`';
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