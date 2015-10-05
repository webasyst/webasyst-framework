<?php

$model = new blogTagPluginModel();

$ids = array_keys(
        $model->query("
            SELECT t.id, COUNT(pt.tag_id) cnt FROM `blog_tag` t 
            LEFT JOIN `blog_post_tag` pt ON t.id = pt.tag_id
            GROUP BY t.id
            HAVING cnt = 0
        ")->fetchAll('id')
);

if ($ids) {
    $model->deleteById($ids);
}