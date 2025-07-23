<?php
class siteEditorFormRenderController extends waController
{
    public function execute()
    {
        $app_id = waRequest::request('app_id');
        $id = waRequest::request('id');
        $smarty_code = '{$wa->'.$app_id.'->form('.$id.')}';

        try {
            $html = wa()->getView()->fetch('string:'.$smarty_code);
        } catch (Throwable $e) {
            if (wa()->getUser()->getId() && wa()->getUser()->get('is_user') > 0) {
                $html = '<br><br> (!!)'.$e->getMessage();
            }
        }
        echo $html;
    }
}
