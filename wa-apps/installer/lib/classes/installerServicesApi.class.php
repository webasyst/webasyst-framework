<?php

class installerServicesApi extends waWebasystIDApi
{
    const WS_CONNECT_SERVICE = 'WS_TOKEN';
    const WS_MESSAGE_SERVICE = 'WS_MESSAGE';
    const EMAIL_MESSAGE_SERVICE = 'EMAIL';
    const SMS_SERVICE = 'SMS';
    const CRON_SERVICE = 'CRON';

    private static $is_connected = null;
    private static $is_valid_connection = null;
    private static $system_token = null;
    private static $user_token = null;

    public function __construct(array $options = [])
    {
        $config = new waServicesApiUrlConfig();
        $this->provider = new waServicesUrlsProvider([
            'config' => $config
        ]);
    }

    public function isConnected()
    {
        if (self::$is_connected !== null) return self::$is_connected;

        $cm = new waWebasystIDClientManager();
        self::$is_connected = !!$cm->isConnected();
        if (self::$is_connected) {
            try {
                $this->getToken(false);
            } catch (waWebasystIDApiAuthException $e) {
                self::$is_connected = false;
                self::$is_valid_connection = false;
            }
        }

        return self::$is_connected;
    }

    public function isBrokenConnection()
    {
        if ($this->isConnected()) return false;
        if (self::$is_valid_connection === false) return true;
        return false;
    }

    public function billingCall($api_method, array $params = [], $http_method = waNet::METHOD_GET, array $net_options = [])
    {
        if (!$this->isConnected()) {
            return $this->falseResult('system_error', _ws('Not connected to Webasyst ID.'));
        }
        $token = $this->getSystemToken();
        if (empty($token)) {
            return $this->falseResult('system_error', _ws('Unable to get access token for Webasyst service API'));
        }

        $resp = $this->requestApiMethod($api_method, $token, $params, $http_method, $net_options);
        if ($resp['status'] == 401) {
            $token = $this->getSystemToken(true);
            if (empty($token)) {
                return $this->falseResult('system_error', _ws('Unable to get access token for Webasyst service API'));
            }
            $resp = $this->requestApiMethod($api_method, $token, $params, $http_method, $net_options);
        } else if ($resp['status'] == 404) {
            // If endpoint URL is invalid, try to refresh endpoint config and repeate call
            $this->refreshApiUrlConfig();
            $resp = $this->requestApiMethod($api_method, $token, $params, $http_method, $net_options);
        } else if (in_array($resp['status'], [301, 302]) && !empty(ifset($resp, 'headers', 'Location', null))) {
            // If endpoint URL is moved, try to repeate call to new url
            $url = $resp['headers']['Location'];
            $resp = $this->requestApiUrl($url, $token, $params, $http_method, $net_options);
        }
        return $resp;
    }

    private function falseResult($error = 'system_error', $error_description = 'System error')
    {
        return [
            'status' => false,
            'response' => [
                'error' => $error,
                'error_description' => $error_description,
            ]
        ];
    }

    public function serviceCall(
        $service,
        array $params = [],
        $http_method = waNet::METHOD_GET,
        array $net_options = [],
        $service_sub_path = '',
        $use_system_token = true
    ) {
        if (!$this->isConnected()) {
            throw new waException(_w('Not connected to Webasyst ID.'));
        }
        $url = $this->provider->getServiceUrl($service);
        if (!$url) {
            throw new waException('Unable to get URL for service '.htmlspecialchars($service));
        }
        $url .= $service_sub_path;
        $token = $this->getToken($use_system_token);
        if (empty($token)) {
            throw new waException('Unable to get access token for Webasyst service API');
        }
        $net_options += $this->getDefaultNetOptionsByService($service);
        $resp = $this->requestApiUrl($url, $token, $params, $http_method, $net_options);
        if ($resp['status'] == 401) {
            $token = $this->getToken($use_system_token, true);
            if (empty($token)) {
                throw new waException('Unable to get access token for Webasyst service API');
            }
            $resp = $this->requestApiUrl($url, $token, $params, $http_method, $net_options);
        } else if ($resp['status'] == 404) {
            // If endpoint URL is invalid, try to refresh endpoint config and repeate call
            $this->refreshApiUrlConfig();
            $url = $this->provider->getServiceUrl($service);
            if (!empty($url)) {
                $url .= $service_sub_path;
                $resp = $this->requestApiUrl($url, $token, $params, $http_method, $net_options);
            }
        } else if (in_array($resp['status'], [301, 302]) && !empty(ifset($resp, 'headers', 'Location', null))) {
            // If endpoint URL is moved, try to repeate call to new url
            $url = $resp['headers']['Location'];
            $resp = $this->requestApiUrl($url, $token, $params, $http_method, $net_options);
        }
        return $resp;
    }

