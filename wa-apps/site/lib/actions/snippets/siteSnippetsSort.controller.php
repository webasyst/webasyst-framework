<?php 

class siteSnippetsSortController extends waJsonController
{
    public function execute()
    {
        $model = new siteSnippetModel();
        $this->response = $model->move(waRequest::post('id'), waRequest::post('pos', 1, 'int'));
    }
    
}