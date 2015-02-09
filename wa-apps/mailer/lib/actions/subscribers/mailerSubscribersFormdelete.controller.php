<?php
/**
 * Deletes Form
 */

class mailerSubscribersFormdeleteController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $form_id = waRequest::post('id',0,'int');

        $mf = new mailerFormModel();
        $mf->deleteById($form_id);

        $mfp = new mailerFormParamsModel();
        $mfp->set($form_id, null);

        $mfsl = new mailerFormSubscribeListsModel();
        $mfsl->updateByFormId($form_id, null);

        $this->response = $form_id;
    }
}