<?php

/**
 * Class waWebasystIDAuthAdapter
 *
 * Abstract OAuth2 adapter for auth into by Webasyst ID service
 */
abstract class waWebasystIDAuthAdapter extends waOAuth2Adapter
{
    const PROVIDER_ID = 'webasystID';

    const TYPE_WA = 'wa';
    const TYPE_SITE = 'site';

    /**
     * On-demand instance, use getter getConfig
     * @see getConfig
     * @var waWebasystIDConfig
     */
    protected $config;

    /**
     * @return waWebasystIDConfig
     */
    protected function getConfig()
    {
        if (!$this->config) {
            $this->config = new waWebasystIDConfig();
        }
        return $this->config;
    }


    /**
     * Get credentials for authorize: client_id and client_secret
     * @return array $credentials
     *      string $credentials['client_id']
     *      string $credentials['client_secret']
     */
    abstract protected function getCredentials();

    /**
     * @return string
     */
    abstract public function getType();

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
     * @throws waWebasystIDAccessDeniedAuthException
     */
    protected function processAuthResponse()
    {
        // check state first
        if (!$this->verifyState()) {
            throw new waWebasystIDAuthException(_ws('Invalid or expired state'));
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
        return wa()->getRootUrl(false, false).'oauth.php?provider='.$this->getId().'&type='.$this->getType();
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

        $credentials = $this->getCredentials();

        $params = [
            'client_id' => $credentials['client_id'],
            'redirect_uri' => $callback_url,
            'state' => $state,
            'scope' => $this->getAuthScope()
        ];

        if (empty($params['response_type'])) {
            $params['response_type'] = 'code';
        }

        if (waRequest::get('change_user')) {
            $params['change_user'] = 1;
        }

        if (waRequest::get('mode')) {
            $params['mode'] = waRequest::get('mode');
        }

        return $this->getConfig()->getAuthCenterUrl('auth/code', $params);
    }

    protected function getAuthScope()
    {
        return 'profile';  // 'profile auth' - switch off auth scope
    }

    /**
     * Get access token
     * @param string $code
     * @param array $params - extra params to send to access token controller
     * @return array $result
     *      - bool  $result['status'] - successful was operation or not?
     *      - array $result['details']
     *          If was failure
     *              $result['details']['error_code']
     *              $result['details']['error_message']
     *          Otherwise
     *              $result['details']['access_token']
     *              $result['details'][...] - other info from server
     * @throws waException
     */
    public function getAccessToken($code, array $params = [])
    {
        $url = $this->getAccessTokenUrl();
        $credentials = $this->getCredentials();

        $redirect_uri = $this->getCallbackUrl(true);
        
        $params = array_merge($params, [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirect_uri,
            'client_id' => $credentials['client_id'],
            'client_secret' => $credentials['client_secret'],
        ]);

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
     */
    protected function getAccessTokenUrl()
    {
        return $this->getConfig()->getAuthCenterUrl('auth/token');
    }

    /**
     * Callback url - url of controller that will process response from oauth provider service
     * @param bool $absolute
     * @return string
     * @throws waException
     */
    public function getCallbackUrl($absolute = true)
    {
        return wa()->getRootUrl($absolute, true).'oauth.php?provider='.$this->getId().'&type='.$this->getType();
    }

    protected function getUserInfo()
    {
        $user = wa()->getUser();

        $info = [
            'firstname' => $user->get('firstname'),
            'lastname' => $user->get('lastname'),
            'middlename' => $user->get('middlename'),
            'photo_url' => $this->getUserPhoto($user, 48),
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

    protected function getUserPhoto(waContact $user, $size = 48)
    {
        $relative_url = $user->getPhoto2x($size);

        $cdn = wa()->getCdn($relative_url);
        if ($cdn->count() > 0) {
            return (string)$cdn;
        }

        $root_url = wa()->getRootUrl();
        $root_url_len = strlen($root_url);

        if (substr($relative_url, 0, $root_url_len) === $root_url) {
            $relative_url = substr($relative_url, $root_url_len);
        }

        $root_url = wa()->getRootUrl(true);
        return rtrim($root_url, '/') . '/' . ltrim($relative_url, '/');
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
    protected function getContactInfo(waContact $contact)
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
