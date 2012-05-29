<?php

class blogTagPluginBackendListController extends waController
{
    public function execute()
    {
        $limit = 30;
        $query = waRequest::request('q', '', waRequest::TYPE_STRING_TRIM);

        $tag_model = new blogTagPluginModel();
        $tags = $tag_model->search($query,$limit);
        $tags = array_keys($tags);
        foreach ($tags as &$tag) {
            $tag = htmlspecialchars($tag);
        }
        unset($tag);
        echo implode("\n", $tags);
    }
}