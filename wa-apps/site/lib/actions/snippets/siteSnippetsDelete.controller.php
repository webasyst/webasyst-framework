<?php 

class siteSnippetsDeleteController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id');
        $model = new siteSnippetModel();
        $block = $model->getById($id);
        if ($block) {
            $model->deleteById($id);
        }
    }
}