<?php

/**
 * Class waWebasystIDApi
 *
 * Class for work with webasyst ID API, not implemented yet
 *
 */
class waWebasystIDApi
{
    /**
     * @var waWebasystIDUrlsProvider
     */
    protected $provider;

    /**
     * waWebasystIDApi constructor.
     * @param array $options
     *      waWebasystIDConfig $options['config'] [optional]
     *          Default is waWebasystIDConfig
     *
     */
    public function __construct(array $options = [])
    {
        if (isset($options['provider']) && $options['config'] instanceof waWebasystIDUrlsProvider) {
            $this->provider = $options['provider'];
        } else {
            if (isset($options['config']) && $options['config'] instanceof waWebasystIDConfig) {
                $config = $options['config'];
            } else {
                $config = new waWebasystIDConfig();
            }
            $this->provider = new waWebasystIDUrlsProvider([
                'config' => $config
            ]);
        }
    }

    /**
     * Get webasyst ID profile info by current contact id of current installation
     * @param int $contact_id
     * @return null|array $info - expected info format:
     *      string $info['name']
     *      string $info['userpic']
     *      array $info['email'] - list of emails (list of assoc arrays)
     *      array $info['phone'] - list of phones (list of assoc arrays)
     *
     * @throws waException
     */
    public function getProfileInfo($contact_id)
    {
        $contact = $this->getExistingContact($contact_id);
        if (!$contact) {
            $this->logError([
                'method' => __METHOD__,
                'error' => sprintf("Contact %s not exist", $contact_id)
            ]);
            return null;
        }

        $token_params = $contact->getWebasystTokenParams();
        if (!$token_params) {
            $this->logError([
                'method' => __METHOD__,
                'error' => sprintf("Contact %s is not authorize (not bound with webasyst ID contact)", $contact_id)
            ]);
            return [
                'status' => false,
                'details' => [
                    'error' => _w('Not connected with webasyst ID contact')
                ]
            ];
        }

        // try get profile info
        $result = $this->tryGetProfileInfo($contact);

        // can't refresh token
        if ($result === null) {
            return null;
        }

        if ($result['status'] == 200) {
            return $result['response'];
        }

        // has invalid access token, try refresh token and execute api method one more time
        if ($result['status'] == 401 && $result['response']['error'] === 'invalid_token') {

            $result = $this->tryGetProfileInfo($contact, true);

            // can't refresh token
            if ($result === null) {
                return null;
            }

            if ($result['status'] == 200) {
                return $result['response'];
            }
        }

        // give up - can't profile info
        return null;
    }

    /**
     * @param waContact $contact
     * @param bool $force_refresh - obligatory refresh token before api call
     * @return array|null - NULL means can't refresh token
     * @throws waDbException
     * @throws waException
     */
    protected function tryGetProfileInfo(waContact $contact, $force_refresh = false)
    {
        $token_params = $contact->getWebasystTokenParams();

        if ($force_refresh) {
            $ok = $this->refreshedTokenParams($token_params, $contact->getId());
        } else {
            $ok = $this->refreshTokenWhenExpired($token_params, $contact->getId());
        }

        if (!$ok) {
            return null;
        }

        return $this->requestApiMethod('profile', $token_params['access_token']);
    }

    public function getProfileUpdated(waContact $contact, $code)
    {
        $token_params = $contact->getWebasystTokenParams();
        $ok = $this->refreshTokenWhenExpired($token_params, $contact->getId());
        if (!$ok) {
            return null;
        }

        $response = $this->requestApiMethod('profile-updated', $token_params['access_token'], [ 'code' => $code ]);
        if ($response['status'] == 200) {
            return $response['response'];
        }
        return null;
    }

