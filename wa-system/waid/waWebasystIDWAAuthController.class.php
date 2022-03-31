<?php

/**
 * Class waWebasystIDWAAuthController
 *
 * Called from waOAuthController for work with waWebasystIDWAAuth adapter, i.e. for auth in WA backend
 */
class waWebasystIDWAAuthController extends waViewController
{
    /**
     * @var waWebasystIDWAAuth
     */
    protected $auth;

    /**
     * @var waWebasystIDClientManager
     */
    protected $cm;

    /**
     * waidWebasystIDAuthController constructor.
     * @param waWebasystIDWAAuth $auth
     */
    public function __construct(waWebasystIDWAAuth $auth)
    {
        $this->auth = $auth;
        $this->cm = new waWebasystIDClientManager();
    }

    public function execute()
    {
        try {
            // this is about webasyst ID binding, see php doc of methods
            if ($this->isUnfinishedBindingProcess()) {
                $this->finishBindingProcess();
                return;
            }

            //
            $this->tryAuth();

        } catch (waWebasystIDException $e) {
            if ($e instanceof waWebasystIDAccessDeniedAuthException && !$this->auth->isBackendAuth()) {
                // if webasyst ID server response 'access_denied' it means that user not allowed authorization, so not showing error (just finish proccess)
                $this->displayAuth(['type' => 'access_denied']);
            } else {
                $this->displayError($e->getMessage());
            }
        } catch (Exception $e) {
            throw $e; // Caught in waSystem->dispatch()
        }
    }

    protected function getAuthType()
    {
        if ($this->auth->isBackendAuth()) {
            $type = 'backend';
        } elseif ($this->auth->getInviteAuthToken()) {
            $type = 'invite';
        } else {
            $type = 'bind';
        }
        return $type;
    }

    /**
     * @throws waException
     * @throws waWebasystIDAuthException
     * @throws waWebasystIDAccessDeniedAuthException
     */
    protected function tryAuth()
    {
        $auth_response_data = $this->auth->auth();

        $type = $this->getAuthType();
        $result = null;

        switch ($type) {
            case 'backend':
                $result = $this->processBackendAuth($auth_response_data);
                break;
            case 'invite':
                $invite_token = $this->auth->getInviteAuthToken();
                $result = $this->processInviteAuth($auth_response_data, $invite_token);
                break;
            case 'bind':
                $result = $this->processBindAuth($auth_response_data);
                break;
        }

        if ($result) {
            // wrap result with type, so we can differ in template how draw different result types
            $this->displayAuth([
                'type' => $type,
                'result' => $result
            ]);
        }
    }

    /**
     * @param array $auth_response_data - here is access token params with expected format:
     *      - string $auth_response_data['access_token'] [required] - access token itself (jwt)
     *      - string $auth_response_data['refresh_token'] [required] - refresh token to refresh access token
     *      - int    $auth_response_data['expires_in'] [optional] - ttl of expiration in seconds
     *      - string $auth_response_data['token_type'] [optional] - "bearer"
     *      - ... and maybe some other fields from Webasyst ID server
     * @return array|NULL $result - IF NULL then will no go further to process webasyst auth result, otherwise array of format:
     *      - bool  $result['status'] - ok or not ok?
     *      - array $result['details']
     *          If ok:
     *              int $result['details']['contact_id']
     *          Otherwise:
     *              string $result['details']['error_code']
     *              string $result['details']['error_message']
     * @throws waException
     */
    protected function processBackendAuth(array $auth_response_data)
    {
        $result = $this->authBackendUser($auth_response_data);

        $is_backend_auth_forced = $this->cm->isBackendAuthForced();

        // save in storage some results which will be used by backend login form (and action)
        // if backend auth forced by webasyst ID - no need to keep results, keep global state clean
        if (!$is_backend_auth_forced) {
            // save result in session
            $this->getStorage()->set('webasyst_id_backend_auth_result', $result);

            // if auth fail because of not bounding, save server data in session for latter use
            if (!$result['status'] && $result['details']['error_code'] === 'not_bound') {
                $this->getStorage()->set('webasyst_id_server_data', $auth_response_data);
            }
        }

        return $result;
    }

