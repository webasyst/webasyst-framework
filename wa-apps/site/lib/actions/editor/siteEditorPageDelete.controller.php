<?php
class siteEditorPageDeleteController extends waJsonController
{
    public function execute()
    {
        $page_id = waRequest::request('id', null, 'int');

        if (!$page_id) {
            // should never happen
            $this->errors = [[
                'code' => 'page_id_required',
                'description' => 'page_id is required',
            ]];
            return;
        }

        $blockpage_model = new siteBlockpageModel();
        if (
            !waRequest::request('confirm_multiple_delete') &&
            $blockpage_model->countByField('parent_id', $page_id) > 0
        ) {
            $this->response = ['multiple_delete' => true];
            return;
        }

        $blockpage_model->delete($page_id);
    }
}
