<?php

/**
 * Class waWebasystIDAuth
 *
 * OAuth2 adapter for work with Webasyst ID service
 */
class waWebasystIDAuth extends waOAuth2Adapter
{
    const PROVIDER_ID = 'webasystID';

    /**
     * Is backend auth or primary auth (to bind user to Webasyst ID)
     * @return bool
     */
    public function isBackendAuth()
    {
        return (bool)waRequest::get('backend_auth');
    }

    /**
     * Auth method
     * It can throw waWebasystIDAuthException on some this oauth2 related issues
     * It can standard waException on some unexpected situations
     * And finally on success must return access token params, with which waOAuthController will be work further
     *
     * @return array $params - access token params
     *      - string $params['access_token'] [required] - access token itself (jwt)
     *      - int    $params['expires_in'] [optional] - ttl of expiration in seconds
     *      - string $params['token_type'] [optional] - "bearer"
     *      - ... and maybe some other fields from Webasyst ID server
     *
     * @throws waException|waWebasystIDAuthException
     * If thrown waWebasystIDAuthException it is legit auth error, need to handle it
     */
    public function auth()
    {
        // error from webasyst ID server
        $error = waRequest::get('error');

        // auth code from webasyst ID server
        $code = waRequest::get('code');

        // auth server returns something be callback url
        if ($error || $code) {
            return $this->processAuthResponse();
        }

        // otherwise it is beginning of auth process, adapter didn't ask webasyst ID server yet
        // redirect to provider auth page
        $request_url = $this->getRedirectUri();
        wa()->getResponse()->redirect($request_url);
    }

