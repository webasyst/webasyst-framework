<?php

/**
 * Class waWebasystIDClientManager
 *
 * Class for connect client (current installation) to Webasyst ID service
 */
class waWebasystIDClientManager
{
    /**
     * @see getWebasystIDConfig
     * @var waWebasystIDConfig
     */
    protected $config;

    /**
     * @see
     * @var waWebasystIDUrlsProvider
     */
    protected $provider;

    /**
     * Lazy loader property, use getAppSettingsModel
     * @var waAppSettingsModel
     */
    protected $asm;

    /**
     * Is client (current installation) connect to Webasyst ID
     * @return bool
     */
    public function isConnected()
    {
        $credentials = $this->getCredentials();
        return !empty($credentials);
    }

    /**
     * Get client (installation) oauth credentials
     * @return array|null $credentials
     *      - string $credentials['client_id']
     *      - string $credentials['client_secret']
     */
    public function getCredentials()
    {
        $credentials = $this->getAppSettingsModel()->get('webasyst', 'waid_credentials');
        $credentials = json_decode($credentials, true);
        if (is_array($credentials) && isset($credentials['client_id']) && isset($credentials['client_secret'])) {
            return $credentials;
        }
        return null;
    }

    /**
     * Is backend auth forced to webasyst ID oauth2 only
     * Check also is installation is connected to webasyst ID
     * If not connected that method will return false
     * @return bool
     */
    public function isBackendAuthForced()
    {
        try {
            return $this->isConnected() && $this->getWebasystIDConfig()->isBackendAuthForced();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Set (or unset) force auth mode
     * @param bool $on
     */
    public function setBackendAuthForced($on = true)
    {
        $this->getWebasystIDConfig()->setBackendAuthForced($on)->commit();

    }

    /**
     * Connect client to Webasyst ID (other words sign up in Webasyst ID system)
     * If connect is successful save credentials in wa_app_settings
     *
     * @return array $result
     *      bool $result['status']
     *          Successful or fail
     *      array $result['details']
     *          If successful
     *              <empty>, credentials stored in wa_app_settings, not returns it to outer world
     *          If fail
     *              string $result['details']['error_code']
     *              string $result['details']['error_message']
     * @throws waDbException
     * @throws waException
     */
    public function connect()
    {
        $options = [
            'timeout' => 30,
            'format' => waNet::FORMAT_JSON
        ];

        $net = new waNet($options);
        $response = null;
        try {
            $response = $net->query($this->getConnectUrl());
        } catch (Exception $e) {
            if ($e instanceof waNetTimeoutException) {
                $this->getWebasystIDUrlsProvider()->complainAboutAuthEndpoint();
            }
            $this->logException($e);
            $this->logError([
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);
            return $this->packFailResult("fail_" . $e->getCode(), $e->getMessage());
        }

        // No response from API
        if (!$response) {
            return $this->packFailResult("unknown", _ws("Unknown connection error"));
        }

        // Error from API
        if (!isset($response['status']) || $response['status'] === 'fail') {
            $errors = isset($response['errors']) && is_array($response['errors']) ? $response['errors'] : [];
            $error_code = "unknown";
            $error_message = _ws("Unknown connection error");
            if ($errors) {
                $error_code = key($errors);
                $error_message = $errors[$error_code];
            }
            return $this->packFailResult($error_code, $error_message);
        }

        // Expected response from API
        if (isset($response['data']['credentials']['client_id']) && isset($response['data']['credentials']['client_secret'])) {

            $this->saveClientCredentials($response['data']['credentials']);

            $csm = new waContactSettingsModel();
            $csm->clearAllWebasystAnnouncementCloseFacts();

            return $this->packOkResult();
        }

        // Unexpected response
        $this->logError([
            'method' => __METHOD__,
            'debug' => $net->getResponseDebugInfo()
        ]);
        return $this->packFailResult("unexpected", _ws("Unexpected response from API"));
    }

    /**
     * @param array $credentials
     *      string $credentials['client_id']
     *      string $credentials['client_secret']
     * @throws waDbException
     * @throws waException
     */
    public function saveClientCredentials($credentials = [])
    {
        // Expected response from API
        if (isset($credentials['client_id']) && isset($credentials['client_secret'])) {
            $this->getAppSettingsModel()->set('webasyst', 'waid_credentials', json_encode($credentials));
        }
    }

    /**
     * Disconnect client from Webasyst ID
     * If disconnect is successful delete current credentials from wa_app_settings
     *
     * @return array $result
     *      bool $result['status']
     *          Successful or fail
     *      array $result['details']
     *          If successful
     *              <empty>, credentials deleted from wa_app_settings
     *          If fail
     *              string $result['details']['error_code']
     *              string $result['details']['error_message']
     * @throws waDbException
     * @throws waException
     */
    public function disconnect()
    {
        if (!$this->isConnected()) {
            return $this->packFailResult('not_connected', _ws('Client not connected'));
        }

        $options = [
            'timeout' => 30,
            'format' => waNet::FORMAT_JSON,
            'request_format' => waNet::FORMAT_RAW
        ];

        $credentials = $this->getCredentials();

        $net = new waNet($options);

        $exception = null;
        $response = null;
        try {
            $response = $net->query($this->getDisconnectUrl(), [
                'client_id' => $credentials['client_id'],
                'client_secret' => $credentials['client_secret']
            ], waNet::METHOD_POST);
        } catch (Exception $e) {
            $this->logException($e);
            $this->logError([
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);
            return $this->packFailResult("fail_" . $e->getCode(), $e->getMessage());
        }

        // No response from API
        if (!$response) {
            $this->logError([
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);
            return $this->packFailResult("unknown", _ws("Unknown connection error"));
        }

        // Error from API
        if (!isset($response['status']) || $response['status'] === 'fail') {
            $errors = isset($response['errors']) && is_array($response['errors']) ? $response['errors'] : [];
            $error_code = "unknown";
            $error_message = _ws("Unknown connection error");
            if ($errors) {
                $error_code = key($errors);
                $error_message = $errors[$error_code];
            }
            $this->logError([
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);
            return $this->packFailResult($error_code, $error_message);
        }

        $asm = $this->getAppSettingsModel();
        $asm->del('webasyst', 'waid_credentials');
        $asm->del('webasyst', 'waid_system_access_token');

        // Fact about disconnecting save in settings for future
        //  differentiate cases when current installation never was connected yet
        //  and installation was disconnected intentionally
        $asm->set('webasyst', 'waid_disconnected', date('Y-m-d H:i:s'));

        waContact::clearAllWebasystIDAssets();

        $this->setBackendAuthForced(false);

        return $this->packOkResult();
    }


    /**
     * Get system (non user) access token
     * @return string
     */
    public function getSystemAccessToken()
    {
        $system_access_token = $this->getAppSettingsModel()->get('webasyst', 'waid_system_access_token');
        if (!empty($system_access_token) && !(new waWebasystIDAccessTokenManager)->isTokenExpired($system_access_token)) {
            return $system_access_token;
        }

        $url = $this->getWebasystIDUrlsProvider()->getAuthCenterUrl('auth/token');
        $credentials = $this->getCredentials();
        if (!$credentials) {
            return false;
        }

        $params = [
            'grant_type' => 'client_credentials',
            'client_id' => $credentials['client_id'],
            'client_secret' => $credentials['client_secret'],
        ];

        $net_options = [
            'timeout' => 20,
            'format' => waNet::FORMAT_RAW,
            'request_format' => waNet::FORMAT_RAW,
            'expected_http_code' => null,
        ];

        $net = new waNet($net_options);

        $response = null;
        try {
            $response = $net->query($url, $params, waNet::METHOD_POST);
        } catch (Exception $e) {
            if ($e instanceof waNetTimeoutException) {
                $this->getWebasystIDUrlsProvider()->complainAboutAuthEndpoint();
            }
            $this->logException($e);
            $this->logError([
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);
            return false;
        }

        $status = $net->getResponseHeader('http_code');
        if (!$status || $status >= 500) {
            $this->getWebasystIDUrlsProvider()->complainAboutAuthEndpoint();
            return false;
        }

        if (!$response) {
            return false;
        }

        $response = json_decode($response, true);
        if (!$response || !is_array($response)) {
            return false;
        }

        if (isset($response['error'])) {
            return false;
        }

        if (!isset($response['access_token'])) {
            return false;
        }

        $this->getAppSettingsModel()->set('webasyst', 'waid_system_access_token', $response['access_token']);

        return $response['access_token'];
    }

    /**
     * Get url to connect to Webasyst ID service
     * @return string
     * @throws waException
     */
    protected function getConnectUrl()
    {
        return $this->getWebasystIDUrlsProvider()->getAuthCenterUrl('connect', [
            'token' => $this->getToken(),
            'domain' => $this->getDomain(),
            'hash' => $this->getIdentityHash(),
            'url' => wa()->getRootUrl(true)
        ]);
    }

    /**
     * Get url to disconnect from Webasyst ID service
     * @return string
     */
    protected function getDisconnectUrl()
    {
        return $this->getWebasystIDUrlsProvider()->getAuthCenterUrl('disconnect');
    }

    /**
     * Get installation token
     * @return string|null
     * @throws waDbException
     * @throws waException
     */
    protected function getToken()
    {
        $token = null;
        if ($token_data = $this->getAppSettingsModel()->get('installer', 'token_data', false)) {
            $token_data = waUtils::jsonDecode($token_data, true);
            if (!empty($token_data['token'])) {
                $token = $token_data['token'];
            }
        }

        return $token;
    }

    /**
     * Get current domain
     * @return string
     * @throws waException
     */
    protected function getDomain()
    {
        return wa()->getRouting()->getDomain();
    }

    /**
     * @return string
     * @throws waException
     */
    protected function getIdentityHash()
    {
        return $this->getWebasystAppConfig()->getIdentityHash();
    }

    /**
     * @return waWebasystIDConfig
     */
    public function getWebasystIDConfig()
    {
        if (!$this->config) {
            $this->config = new waWebasystIDConfig();
        }
        return $this->config;
    }

    /**
     * @return waWebasystIDUrlsProvider
     */
    protected function getWebasystIDUrlsProvider()
    {
        if (!$this->provider) {
            $this->provider = new waWebasystIDUrlsProvider([
                'config' => $this->getWebasystIDConfig()
            ]);
        }
        return $this->provider;
    }

    /**
     * @return webasystConfig
     * @throws waException
     */
    protected function getWebasystAppConfig()
    {
        return wa('webasyst')->getConfig();
    }

    /**
     * @return waAppSettingsModel
     */
    protected function getAppSettingsModel()
    {
        if (!$this->asm) {
            $this->asm = new waAppSettingsModel();
        }
        return $this->asm;
    }

    protected function packFailResult($error_code, $error_message)
    {
        return [
            'status' => false,
            'details' => [
                'error_code' => $error_code,
                'error_message' => $error_message
            ]
        ];
    }

    protected function packOkResult($details = [])
    {
        return [
            'status' => true,
            'details' => $details
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