    /**
     * @param array $auth_response_data - here is access token params with expected format:
     *      - string $auth_response_data['access_token'] [required] - access token itself (jwt)
     *      - string $auth_response_data['refresh_token'] [required] - refresh token to refresh access token
     *      - int    $auth_response_data['expires_in'] [optional] - ttl of expiration in seconds
     *      - string $auth_response_data['token_type'] [optional] - "bearer"
     * @param string $invite_token
     * @return array $result
     *      bool    $result['status']
     *      array   $result['details']
     *          IF $result['status'] === FALSE:
     *              array $result['details']['error_code']
     *              array $result['details']['error_message'] [optional]
     *              array $result['details']['webasyst_contact_info'] [optional] - IF error_code==already_bound
     *              array $result['details']['bound_contact_info']  [optional] - IF error_code==already_bound
     *          ELSE:
     *              []
     * @throws waException
     */
    protected function processInviteAuth(array $auth_response_data, $invite_token)
    {
        $result = $this->getInviteInfo($invite_token);

        $auth_result = $this->authInvitedUser($auth_response_data, $result['details']['contact']);

        // system logic will process this response - show user message about bounding problem
        $is_backend_auth_forced = $this->cm->isBackendAuthForced();
        if ($is_backend_auth_forced && !$auth_result['status'] && $auth_result['details']['error_code'] == 'already_bound') {
            return $auth_result;
        }

        // delegate control to app token dispatcher - it will deals with success responses and response with already_bound
        if ($auth_result['status'] || $auth_result['details']['error_code'] == 'already_bound') {
            wa($result['details']['token_info']['app_id'], true)->getConfig()->dispatchAppToken([
                'token_info' => $result['details']['token_info'],
                'auth_result' => $auth_result
            ]);
            return null;
        }

        // otherwise system logic will process response
        return $auth_result;
    }

    /**
     * @param array $auth_response_data
     * @param waContact $invite_contact
     * @return array
     * @throws waAuthConfirmEmailException
     * @throws waAuthConfirmPhoneException
     * @throws waAuthException
     * @throws waAuthInvalidCredentialsException
     * @throws waException
     */
    protected function authInvitedUser(array $auth_response_data, $invite_contact)
    {
        $result = $this->auth->bindUserWithWebasystContact($invite_contact, $auth_response_data);
        if (!$result['status']) {
            return $result;
        }

        // search backend contact in DB by webasyst_contact_id
        $cwm = new waContactWaidModel();

        // last backend login datetime
        $cwm->updateById($invite_contact->getId(), [
            'login_datetime' => date('Y-m-d H:i:s')
        ]);

        // update invited contact by webasyst ID contact info
        $profile_info = (new waWebasystIDApi())->loadProfileInfo($auth_response_data);
        if ($profile_info) {
            $this->updateContactByWaProfile($invite_contact, $profile_info);
        }

        wa()->getAuth()->auth(['id' => $invite_contact->getId()]);

        return [
            'status' => true,
            'details' => []
        ];
    }

    /**
     * @param string $invite_token
     * @return array $result
     *      bool    $result['status']
     *      array   $result['details']
     *
     *          IF $result['status'] == FALSE:
     *              string $result['details']['error_code']
     *              string $result['details']['error_message']
     *          ELSE:
     *              waContact $result['details']['contact']
     *              array     $result['details']['token_info']
     *
     * @throws waException
     */
    protected function getInviteInfo($invite_token)
    {
        $atm = new waAppTokensModel();

        $invite_token_info = $atm->getById($invite_token);
        if (!$invite_token_info) {
            return [
                'status' => false,
                'details' => [
                    'error_code' => 'invite_token_invalid',
                    'error_message' => _ws('Invitation token is invalid.'),
                ]
            ];
        }

        if ($invite_token_info['expire_datetime'] && strtotime($invite_token_info['expire_datetime']) < time()) {
            $atm->purge();
            return [
                'status' => false,
                'details' => [
                    'error_code' => 'invite_token_invalid',
                    'error_message' => _ws('Invitation token is invalid.'),
                ]
            ];
        }

        $invite_contact = new waContact($invite_token_info['contact_id']);
        if (!$invite_contact->exists()) {
            return [
                'status' => false,
                'details' => [
                    'error_code' => 'invite_contact_not_exist',
                    'error_message' => _ws("Invited contact doesn't exist."),
                ]
            ];
        }

        return [
            'status' => true,
            'details' => [
                'contact' => $invite_contact,
                'token_info' => $invite_token_info
            ]
        ];
    }


