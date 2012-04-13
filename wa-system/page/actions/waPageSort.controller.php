<?php

class waPageSortController extends waPageJsonController
{
    public function execute()
    {
        $page_model = $this->getPageModel();
        $page_model->move(waRequest::post('id', 0, 'int'), waRequest::post('pos', 1, 'int'));
    }
}