    public function getBalance($service = null, $locale = null)
    {
        if (empty($locale)) {
            $locale = wa()->getLocale();
        }
        $params = ['locale' => $locale];
        if (!empty($service)) {
            if (is_array($service)) {
                $params['services'] = $service;
            } else {
                $params['service'] = $service;
            }
        }
        return $this->billingCall('balance', $params);
    }

    public function isBalanceEnough($service, $count, $locale = null)
    {
        if (empty($locale)) {
            $locale = wa()->getLocale();
        }
        return $this->billingCall(
            'balance/check-service',
            [
                'service' => $service,
                'count' => $count,
                'locale' => $locale
            ],
            waNet::METHOD_POST
        );
    }

    public function getBalanceCreditUrl($service = null)
    {
        $params = empty($service) ? [] : [ 'service' => $service ];
        return $this->billingCall('balance/credit-url', $params);
    }

    public function getIpWhiteList()
    {
        return $this->billingCall('ip-white-list');
    }

    public function addIpToWhiteList($ip)
    {
        return $this->billingCall(
            'ip-white-list/add',
            ['ip' => $ip],
            waNet::METHOD_POST,
            [
                'format' => waNet::FORMAT_JSON,
                'request_format' => waNet::FORMAT_RAW,
            ]
        );
    }

    public function deleteIpFromWhiteList($ip)
    {
        return $this->billingCall(
            'ip-white-list/delete',
            ['ip' => $ip],
            waNet::METHOD_POST,
            [
                'format' => waNet::FORMAT_JSON,
                'request_format' => waNet::FORMAT_RAW,
            ]
        );
    }

    public function confirmIpWhiteListChange($code)
    {
        return $this->billingCall(
            'ip-white-list/confirm-operation',
            ['code' => $code],
            waNet::METHOD_POST,
            [
                'format' => waNet::FORMAT_JSON,
                'request_format' => waNet::FORMAT_RAW,
            ]
        );
    }

    public function sendEmail(array $message_data)
    {
        return $this->serviceCall(self::EMAIL_MESSAGE_SERVICE, $message_data, waNet::METHOD_POST, ['request_format' => waNet::FORMAT_JSON]);
    }

    public function sendSms($to, $text, $from = null, $is_repeated = false, $app_id = null, $context = null)
    {
        $app_id = ifempty($app_id, wa()->getApp());
        return $this->serviceCall(self::SMS_SERVICE, [
            'to' => $to,
            'text' => $text,
            'from' => $from,
            'is_repeated' => $is_repeated,
            'app_id' => $app_id,
            'context' => $context,
        ], waNet::METHOD_POST, ['request_format' => waNet::FORMAT_JSON]);
    }

    public function schedule($cron_expression, 
        $action, 
        $app_id = null, 
        $get_params = [],
        $method = waNet::METHOD_GET, 
        $timeout = null, 
        $request_format = null, 
        $request_body_data = null
    ) {
        $app_id = ifempty($app_id, wa()->getApp());
        $query_string = http_build_query($get_params);
        if (!empty($query_string)) {
            $query_string .= '?' . $query_string;
        }
        $data =  [
            'cron_expression' => $cron_expression,
            'url' => wa()->getRootUrl(true) . 'api.php/cron/' . $app_id . '/' . $action . $query_string,
            'app_id' => $app_id,
            'action' => $action,
            'method' => $method,
        ];

        if (!empty($timeout)) {
            $data['timeout'] = $timeout;
        }

        if (!empty($request_format) && !empty($request_body_data)) {
            switch ($request_format) {
                case waNet::FORMAT_JSON:
                    $data['request_content_type'] = 'application/json';
                    $data['request_body'] = json_encode($request_body_data);
                    break;
                default:
                    $data['request_content_type'] = 'application/x-www-form-urlencoded';
                    $data['request_body'] = http_build_query($request_body_data);
                    break;
            }            
        }

        $api_result = $this->serviceCall(self::CRON_SERVICE, $data, waNet::METHOD_POST, ['request_format' => waNet::FORMAT_JSON], 'jobs');
        if (empty($api_result['status']) || $api_result['status'] >= 300) {
            $this->logError($api_result);
            throw new waException(ifset($api_result, 'response', 'message', _w('Webasyst CRON API error.')), $api_result['status']);
        } else {
            return ifset($api_result, 'response', null);
        }
    }