    /**
     * @param array $auth_response_data - here is access token params with expected format:
     *      - string $auth_response_data['access_token'] [required] - access token itself (jwt)
     *      - string $auth_response_data['refresh_token'] [required] - refresh token to refresh access token
     *      - int    $auth_response_data['expires_in'] [optional] - ttl of expiration in seconds
     *      - string $auth_response_data['token_type'] [optional] - "bearer"
     * @return array $result - see waWebasystIDWAAuth::bindWithWebasystContact for format
     *      bool  $result['status']
     *      array $result['details']
     * @throws waException
     */
    protected function processBindAuth(array $auth_response_data)
    {
        $current_backend_user = wa()->getUser();
        $result = $this->auth->bindUserWithWebasystContact($current_backend_user, $auth_response_data);

        // if binding fail, save server data in session for latter use
        if (!$result['status']) {
            $this->getStorage()->set('webasyst_id_server_data', $auth_response_data);
            return $result;
        }

        // delete webasyst ID invite token (exists it or not)
        $app_tokens_model = new waAppTokensModel();
        $app_tokens_model->deleteByField([
            'app_id' => 'webasyst',
            'type' => 'webasyst_id_invite',
            'contact_id' => wa()->getUser()->getId()
        ]);

        $profile_info = !empty($result['details']['webasyst_contact_info']) ? $result['details']['webasyst_contact_info'] : [];

        if (!$profile_info) {
            $profile_info = (new waWebasystIDApi())->loadProfileInfo($auth_response_data);
        }

        if ($profile_info) {
            $this->updateContactByWaProfile($current_backend_user, $profile_info);
        }

        return $result;
    }

    /**
     * If we in process of binding and user has been asked to choose what to do (see OAuth.html)
     * @return bool
     */
    protected function isUnfinishedBindingProcess()
    {
        $post_data = $this->getRequest()->post();
        return $this->getStorage()->get('webasyst_id_server_data') && isset($post_data['renew']);
    }

    /**
     * Finish process of binding contact with webasyst ID contact
     * @throws waException
     */
    protected function finishBindingProcess()
    {
        $post_data = $this->getRequest()->post();

        $data = $this->getStorage()->get('webasyst_id_server_data');
        $this->getStorage()->del('webasyst_id_server_data');

        // renew binding confirmed by user, unbind webasyst contact with existing contact and bind with current
        if ($post_data['renew']) {
            $result = $this->auth->bindWithWebasystContact($data, true);
        } else {
            $result = [
                'status' => false,
                'details' => [
                    'error_code' => 'not_bound'
                ]
            ];
        }

        if ($result['status']) {
            // delete webasyst ID invite token (exists it or not)
            $app_tokens_model = new waAppTokensModel();
            $app_tokens_model->deleteByField([
                'app' => 'webasyst',
                'type' => 'webasyst_id_invite',
                'contact_id' => wa()->getUser()->getId()
            ]);
        }

        $this->displayAuth([
            'type' => 'bind',
            'result' => $result,
        ]);
    }

