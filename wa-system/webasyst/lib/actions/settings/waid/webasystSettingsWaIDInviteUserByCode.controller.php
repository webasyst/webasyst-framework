<?php

class webasystSettingsWaIDInviteUserByCodeController extends waJsonController
{
    public function execute()
    {
        // Close session storage to allow parallel requests
        wa()->getStorage()->close();

        $is_qrcode = !!waRequest::request('qrcode');

        $user_to_invite = $this->getUserToInvite();
        if (!$user_to_invite) {
            $this->errors = _ws('User not found.');
            return;
        }

        // Only allow to send code to self unless global admin.
        if ($user_to_invite['id'] != wa()->getUser()->getId() && !wa()->getUser()->isAdmin()) {
            $this->errors = _ws('Can be used only for yourself.');
            return;
        }

        $binding_token = $this->generateWaidConnectToken($user_to_invite['id']);
        $binding_token = $binding_token['token'];
        $profile_data = [];
        if ($is_qrcode && $user_to_invite['id'] == wa()->getUser()->getId()) {
            $profile_data = [
                'firstname' => wa()->getUser()->get('firstname'),
                'lastname' => wa()->getUser()->get('lastname'),
                'middlename' => wa()->getUser()->get('middlename'),
                'email' => wa()->getUser()->get('email', 'default'),
            ];
            if ((bool)wa()->getUser()->getPhoto()) {
                $profile_data['userpic_original_crop'] = $this->getDataResourceUrl(wa()->getUser()->getPhoto('original_crop'));
            }
        }
        $invitation = (new waWebasystIDApi())->installationCode($binding_token, $is_qrcode, $profile_data);
        if (!empty($invitation['error'])) {
            $this->errors = ifempty($invitation, 'error_description', _ws('Unable to connect to Webasyst API.'));
            return;
        }

        $this->response = [
            'code' => $invitation['code'],
            'expire' => $invitation['expire'],
            'expire_in' => $invitation['expire'] - time(),
        ];
    }

    protected function getUserToInvite()
    {
        $id = $this->getRequest()->post('id');

        $col = new waContactsCollection("id/" . $id);
        $col->addLeftJoin('wa_contact_waid', null, ':table.contact_id IS NULL');
        $col->addWhere('c.is_user=1');

        $users = $col->getContacts("id,is_user");
        $user = reset($users);
        return $user;
    }

    // One-time token for WAID server to connect with to finish binding
    protected function generateWaidConnectToken($user_id)
    {
        $token_search = [
            'app_id' => 'webasyst',
            'type' => 'waid_connect',
            'contact_id' => $user_id,
        ];
        $token_update = [
            'create_contact_id' => wa()->getUser()->getId(),
            'expire_datetime' => date('Y-m-d H:i:s', time() + 1800),
        ];

        $app_tokens_model = new waAppTokensModel();
        $token = $app_tokens_model->getByField($token_search);

        if ($token) {
            $app_tokens_model->updateById($token['token'], $token_update);
            return array_merge($token, $token_update);
        }

        return $app_tokens_model->add($token_search + $token_update);
    }

    protected function getDataResourceUrl($relative_url)
    {
        $cdn = wa()->getCdn($relative_url);
        if ($cdn->count() > 0) {
            return (string)$cdn;
        }
        $host_url = wa()->getConfig()->getHostUrl();
        return rtrim($host_url, '/') . '/' . ltrim($relative_url, '/');
    }
}
