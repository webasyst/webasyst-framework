<?php
$table_indexes = array(

    'blog_blog'    => array(
        'list'    => 'status, sort',
        'routing' => 'url, status',
        'sort'    => null,
        'status'  => null,
    ),
    'blog_comment' => array(
        'count'   => 'blog_id, post_id, status',
        'comment' => 'post_id, left',
        'post_id' => null,
    ),
    'blog_post'    => array(
        'feed'       => 'status, blog_id, datetime',
        'routing'    => 'status, url, blog_id',
        'contact'    => 'contact_id, blog_id, status, datetime',
        'contact_id' => null,
        'blog'       => null,

    ),
);

$model = new waModel();
foreach ($table_indexes as $table => $indexes) {
    foreach ($indexes as $index => $fields) {
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }
        $fields = implode('`, `', array_map('trim', $fields));
        if ($fields) {
            $sql = "ALTER TABLE `{$table}` ADD INDEX `{$index}` (`{$fields}`)";
        } else {
            $sql = "ALTER TABLE  `{$table}` DROP INDEX  `{$index}`";
        }
        try {
            $model->exec($sql);
        } catch (Exception $ex) {
            if (class_exists('waLog')) {
                waLog::log(basename(__FILE__).': '.$ex->getMessage(), 'blog-update.log');
            }
        }
    }
}
