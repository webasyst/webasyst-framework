<?php

/**
 * Save or update Form
 */
class mailerSubscribersFormsaveController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }

        $form_id = waRequest::post('id',0,'int');
        $form = waRequest::post('form');
        $form_params = waRequest::post('params');

        if (isset($form_params['confirm_mail_from'])) {
            $emailvalidator = new waEmailValidator();
            if (!$emailvalidator->isValid($form_params['confirm_mail_from']) || strlen(trim($form_params['confirm_mail_from'])) == 0) {
                $this->errors = 'confirm_mail_from';
                return;
            }
        }

        $mf = new mailerFormModel();
        $form_id = $mf->save($form_id, $form);

        $mfp = new mailerFormParamsModel();
        $mfp->set($form_id, $form_params);

        $mfsl = new mailerFormSubscribeListsModel();
        // all subscribers list by default
        $form['lists'] = isset($form['lists']) ? $form['lists'] : array();
        $mfsl->updateByFormId($form_id, $form['lists']);

        $this->response = $form_id;
    }
} 