    /**
     * Notify WAID server that backend user invitation has been created.
     * Used by Team app.
     *
     * @param $contact_id
     * @param $user_token
     * @param $waid_token
     * @return mixed|null
     * @throws waDbException
     * @throws waException
     * @throws waNetTimeoutException
     */
    public function createClientInvite($contact_id, $user_token, $waid_token)
    {
        $user = wa()->getUser();
        $token_params = $user->getWebasystTokenParams();
        if (!$token_params || !$this->refreshTokenWhenExpired($token_params, $user->getId())) {
            return false;
        }
        $contact = $this->getExistingContact($contact_id);
        $data = [
            'email'      => $contact->get('email', 'default'),
            'phone'      => $contact->get('phone', 'default'),
            'user_token' => $user_token,
            'waid_token' => $waid_token
        ];
        $response = $this->requestApiMethod(
            'client-invite',
            $token_params['access_token'],
            $data,
            waNet::METHOD_POST
        );
        if ($response['status'] == 201) {
            return true;
        }

        return null;
    }

    /**
     * Notify WAID server that backend user invitation has been accepted locally.
     * Returns related contact ID from WAID server or null
     * Used by Team app.
     */
    public function clientInviteAccept($waid_token)
    {
        $cm = new waWebasystIDClientManager();
        if (!$cm->isConnected()) {
            return null;
        }
        $auth_token = $cm->getSystemAccessToken();
        $response = $this->requestApiMethod('client-invite/accept', $auth_token, [
            'token' => $waid_token,
        ], 'POST');
        if (ifset($response, 'status', null) != 201) {
            $this->logError([
                'Abnormal response from API call client-invite/accept',
                'method' => __METHOD__,
                'token' => $waid_token,
                'response' => $response,
            ]);
            return null;
        }
        return ifset($response['response']['webasyst_contact_id'], null);
    }

    /**
     * Get auth url for authorization into customer center (aka reverse authorization)
     * @param int $contact_id - what contact authorize into customer center
     * @return array $result
     *      bool $result['status'] - successful api call or not
     *      array $result['details']
     *          IF $result['status'] === TRUE:
     *              $result['details']['auth_url']
     *          ELSE:
     *              string $result['details']['error']
     * @throws waDbException
     * @throws waException
     */
    public function getAuthUrl($contact_id)
    {
        $contact = $this->getExistingContact($contact_id);
        if (!$contact) {
            $this->logError([
                'method' => __METHOD__,
                'error' => sprintf("Contact %s not exist", $contact_id)
            ]);
            return [
                'status' => false,
                'details' => [
                    'error' => ''
                ]
            ];
        }

        $token_params = $contact->getWebasystTokenParams();
        if (!$token_params) {
            $this->logError([
                'method' => __METHOD__,
                'error' => sprintf("Contact %s is not authorize (not bound with webasyst ID contact)", $contact_id)
            ]);
            return [
                'status' => false,
                'details' => [
                    'error' => _w('Not connected with webasyst ID contact')
                ]
            ];
        }

        $result = $this->tryGetAuthUrl($contact);

        // can't refresh token
        if ($result === null) {
            return [
                'status' => false,
                'details' => [
                    'error' => ''
                ]
            ];
        }

        if ($result['status'] == 200) {
            return [
                'status' => true,
                'details' => [
                    'auth_url' => $result['response']['auth_url']
                ]
            ];
        }

        if ($result['status'] == 401 && $result['response']['error'] === 'invalid_token') {
            $result = $this->tryGetAuthUrl($contact, true);

            // can't refresh token
            if ($result === null) {
                return [
                    'status' => false,
                    'details' => [
                        'error' => ''
                    ]
                ];
            }

            if ($result['status'] == 200) {
                return [
                    'status' => true,
                    'details' => [
                        'auth_url' => $result['response']['auth_url']
                    ]
                ];
            }

        }

        return [
            'status' => false,
            'details' => [
                'error' => $result['response']['error']
            ]
        ];
    }

    /**
     * @param waContact $contact
     * @param bool $force_refresh
     * @return array|null - NULL means can't refresh token
     * @throws waDbException
     * @throws waException
     */
    protected function tryGetAuthUrl(waContact $contact, $force_refresh = false)
    {
        $token_params = $contact->getWebasystTokenParams();
        if ($force_refresh) {
            $ok = $this->refreshedTokenParams($token_params, $contact->getId());
        } else {
            $ok = $this->refreshTokenWhenExpired($token_params, $contact->getId());
        }

        if (!$ok) {
            return null;
        }

        return $this->requestAuthUrl($token_params);
    }

