<?php
$sqls = array();
$sqls[] = <<<SQL
UPDATE `wa_app_settings`
SET app_id = CONCAT('blog.', SUBSTR(name, 8, POSITION('.' IN SUBSTR(name, 8)) - 1)),
name = SUBSTR(name, 8 + POSITION('.' IN SUBSTR(name, 8)))
WHERE `app_id`= 'blog' AND `name` LIKE 'plugin.%'
SQL;

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

$cache = new waSerializeCache('app_settings/blog', SystemConfig::isDebug() ? 600 : 86400, 'webasyst');
$cache->delete();
