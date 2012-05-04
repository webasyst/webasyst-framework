<?php

class blogTagPluginBackendListController extends waController
{
    public function execute()
    {
        $limit = 30;
        $query = waRequest::request('q', '', waRequest::TYPE_STRING_TRIM);

        $tag_model = new blogTagPluginModel();
        $tags = $tag_model->search($query,$limit);

        echo implode("\n", array_keys($tags));
    }
}