    /**
     * Delete user db record from webasyst ID server
     * @param int $contact_id
     * @return bool
     * @throws waDbException
     * @throws waException
     */
    public function deleteUser($contact_id)
    {
        $contact = $this->getExistingContact($contact_id);
        if (!$contact) {
            return false;
        }

        $token_params = $contact->getWebasystTokenParams();
        if (!$token_params) {
            return false;
        }

        $token_params = $contact->getWebasystTokenParams();
        $ok = $this->refreshTokenWhenExpired($token_params, $contact->getId());
        if (!$ok) {
            return false;
        }

        $result = $this->requestToDeleteUser($token_params);
        return $result && !empty($result['deleted']);
    }

    protected function getExistingContact($contact_id)
    {
        if (!wa_is_int($contact_id) || $contact_id <= 0) {
            return null;
        }

        $contact = new waContact($contact_id);
        if (!$contact->exists()) {
            return null;
        }
        return $contact;
    }

    protected function isExpired($token_params)
    {
        $tm = new waWebasystIDAccessTokenManager();
        return $tm->isTokenExpired($token_params['access_token']);
    }

    /**
     * Refresh access token by refresh token
     * @param string $token
     * @param int $contact_id
     * @return bool
     * @throws waDbException
     * @throws waException
     */
    protected function refreshToken($token, $contact_id)
    {
        $m = new waWebasystIDClientManager();
        $credentials = $m->getCredentials();
        if (!$credentials) {
            $this->logError([
                'method' => __METHOD__,
                'error' => "No client connection credentials"
            ]);
            return false;
        }
        $auth = new waWebasystIDWAAuth();
        return $auth->refreshAccessToken($token, $contact_id, $credentials['client_id'], $credentials['client_secret']);
    }

    /**
     * Refresh token only when access token is expired, otherwise just return TRUE
     * @param array &$token_params - will be updated when then token params would be refreshed
     * @param int $contact_id
     * @return bool
     * @throws waDbException
     * @throws waException
     */
    protected function refreshTokenWhenExpired(&$token_params, $contact_id)
    {
        if ($this->isExpired($token_params)) {
            return $this->refreshedTokenParams($token_params, $contact_id);
        }
        return true;
    }

    /**
     * Refresh token and update token_params input argument
     * @param &$token_params - side effect is here
     * @param $contact_id
     * @return bool
     * @throws waDbException
     * @throws waException
     */
    protected function refreshedTokenParams(&$token_params, $contact_id)
    {
        $ok = $this->refreshToken($token_params['refresh_token'], $contact_id);
        if ($ok) {
            $contact = new waContact($contact_id);
            $token_params = $contact->getWebasystTokenParams();
        }
        return $ok;
    }

    /**
     * @param array $token_params
     * @return array $result - see requestApiMethod result format
     * @throws waException
     */
    protected function requestAuthUrl($token_params)
    {
        return $this->requestApiMethod('auth', $token_params['access_token']);
    }

    /**
     * @param array $token_params
     * @return array|null $response
     *      string $response['deleted']
     * @throws waException
     */
    protected function requestToDeleteUser($token_params)
    {
        $response = $this->requestApiMethod('delete', $token_params['access_token'], [], waNet::METHOD_DELETE);
        if ($response['status'] == 200) {
            return $response['response'];
        }
        return null;
    }

    /**
     * @param array $token_params
     * @return null|array $info - expected info format:
     *      string $info['name']
     *      string $info['userpic']
     *      array $info['email'] - list of emails (list of assoc arrays)
     *      array $info['phone'] - list of phones (list of assoc arrays)
     * @throws waException
     */
    public function loadProfileInfo($token_params)
    {
        $response = $this->requestApiMethod('profile', $token_params['access_token']);
        if ($response['status'] == 200) {
            return $response['response'];
        }
        return null;
    }