    /**
     * @param array $result
     *      string $result['type']  - 'backend', 'invite', 'bind', 'access_denied'
     *      array $result['result'] - auth result
     * @return mixed
     * @throws waException
     */
    protected function displayAuth(array $result)
    {
        $invite_token = $this->auth->getInviteAuthToken();

        $type = $result['type'];

        if ($type === 'invite') {
            $auth_result = $result['result'];
            $is_backend_auth_forced = $this->cm->isBackendAuthForced();
            $system_should_process = $is_backend_auth_forced && !$auth_result['status'] && $auth_result['details']['error_code'] == 'already_bound';

            if (!$system_should_process) {
                $this->redirect(wa()->getConfig()->getRootUrl() . 'link.php/' . $invite_token . '/');
            }
        }

        wa('webasyst');
        $this->executeAction(new webasystOAuthAction([
            'provider_id' => waWebasystIDAuthAdapter::PROVIDER_ID,
            'invite_token' => $invite_token,
            'result' => $result
        ]));

        $this->display();
    }

    protected function displayError($error)
    {
        wa('webasyst');
        $this->executeAction(new webasystOAuthAction([
            'provider_id' => waWebasystIDAuthAdapter::PROVIDER_ID,
            'result' => [
                'type' => $this->getAuthType(),
                'is_system_error' => true,
                'error_msg' => $error
            ]
        ]));
        $this->display();
    }

    /**
     * Authorize backend user by params that we get from Webasyst ID service
     *
     * @param array $params - here is access token params with expected format:
     *      - string $params['access_token'] [required] - access token itself (jwt)
     *      - string $params['refresh_token'] [required] - refresh token to refresh access token
     *      - int    $params['expires_in'] [optional] - ttl of expiration in seconds
     *      - string $params['token_type'] [optional] - "bearer"
     *      - ... and maybe some other fields from Webasyst ID server
     * @return array $result
     *      - bool  $result['status'] - ok or not ok?
     *      - array $result['details']
     *          If ok:
     *              int $result['details']['contact_id']
     *          Otherwise:
     *              string $result['details']['error_code']
     *              string $result['details']['error_message']
     *
     * @throws waException
     */
    private function authBackendUser($params)
    {
        $m = new waWebasystIDAccessTokenManager();
        $token_info = $m->extractTokenInfo($params['access_token']);

        // it is webasyst contact id
        $contact_id = $token_info['contact_id'];

        // search backend contact in DB by webasyst_contact_id
        $cwm = new waContactWaidModel();

        // current contact id (of current installation)
        $current_contact_id = $cwm->getBoundWithWebasystContact($contact_id);

        if ($current_contact_id <= 0) {
            $webasyst_contact_info = $this->auth->getUserData($params);
            return [
                'status' => false,
                'details' => [
                    'error_code' => 'not_bound',
                    'error_message' => _w('Not bound yet'),
                    'webasyst_contact_info' => $webasyst_contact_info,
                ]
            ];
        }

        $contact = new waContact($current_contact_id);

        $is_existing_backend_user = $contact->exists() && $contact['is_user'] >= 1;
        if (!$is_existing_backend_user) {
            return [
                'status' => false,
                'details' => [
                    'error_code' => 'access_denied',
                    'error_message' => _w("Access denied")
                ]
            ];
        }

        $contact->updateWebasystTokenParams($params);

        // last backend login datetime
        $cwm->updateById($current_contact_id, [
            'login_datetime' => date('Y-m-d H:i:s')
        ]);

        $backend_auth = new waAuth(['env' => 'backend']);
        $backend_auth->auth([
            'id' => $current_contact_id,
            'remember' => $this->auth->isRememberMe()
        ]);

        $_params = [
            'type'       => 'backend',
            'user-agent' => wa()->getRequest()->getUserAgent()
        ];
        $this->logAction('waid_auth', json_encode($_params));

        /**
         * Event after success auth by webasyst ID
         * User is already authorized in backend to this time
         *
         * Listener could do some inner work what need to do
         * But also listener could use $dispatch input argument and try to suggest url to redirect after auth is fully finish
         *
         *
         * @event 'waid_auth'
         *
         * @param string $type - 'backend', 'invite', 'bind' ...
         * @param array|null $dispatch, if not null has this format
         *      string $dispatch['app'] - so concrete app could be even try suggest redirect url
         *      string $dispatch['module'] -
         *      string $dispatch['action'] -
         *
         * @return array $return[%listener_id%]['dispatch'] [optional]
         *      Listener (application) by input parameter $dispatch try to suggest url to redirect after auth is fully finish
         *      If listener not prefer any url to redirect where and just do own inner work on event, it SHOULD NOT return this type of data
         * @return string $return[%listener_id%]['dispatch']['url']
         *      Where to redirect
         * @return string $return[%listener_id%]['dispatch']['error']['code']
         *      Listener would like to suggest redirect url but something wrong, so here is error code
         * @return string $return[%listener_id%]['dispatch']['error']['message]
         *      Listener would like to redirect but something wrong, so here is error message
         */
        $event_params = [
            'type' => 'backend',
            'dispatch' => $this->auth->getDispatchParams()
        ];
        $event_result = wa('webasyst')->event('waid_auth', $event_params);

        return [
            'status' => true,
            'details' => [
                'contact_id' => $current_contact_id,
                'event_result' => $event_result
            ]
        ];
    }

