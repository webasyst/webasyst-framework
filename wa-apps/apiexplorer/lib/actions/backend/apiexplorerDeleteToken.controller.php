<?php

class apiexplorerDeleteTokenController extends apiexplorerJsonController
{
    public function execute()
    {
        $login = waRequest::get('user', false, waRequest::TYPE_STRING_TRIM);
        $user = $login && wa()->getUser()->isAdmin() ? waUser::getByLogin($login) : wa()->getUser();
        $data = [
            'client_id' => apiexplorerConfig::API_CLIENT_ID, 
            'contact_id' => $user->getId()
        ];
        $token = waRequest::post('token', false, waRequest::TYPE_STRING_TRIM);
        if ($token) {
            $data['token'] = $token;
        }
        $token = (new waApiTokensModel())->deleteByField($data);
    }
}
