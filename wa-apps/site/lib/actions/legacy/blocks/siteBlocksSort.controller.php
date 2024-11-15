<?php 

class siteBlocksSortController extends waJsonController
{
    public function execute()
    {
        $model = new siteBlockModel();
        $this->response = $model->move(waRequest::post('id'), waRequest::post('pos', 1, 'int'));
    }
    
}