    public function getJobs($app_id = null)
    {
        $path = empty($app_id) ? 'jobs' : 'jobs/app/' . $app_id;
        $api_result = $this->serviceCall(self::CRON_SERVICE, [], waNet::METHOD_GET, [], $path);
        if (empty($api_result['status']) || $api_result['status'] >= 300) {
            $this->logError($api_result);
            throw new waException(_w('Webasyst CRON API error.'));
        } else {
            return ifset($api_result, 'response', []);
        }
    }

    public function getWebsocketUrl($channel_id, $app_id = null)
    {
        $app_id = ifempty($app_id, wa()->getApp());
        $api_result = $this->serviceCall(
            self::WS_CONNECT_SERVICE,
            ['channel' => $app_id.'_'.$channel_id],
            waNet::METHOD_POST,
            ['request_format' => waNet::FORMAT_JSON],
            '', false
        );
        if (empty($api_result['status']) || $api_result['status'] >= 300) {
            $this->logError($api_result);
            throw new waException(_w('Webasyst API access error.'));
        } else {
            return ifset($api_result, 'response', 'ws_url', null);
        }
    }

    public function sendWebsocketMessage(array $message, $channel_id, $app_id = null)
    {
        $app_id = ifempty($app_id, wa()->getApp());
        $api_result = $this->serviceCall(
            self::WS_MESSAGE_SERVICE,
            $message,
            waNet::METHOD_POST,
            ['request_format' => waNet::FORMAT_JSON],
            '/'.$app_id.'_'.$channel_id.'/'
        );
        if (empty($api_result['status']) || $api_result['status'] >= 300) {
            $this->logError($api_result);
            throw new waException(_w('Webasyst API access error.'));
        }
    }

    public function getSystemToken($force_refresh = false)
    {
        if (self::$system_token === null || $force_refresh) {
            self::$system_token = (new waWebasystIDClientManager)->getSystemAccessToken($force_refresh);
        }
        return self::$system_token;
    }

    public function getUserToken($force_refresh = false)
    {
        if (self::$user_token === null || $force_refresh) {

            $lock_name = 'wa_get_user_token_' . wa()->getUser()->getId();
            $locking_model = new waAppSettingsModel();
            try {
                $locking_model->exec("SELECT GET_LOCK(?, -1)", [ $lock_name ]);
            } catch (Exception $e) {
                return null;
            }

            $token_params = wa()->getUser()->getWebasystTokenParams();
            if (empty($token_params)) {
                $locking_model->exec("SELECT RELEASE_LOCK(?)", [ $lock_name ]);
                return null;
            }

            if ($force_refresh) {
                $ok = $this->refreshedTokenParams($token_params, wa()->getUser()->getId());
            } else {
                $ok = $this->refreshTokenWhenExpired($token_params, wa()->getUser()->getId());
            }
            $locking_model->exec("SELECT RELEASE_LOCK(?)", [ $lock_name ]);
            if (!$ok) {
                return null;
            }
            self::$user_token = $token_params['access_token'];
        }
        return self::$user_token;
    }

    private function getToken($use_system_token, $force_refresh = false)
    {
        return $use_system_token ?
            $this->getSystemToken($force_refresh) :
            ($this->getUserToken($force_refresh) ?: $this->getSystemToken($force_refresh));
    }

    protected function refreshApiUrlConfig()
    {
        $config = new waServicesApiUrlConfig();
        $config->keepEndpointsSynchronized(true);
        $this->provider = new waServicesUrlsProvider([
            'config' => $config
        ]);
    }

    protected function getDefaultNetOptionsByService($service): array
    {
        switch ($service) {
            case 'AI':
                return ['timeout' => 30];
        }
        return [];
    }
}
