<?php

class waPageDeleteController extends waPageJsonController
{
    public function execute()
    {
        $id = waRequest::post('id');
        $page_model = $this->getPageModel();
        $page = $page_model->getById($id);
        if ($page) {
            $page_model->delete($id);
        }
    }
}