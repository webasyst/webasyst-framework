<?php

$sqls = array(
    "ALTER TABLE `site_page` CHANGE `route` `route` VARCHAR( 64 ) NOT NULL DEFAULT  ''",
    "ALTER TABLE `site_page` ADD INDEX `url` ( `domain_id` , `route` , `full_url` )",
    "ALTER TABLE `site_page` ADD INDEX `parent_id` ( `parent_id` )"
);

$model = new waModel();
foreach ($sqls as $sql) {
    try {
        $model->exec($sql);
    } catch (waDbException $e) {
        waLog::log(__FILE__.":".$e->getMessage(), 'site-update.log');
    }
}