    protected function updateContactByWaProfile(waContact $contact, array $profile_info = [])
    {
        $update_data = [];

        // add new emails into list of contact's emails
        $has_added_new_email = false;
        $emails = isset($profile_info['email']) && is_array($profile_info['email']) ? $profile_info['email'] : [];
        $contact_emails_list = $contact->get('email'); // list with full records
        $contact_emails = waUtils::getFieldValues($contact_emails_list, 'value'); // only emails
        foreach ($emails as $email) {
            if (!in_array($email['value'], $contact_emails, true)) {
                $has_added_new_email = true;
                $contact_emails_list[] = $email;
            }
        }

        if ($has_added_new_email) {
            $update_data['email'] = $contact_emails_list;
        }

        // add new phones into list of contact's phones
        $has_added_new_phone = false;
        $phones = isset($profile_info['phone']) && is_array($profile_info['phone']) ? $profile_info['phone'] : [];
        $contact_phone_list = $contact->get('phone');   // list with full records
        $contact_phones = waUtils::getFieldValues($contact_phone_list, 'value');    // only phones
        $contact_phones = array_map(['waContactPhoneField', 'cleanPhoneNumber'], $contact_phones);
        foreach ($phones as $phone) {
            if (!in_array(waContactPhoneField::cleanPhoneNumber($phone['value']), $contact_phones, true)) {
                $has_added_new_phone = true;
                $contact_phone_list[] = $phone;
            }
        }

        if ($has_added_new_phone) {
            $update_data['phone'] = $contact_phone_list;
        }


        $name_fields = ['firstname', 'lastname', 'middlename'];
        $is_empty_name = true;
        foreach ($name_fields as $name_field) {
            if (!empty($contact[$name_field])) {
                $is_empty_name = false;
                break;
            }
        }

        // if all three part on names is empty then is allowed to update all three name's parts
        if ($is_empty_name) {
            foreach ($name_fields as $name_field) {
                $update_data[$name_field] = isset($profile_info[$name_field]) ? $profile_info[$name_field] : '';
            }
        }

        if ($update_data) {
            $contact->save($update_data);
        }

        // update userpic if only contact doesn't has it yet
        if (!$contact->get('photo') && !empty($profile_info['userpic_uploaded']) && !empty($profile_info['userpic_original_crop'])) {
            $this->saveUserpic($contact, $profile_info['userpic_original_crop']);
        }
    }

    protected function saveUserpic(waContact $contact, $photo_url)
    {
        // Load person photo and save to contact
        $photo = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($photo_url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9);
            $photo = curl_exec($ch);
            curl_close($ch);
        } else {
            $scheme = parse_url($photo_url, PHP_URL_SCHEME);
            if (ini_get('allow_url_fopen') && in_array($scheme, stream_get_wrappers())) {
                $photo = @file_get_contents($photo_url);
            }
        }
        if ($photo) {
            $photo_url_parts = explode('/', $photo_url);
            $path = wa()->getTempPath('auth_photo/'.$contact->getId().'.'.md5(end($photo_url_parts)), 'webasyst');
            file_put_contents($path, $photo);
            try {
                $contact->setPhoto($path);
            } catch (Exception $exception) {
                
            }

        }
    }
}
