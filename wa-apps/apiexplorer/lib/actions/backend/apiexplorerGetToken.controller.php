<?php

class apiexplorerGetTokenController extends apiexplorerJsonController
{
    public function execute()
    {
        $login = waRequest::get('user', false, waRequest::TYPE_STRING_TRIM);
        $user = $login && wa()->getUser()->isAdmin() ? waUser::getByLogin($login) : wa()->getUser();

        $scope = waRequest::post('scope', implode(',', array_keys($user->getApps())), waRequest::TYPE_STRING_TRIM);
        $scope = explode(',', $scope);
        sort($scope);
        $scope = implode(',', $scope);

        $token = (new waApiTokensModel())->getToken(apiexplorerConfig::API_CLIENT_ID, $user->getId(), $scope);

        $this->response = ['token' => $token];
    }
}