    /**
     * @param string $api_method
     * @param string $access_token
     * @param array $params
     * @param string $http_method - waNet::METHOD_
     * @param array $net_options
     * @return array $result
     *      int|null    $result['status']   - http status or if failed before net query NULL
     *      array       $result['response'] - response data
     *                      IF $result['status'] == 200:
     *                          array $result['response'] - as it has been returned by server
     *                      ELSE:
     *                          string $result['response']['error'] - error from server
     * @throws waNetTimeoutException|waException
     */
    protected function requestApiMethod($api_method, $access_token, array $params = [], $http_method = waNet::METHOD_GET, array $net_options = [])
    {
        $url = $this->provider->getApiUrl($api_method);
        return $this->requestApiUrl($url, $access_token, $params, $http_method, $net_options);
    }

    /**
     * @param string $api_method
     * @param string $access_token
     * @param array $params
     * @param string $http_method - waNet::METHOD_
     * @param array $net_options
     * @return array $result
     *      int|null    $result['status']   - http status or if failed before net query NULL
     *      array       $result['response'] - response data
     *                      IF $result['status'] == 200:
     *                          array $result['response'] - as it has been returned by server
     *                      ELSE:
     *                          string $result['response']['error'] - error from server
     * @throws waNetTimeoutException|waException
     */
    protected function requestApiUrl($url, $access_token, array $params = [], $http_method = waNet::METHOD_GET, array $net_options = [])
    {
        $default_net_options = [
            'timeout' => 20,
            'format' => waNet::FORMAT_JSON,
            'request_format' => waNet::FORMAT_RAW,
            'expected_http_code' => null
        ];

        $net_options = array_merge($default_net_options, $net_options);

        $headers = [
            'Authorization' => "Bearer {$access_token}"
        ];

        $net = new waNet($net_options, $headers);

        $exception = null;
        $response = null;
        try {
            $response = $net->query($url, $params, $http_method);
        } catch (Exception $e) {
            if ($e instanceof waNetTimeoutException) {
                $this->provider->complainAboutApiEndpoint();
            }
            $exception = $e;
        }

        $status = $net->getResponseHeader('http_code');
        $response_headers = $net->getResponseHeader();
        $body = trim($net->getResponse(true));
        if ($status == 204 && strlen($body) == 0) {
            return [
                'status' => 204,
                'headers' => $response_headers,
                'response' => []
            ];
        }

        if (!$status || $status >= 500) {
            $this->provider->complainAboutApiEndpoint();
        }

        if ($exception) {
            $this->logException($exception);
            $this->logError([
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);

            return [
                'status' => $status,
                'headers' => $response_headers,
                'response' => [
                    'error' => 'system_error',
                    'error_description' => 'System error (see ' . get_class($this) . '.log for details)'
                ]
            ];
        }

        if ($response) {
            return [
                'status' => $status,
                'headers' => $response_headers,
                'response' => $response
            ];
        }

        $response_auth_header = $net->getResponseHeader('WWW-Authenticate');
        if ($response_auth_header) {
            $response_auth_header = trim($response_auth_header);
            if (substr($response_auth_header, 0, 6) === 'Bearer') {
                $resp = trim(substr($response_auth_header, 6));
                $parts = preg_split('/\s+/', $resp);
                $resp_error = null;
                foreach ($parts as $part) {
                    if (substr($part, 0, 6) === 'error=') {
                        $resp_error = substr($part, 6);
                        $resp_error = trim($resp_error, '"');
                        break;
                    }
                }
                if ($resp_error) {
                    return [
                        'status' => $status,
                        'headers' => $response_headers,
                        'response' => [
                            'error' => $resp_error
                        ]
                    ];
                }
            }
        }

        if (!$exception) {
            $this->logError([
                'method' => __METHOD__,
                'response_error' => 'unknown',
                'status' => $status,
                'debug' => $net->getResponseDebugInfo()
            ]);
        }

        return [
            'status' => $status,
            'headers' => $response_headers,
            'response' => $response
        ];
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
}
