<?php

/** Managing user API tokens */
class webasystProfileApiController extends waJsonController
{
    protected static $available_actions = array('remove');

    public function execute()
    {
        $user = wa()->getUser();
        $api_token_model = new waApiTokensModel();

        $action = waRequest::post('action', null, waRequest::TYPE_STRING_TRIM);
        $token_id = waRequest::post('token_id', null, waRequest::TYPE_STRING_TRIM);

        if (!in_array($action, self::$available_actions)) {
            return $this->errors[] = _w('Unknown action');
        }

        if ($action === 'remove' && !$token_id) {
            return $this->errors[] = _w('Token not transferred');
        } else {
            return $api_token_model->deleteByField(array('contact_id' => $user->getId(), 'token' => $token_id));
        }
    }
}
