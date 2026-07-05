<?php 

class siteBlocksDeleteController extends waJsonController
{
    public function execute()
    {
        $id = waRequest::post('id');
        $model = new siteBlockModel();
        $block = $model->getById($id);
        if ($block) {
            $model->deleteById($id);
            $this->logAction('block_delete');
        }
    }
}