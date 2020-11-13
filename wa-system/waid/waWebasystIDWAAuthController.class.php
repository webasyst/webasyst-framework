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


        } catch (waWebasystIDAccessDeniedAuthException $e) {
            // if webasyst ID server response 'access_denied' it means that user not allowed authorization, so not showing error (just finish proccess)
            $this->displayAuth(['type' => 'access_denied']);
        } catch (waWebasystIDAuthException $e) {
            $this->displayError($e->getMessage());  // show legitimate error from webasyst ID auth adapter
        } catch (Exception $e) {
            throw $e; // Caught in waSystem->dispatch()
        }
    }

    /**
     * @throws waException
     * @throws waWebasystIDAuthException
     * @throws waWebasystIDAccessDeniedAuthException
     */
    protected function tryAuth()
    {
        $auth_response_data = $this->auth->auth();

        if ($this->auth->isBackendAuth()) {
            $type = 'backend';
            $result = $this->processBackendAuth($auth_response_data);
        } elseif ($invite_token = $this->auth->isInviteAuth()) {
            // this is case of invite user to team
            $type = 'invite';
            $result = $this->processInviteAuth($auth_response_data, $invite_token);
        } else {
            $type = 'bind';
            $result = $this->processBindAuth($auth_response_data);
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
        $this->updateByWaContactInfo($invite_contact, $auth_response_data);

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
     * @return array $result - see waWebasystIDAuth::bindWithWebasystContact for format
     *      bool  $result['status']
     *      array $result['details']
     * @throws waException
     */
    protected function processBindAuth(array $auth_response_data)
    {
        $result = $this->auth->bindWithWebasystContact($auth_response_data);

        // if binding fail, save server data in session for latter use
        if (!$result['status']) {
            $this->getStorage()->set('webasyst_id_server_data', $auth_response_data);
        } else {
            // delete webasyst ID invite token (exists it or not)
            $app_tokens_model = new waAppTokensModel();
            $app_tokens_model->deleteByField([
                'app_id' => 'webasyst',
                'type' => 'webasyst_id_invite',
                'contact_id' => wa()->getUser()->getId()
            ]);
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
        $invite_token = $this->auth->isInviteAuth();

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
                'type' => 'error',
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

    /**
     * @param waContact $contact
     * @return array
     * @throws waException
     */
    private function getContactInfo(waContact $contact)
    {
        $info = [
            'name' => '',
            'userpic' => wa()->getRootUrl().'wa-content/img/userpic96x96.jpg',
            'email' => [],
            'phone' => [],
        ];

        if (!$contact->exists()) {
            $info['name'] = sprintf(_w('deleted contact %s'), $contact->getId());
            return $info;
        }

        $info['name'] = $contact->getName();
        $info['userpic'] = $contact->getPhoto();
        $info['email'] = $contact->get('email');
        $info['phone'] = $contact->get('phone');

        return $info;
    }

    protected function updateByWaContactInfo(waContact $contact, array $params)
    {
        $api = new waWebasystIDApi();
        $profile_info = $api->loadProfileInfo($params);
        if (!$profile_info) {
            return;
        }

        $update_data = [];

        // add new emails into list of contact's emails
        $has_added_new = false;
        $emails = $profile_info['email'];
        $contact_emails_list = $contact->get('email'); // list with full records
        $contact_emails = waUtils::getFieldValues($contact_emails_list, 'value'); // only emails
        foreach ($emails as $email) {
            if (!in_array($email['value'], $contact_emails, true)) {
                $has_added_new = true;
                $contact_emails_list[] = $email;
            }
        }

        if ($has_added_new) {
            $update_data['email'] = $contact_emails_list;
        }

        $name_fields = ['firstname', 'lastname', 'middlename'];
        foreach ($name_fields as $name_field) {
            $update_data[$name_field] = $profile_info[$name_field];
        }

        if ($update_data) {
            $contact->save($update_data);
        }

        if ($profile_info['userpic_uploaded']) {
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
