<?php

class photosTagListController extends waController
{
    public function execute()
    {
        $query = waRequest::request('q', '', waRequest::TYPE_STRING_TRIM);

        $tag_model = new photosTagModel();
        $tags = $tag_model->select('name')->where("name LIKE '" . $tag_model->escape($query, 'like') . "%'")->fetchAll('name', true);
        $tags = array_keys($tags);
        foreach ($tags as &$tag) {
            $tag = photosPhoto::escape($tag);
        }
        unset($tag);
        echo implode("\n", $tags);
    }
}