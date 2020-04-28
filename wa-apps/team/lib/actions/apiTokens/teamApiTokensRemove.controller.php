<?php

class teamApiTokensRemoveController extends waJsonController
{
    public function execute()
    {
        if (!wa()->getUser()->isAdmin()) {
            return $this->errors[] = _w('Access denied');
        }

        $api_token_model = new waApiTokensModel();

        $action = waRequest::post('action', null, waRequest::TYPE_STRING_TRIM);
        $token_id = waRequest::post('token_id', null, waRequest::TYPE_STRING_TRIM);
        $contact_id = waRequest::post('contact_id', 0, waRequest::TYPE_INT);

        $available_actions = array('remove');

        if (!in_array($action, $available_actions)) {
            return $this->errors[] = _w('Unknown action');
        }

        if ($action === 'remove' && !$token_id) {
            return $this->errors[] = _w('The token was not transferred.');
        } else {
            return $api_token_model->deleteByField(array('contact_id' => $contact_id, 'token' => $token_id));
        }
    }
}