    /**
     * @param string $refresh_token
     * @param int $contact_id - contact id for which try refresh access token by passed refresh token
     * @param string $client_id
     * @param string $client_secret
     * @return bool - status of refreshing, if TRUE we will has refreshed token params for current contact
     *
     * @throws waDbException
     * @throws waException
     */
    public function refreshAccessToken($refresh_token, $contact_id, $client_id, $client_secret)
    {
        if (!is_string($refresh_token) || !$refresh_token) {
            return false;
        }

        if (!wa_is_int($contact_id) || $contact_id <= 0) {
            return false;
        }

        $contact = new waContact($contact_id);
        if (!$contact->exists()) {
            return false;
        }

        if (!is_string($client_id) || !$client_id) {
            return false;
        }

        if (!is_string($client_secret) || !$client_secret) {
            return false;
        }

        $options = [
            'timeout' => 20,
            'request_format' => waNet::FORMAT_RAW,
            'format' => waNet::FORMAT_JSON
        ];

        $access_token_url = $this->getAccessTokenUrl();

        $net = new waNet($options);

        $exception = null;
        $response = null;
        try {
            $response = $net->query($access_token_url, [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token,
                'client_id' => $client_id,
                'client_secret' => $client_secret
            ], waNet::METHOD_POST);
        } catch (Exception $e) {
            $exception = $e;
        }

        // No response from API
        if (!$response) {
            $this->logError([
                'method' => __METHOD__,
                'error' => "Empty response",
                'debug' => $net->getResponseDebugInfo()

            ]);
            return false;
        }

        if (isset($response['error'])) {
            $error = [
                'method' => __METHOD__,
                'error' => $response['error'],
                'debug' => $net->getResponseDebugInfo()
            ];
            if (isset($response['error_description'])) {
                $error['error_description'] = $response['error_description'];
            }
            $this->logError($error);
            return false;
        }

        if (!isset($response['access_token'])) {
            $this->logError("Empty token was returned from endpoint");
            return false;
        }

        if ($exception) {
            $this->logException($exception);
            $this->logError([
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);
            return false;
        }

        $webasyst_token_params = $response;
        $contact->updateWebasystTokenParams($webasyst_token_params);

        return true;
    }

    /**
     * @return array $params - access token params
     *      - string $params['access_token'] [required] - access token itself (jwt)
     *      - int    $params['expires_in'] [optional] - ttl of expiration in seconds
     *      - string $params['token_type'] [optional] - "bearer"
     *      - ... and maybe some other fields from Webasyst ID server
     * @throws waException
     * @throws waWebasystIDAuthException
     */
    protected function processAuthResponse()
    {
        // check state first
        if (!$this->verifyState()) {
            throw new waWebasystIDAuthException('State is not verified');
        }

        // check error from server
        $error = waRequest::get('error');
        if ($error) {
            $error_description = waRequest::get('error_description');
            if (!$error_description) {
                $error_description = _ws('Unknown error from Webasyst ID server.');
            }

            // if webasyst ID server response 'access_denied' it means that we must react on it special way
            if ($error === 'access_denied') {
                throw new waWebasystIDAccessDeniedAuthException($error_description);
            }

            throw new waWebasystIDAuthException($error_description);
        }

        $result = $this->getAccessToken(waRequest::get('code'));
        if (!$result['status']) {
            throw new waWebasystIDAuthException($result['details']['error_message']);
        }

        return $result['details'];
    }

    /**
     * @inheritDoc
     */
    public function getRedirectUri()
    {
        $auth_url = $this->getAuthCodeUrl();
        if (wa()->getUser()->getId() > 0) {
            $user_info = $this->getUserInfo();
            $user_info_str = waUtils::urlSafeBase64Encode(json_encode($user_info));
            $auth_url .= '&info=' . $user_info_str;
        }
        return $auth_url;
    }

    /**
     * @inheritDoc
     */
    public function getUrl()
    {
        return wa()->getRootUrl(false, true).'oauth.php?provider='.$this->getId();
    }

    /**
     * Url for get auth code
     * @return string
     * @throws waDbException
     * @throws waException
     */
    protected function getAuthCodeUrl()
    {
        $callback_url = $this->getCallbackUrl();
        $state = $this->generateState();

        $m = new waWebasystIDClientManager();
        if (!$m->isConnected()) {
            return '';
        }

        $credentials = $m->getCredentials();

        $params = [
            'client_id' => $credentials['client_id'],
            'redirect_uri' => $callback_url,
            'state' => $state,
            'scope' => 'profile' // 'profile auth' - switch off auth scope
        ];

        if (empty($params['response_type'])) {
            $params['response_type'] = 'code';
        }

        $c = new waWebasystIDConfig();
        return $c->getAuthCenterUrl('auth/code', $params);
    }

    /**
     * Get access token
     * @param string $code
     * @return array $result
     *      - bool  $result['status'] - successful was operation or not?
     *      - array $result['details']
     *          If was failure
     *              $result['details']['error_code']
     *              $result['details']['error_message']
     *          Otherwise
     *              $result['details']['access_token']
     *              $result['details'][...] - other info from server
     * @throws waDbException
     * @throws waException
     */
    public function getAccessToken($code)
    {
        $manager = new waWebasystIDClientManager();
        if (!$manager->isConnected()) {
            return [
                'status' => false,
                'details' => [
                    'error_code' => 'client_not_connected',
                    'error_message' => 'Client is not connected'
                ]
            ];
        }

        $url = $this->getAccessTokenUrl();
        $credentials = $manager->getCredentials();
        $params = [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->getCallbackUrl(),
            'client_id' => $credentials['client_id'],
            'client_secret' => $credentials['client_secret'],
        ];

        $response = $this->post($url, $params);
        if (!$response) {
            return [
                'status' => false,
                'details' => [
                    'error_code' => 'empty_response',
                    'error_message' => "Empty response from endpoint"
                ]
            ];
        }

        $response = json_decode($response, true);
        if (!$response || !is_array($response)) {
            return [
                'status' => false,
                'details' => [
                    'error_code' => 'unexpected_response_format',
                    'error_message' => "Expect correct json as response from endpoint"
                ]
            ];
        }

        if (isset($response['error'])) {
            return [
                'status' => false,
                'details' => [
                    'error_code' => $response['error'],
                    'error_message' => $response['error_description']
                ]
            ];
        }

        if (!isset($response['access_token'])) {
            return [
                'status' => false,
                'details' => [
                    'error_code' => 'empty_token',
                    'error_message' => "Empty token was returned from endpoint"
                ]
            ];
        }

        return [
            'status' => true,
            'details' => $response
        ];
    }

    public function isClientConnect()
    {
        $m = new waWebasystIDClientManager();
        return $m->isConnected();
    }

    public function getId()
    {
        return self::PROVIDER_ID;
    }

    /**
     * Get user data from OAuth provider
     * @param int|array $params - it could be contact ID > 0 or token params (where is token and refresh token packed in one array)
     * @return null|array $info - expected info format:
     *      string $info['name']
     *      string $info['userpic']
     *      array $info['email'] - list of emails (list of assoc arrays)
     *      array $info['phone'] - list of phones (list of assoc arrays)
     * @throws waException
     */
    public function getUserData($params)
    {
        $api = new waWebasystIDApi();
        if (wa_is_int($params)) {
            return $api->getProfileInfo($params);
        } elseif (is_array($params)) {
            return $api->loadProfileInfo($params);
        } else {
            return null;
        }
    }

    /**
     * Save access token params to current user
     * Extract Webasyst ID contact id from access token and bind current user with Webasyst ID contact,
     *      so we can authorize in backend by Webasyst ID
     *
     * Save in `wa_contact_data` by fields:
     *      - 'webasyst_token_params' to store token params itself
     *      - 'webasyst_contact_id' to bind with Webasyst ID contact
     *
     * @param array $params - here is access token params with expected format:
     *      - string $params['access_token'] [required] - access token itself (jwt)
     *      - string $params['refresh_token'] [required] - refresh token to refresh access token
     *      - int    $params['expires_in'] [optional] - ttl of expiration in seconds
     *      - string $params['token_type'] [optional] - "bearer"
     *      - ... and maybe some other fields from Webasyst ID server
     * @param bool $force - force renew binding even if this is conflict with existing binding
     * @return array $result
     *      bool  $result['status'] - bind status

     *      array $result['details']['webasyst_contact_info']               - info about webasyst contact, always returns
     *      array $result['details']['bound_contact_info'] [optional]       - info about bound contact (that is conflicted with current user), returns in case of conflict
     *      array $result['details']['current_user_info'] [optional]        - info about current user, returns in case of conflict
     *
     *      info is array of format:
     *          string $info['name']
     *          string $info['userpic']
     *          array $info['email'] - list of emails (list of assoc arrays)
     *          array $info['phone'] - list of phones (list of assoc arrays)
     *
     * @throws waException
     */
    public function bindWithWebasystContact($params, $force = false)
    {

        // Extract Webasyst contact
        $m = new waWebasystIDAccessTokenManager();
        $token_info = $m->extractTokenInfo($params['access_token']);
        $contact_id = $token_info['contact_id'];

        // This is current user
        $user = wa()->getUser();

        // Found contact that already bound with this Webasyst contact
        $cwm = new waContactWaidModel();
        $bound_contact_id = $cwm->getBoundWithWebasystContact($contact_id);
        $bound_contact = new waContact($bound_contact_id);

        // one contact from current DB must be bound only with one Webasyst contact
        $is_conflict = $bound_contact->exists() && $user->getId() != $bound_contact->getId();

        $webasyst_contact_info = $this->getUserData($params);

        if ($is_conflict && !$force) {

            return [
                'status' => false,
                'details' => [
                    'webasyst_contact_info' => $webasyst_contact_info,
                    'bound_contact_info' => $this->getContactInfo(new waContact($bound_contact_id)),
                    'current_user_info' => $this->getContactInfo($user)
                ]
            ];
        }

        if ($is_conflict && $force) {
            $bound_contact->unbindWaid();
        }

        $user->bindWithWaid($contact_id, $params);

        return [
            'status' => true,
            'details' => [
                'webasyst_contact_info' => $webasyst_contact_info,
            ]
        ];
    }

    /**
     * Generate state and save it in Session
     * @return string
     * @throws waException
     */
    protected function generateState()
    {
        $state = md5(uniqid(rand(), true));
        wa()->getStorage()->set(get_class($this) . '/state', $state);
        return $state;
    }

    /**
     * Verify state
     * @return bool
     * @throws waException
     */
    protected function verifyState()
    {
        // check state first
        $state = waRequest::get('state');

        return !$state || wa()->getStorage()->get(get_class($this) . '/state') === $state;
    }

    /**
     * @return string
     * @throws waDbException
     * @throws waException
     */
    protected function getAccessTokenUrl()
    {
        $m = new waWebasystIDClientManager();
        if (!$m->isConnected()) {
            return '';
        }
        $c = new waWebasystIDConfig();
        return $c->getAuthCenterUrl('auth/token');
    }

    /**
     * Callback url - url of controller that will process response from oauth provider service
     * @param bool $absolute
     * @return string
     * @throws waException
     */
    public function getCallbackUrl($absolute = true)
    {
        $callback_url = wa()->getRootUrl($absolute, true).'oauth.php?provider='.$this->getId();
        if ($this->isBackendAuth()) {
            $callback_url .= '&backend_auth=1';
        }

        $referrer_url = $this->getReferrerUrl();
        if ($referrer_url) {
            $callback_url .= '&referrer_url=' . urlencode($referrer_url);
        }
        return $callback_url;
    }

    protected function getUserInfo()
    {
        $user = wa()->getUser();

        $root_url = wa()->getRootUrl(true);

        $info = [
            'firstname' => $user->get('firstname'),
            'lastname' => $user->get('lastname'),
            'middlename' => $user->get('middlename'),
            'photo_url' => rtrim($root_url, '/') . '/' . ltrim($user->getPhoto2x(48), '/'),
            'locale' => $user->getLocale() ? $user->getLocale() : wa()->getLocale()
        ];

        $email = $user->get('email', 'default');
        if ($email) {
            $info['email'] = $email;
        } else {
            $info['login'] = $user->get('login');
        }

        return $info;
    }

    /**
     * Referer url - is url of page from where we go to oauth.php endpoint
     * It make sense only when oauth.php called in page not in modal window
     * @return mixed
     */
    protected function getReferrerUrl()
    {
        return waRequest::get('referrer_url');
    }

    protected function logException(Exception $e)
    {
        $message = join(PHP_EOL, [$e->getCode(), $e->getMessage(), $e->getTraceAsString()]);
        waLog::log($message, 'webasyst/' . get_class($this) . '.log');
    }

    protected function logError($e)
    {
        if (!is_scalar($e)) {
            $e = var_export($e, true);
        }
        waLog::log($e, 'webasyst/' . get_class($this) . '.log');
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
}
