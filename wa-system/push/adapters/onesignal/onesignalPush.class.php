<?php

class onesignalPush extends waPushAdapter
{
    const PROVIDER_NAME = 'OneSignal';
    const API_URL_V1 = 'https://onesignal.com/api/v1/';
    const API_URL_V2 = 'https://api.onesignal.com/';
    const API_TOKEN = 'api_token';
    const ORG_ID = 'org_id';
    const ORG_KEY = 'org_key';
    const APP_KEY = 'app_key';

    const EMPTY_API_KEY_TEMPLATE = '--EMPTY-API-KEY--';

    protected $net_api_token;
    protected $force_expose_exceptions = false;
    protected $lock_fd = null;

    /**
     * @var waNet
     */
    protected $net;

    //
    // Init methods
    //

    public function getName()
    {
        return self::PROVIDER_NAME;
    }

    public function isEnabled()
    {
        try {
            $app = $this->getAppByDomain();
            if ($this->isLegacyV1()) {
                return !empty($app);
            }

            $org_key = $this->getSettings(self::ORG_KEY);
            $org_id = $this->getSettings(self::ORG_ID);
            $app_key = empty($app) ? null : $this->getSettings(self::APP_KEY . '_' . $app['id']);
            return !empty($org_key) && !empty($org_id) && !empty($app_key);
        } catch (waException $e) {
            return false;
        }
    }

    public function getInitJs()
    {
        $is_enabled = $this->isEnabled();
        if (!$is_enabled) {
            return null;
        }

        $actions_url = $this->getActionUrl();
        $webasyst_app_url = wa()->getConfig()->getBackendUrl(true).'webasyst/';

        $app = $this->getAppByDomain();
        //$app = $this->getApp();

        $options = [
            'api_app_id'         => $app['id'],
            'api_subdomain_name' => $app['chrome_web_sub_domain'],
            'external_id'        => wa()->getUser()->getId(),
            'ui'                 => wa()->whichUI(),
        ];

        $view = wa('webasyst')->getView();
        $view->assign([
            'options'          => $options,
            'actions_url'      => $actions_url,
            'webasyst_app_url' => $webasyst_app_url,
            //'api_token'        => $this->getSettings(self::ORG_KEY),
        ]);
        $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/onesignal/init.js';
        return $view->fetch($template);
    }
/*
    public function getInitData()
    {
        $is_enabled = $this->isEnabled();
        if (!$is_enabled) {
            return null;
        }

        $app = $this->getAppByDomain();
        return [
            [ 'key' => 'appId', 'value' => $app['id'] ],
            [ 'key' => 'subdomainName', 'value' => $app['chrome_web_sub_domain'] ],
            [ 'key' => 'path', 'value' => $this->getActionUrl() ],
        ];
    }
*/

    public function getSettingsHtml($params = array())
    {
        self::clearCache();
        return parent::getSettingsHtml($params);
    }

    protected function initControls()
    {
        $api_token = $this->getSettings(self::API_TOKEN);
        $org_id = $this->getSettings(self::ORG_ID);
        $org_key = $this->getSettings(self::ORG_KEY);
        $domains = [];
        $current_domain = wa()->getConfig()->getHostUrl();

        $is_api_key_ok = $this->isLegacyV1() || (!empty($org_key) && !empty($org_id));
        if ($is_api_key_ok) {
            try {
                // List of connected domains
                $domains = $this->getConnectedDomains();

                if (strpos($current_domain, 'https://') === 0) {
                    // Add current domain as unconnected if not in the list
                    $domains += [
                        $current_domain => [
                            'name'      => $current_domain,
                            'id'        => null,
                            'connected' => false,
                        ],
                    ];

                    // Current domain always first, no matter connected or not
                    $domains = [
                        $current_domain => $domains[$current_domain],
                    ] + $domains;
                }

            } catch (Exception $e) {
                $api_token_error = $e->getMessage();
                $is_api_key_ok = false;
            }
        }

        $this->controls = [];
        if (!empty($api_token)) {
            $this->controls = [
                self::API_TOKEN => [
                    'title'        => _ws('User Auth Key'),
                    'control_type' => waHtmlControl::INPUT,
                    'description'  => '<p class="hint state-caution-hint">'._ws('Authentication by User Auth Key is deprecated. You need to set up Organization ID and Organization API Auth Key.').'</p>',
                    'translate' => false,
                ]
            ];
        }

        $this->controls[self::ORG_ID] = [
            'title'        => _ws('Organization ID'),
            'control_type' => waHtmlControl::INPUT,
            'description'  => '<p class="hint">'.sprintf_wp('How to find the <em>Organization ID</em>: Navigate to the Organization from %s. The Organization ID is the UUID found in the URL after <em>/organizations/</em>.',
                sprintf_wp('<%s>organization list page<%s><%s>', 'a href="https://dashboard.onesignal.com/organizations/" target="_blank"', 'i class="icon16 new-window"></i', '/a')).
                '</p>',
            'translate' => false,
        ];

        $view = wa('webasyst')->getView();
        $view->assign([
            'domains'         => $domains,
            'current_domain'  => $current_domain,
            'is_api_key_ok'   => $is_api_key_ok,
            'api_token_error' => ifset($api_token_error),
        ]);
        $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/onesignal/api_key_description.html';
        $description = $view->fetch($template);
        $this->controls[self::ORG_KEY] = [
            'title'        => _ws('Organization API Auth Key'),
            'control_type' => waHtmlControl::INPUT,
            'description'  => $description,
            'translate' => false,
        ];

        foreach ($domains as $domain => $domain_data) {
            if (!empty($domain_data['id']) && !empty($domain)) {
                $this->controls[self::APP_KEY . '_' . $domain_data['id']] = [
                    'title'        => $domain,
                    'control_type' => waHtmlControl::INPUT,
                    'description'  => sprintf_wp('Create an API key on the %s page.',
                        '<a href="https://dashboard.onesignal.com/apps/'.$domain_data['id'].'/settings/keys_and_ids" target="_blank">App Keys &amp; IDs<i class="icon16 new-window"></i></a>'),
                    'translate' => false,
                ];
            }
        }
    }

    public function validateSettings($settings = [])
    {
        if ($this->isLegacyV1()) {
            // do not validate legacy settings
            return null;
        }

        $org_id = ifset($settings[self::ORG_ID]);
        $org_key = ifset($settings[self::ORG_KEY]);
        if (empty($org_key) || empty($org_id)) {
            return _ws('OneSignal Organization ID and API Auth Key are required.');
        }

        list($apps, $errors) = $this->testApiKey('apps', $org_key, _ws('Invalid Organization API Auth Key.'));
        if (!empty($errors)) {
            return $errors;
        }

        $errors = [];
        foreach ($apps as $app) {
            $domain = $app['chrome_web_origin'];
            if (!empty($domain)) {
                $api_key = ifset($settings[self::APP_KEY . '_' . $app['id']]);
                if (!empty($api_key)) {
                    list($res, $err) = $this->testApiKey('templates?app_id=' . $app['id'], $api_key, sprintf(_ws('Invalid App API Key for %s.'), $domain));
                    if (!empty($err)) {
                        $errors[] = $err;
                    }
                }
            }
        }

        if (!empty($errors)) {
            return join("\n", $errors);
        }

        return null;
    }

    protected function testApiKey($api_method, $api_key, $access_denied_error_str)
    {
        $res = null;
        $errors = null;
        $this->setApiKey($api_key);
        $this->force_expose_exceptions = true;
        try {
            $res = (array)$this->request($api_method, [], waNet::METHOD_GET);
        } catch (waException $e) {
            if ($e->getCode() == 403) {
                $errors = $access_denied_error_str;
            } else {
                $error_result = @json_decode($e->getMessage(), 1);
                if (empty($error_result['errors'])) {
                    $errors = $e->getMessage();
                } else {
                    $errors = join("\n", (array)$error_result['errors']);
                }
            }
        }
        $this->clearApiKey();
        $this->force_expose_exceptions = false;
        return [$res, $errors];
    }

    protected function normalizeSubscriberData($data)
    {
        if (!is_array($data) ||
            !ifset($data['onesignal_app_id']) ||
            !ifset($data['onesignal_player_id'])
        ) {
            throw new waException(_ws('Invalid subscriber data'));
        }
        return [
            'api_app_id' => $data['onesignal_app_id'],
            'api_user_id' => $data['onesignal_player_id'],
        ];
    }

    //
    // Senders
    //

    public function send($id, $data)
    {
        $request_data = $this->prepareRequestData($data);
        $subscriber_list = $this->getSubscriberListByField('id', $id);
        $request_data['subscriber_list'] = $subscriber_list;
        $result = [];
        foreach ($subscriber_list as $app_id => $user_ids) {
            $user_ids = array_filter($user_ids);
            if ($user_ids) {
                $result[] = $this->createPush($request_data, $app_id, $user_ids);
            }
        }

        $success_result = array_filter($result, function($res) {
            return $res['status'];
        });

        return [ 'status' => !empty($success_result) ];
    }

    public function sendByContact($contact_id, $data)
    {
        $request_data = $this->prepareRequestData($data);
        $subscriber_list = $this->getSubscriberListByField('contact_id', $contact_id);
        $result = [];
        foreach ($subscriber_list as $app_id => $user_ids) {
            $user_ids = array_filter($user_ids);
            if (!empty($user_ids)) {
                $result[] = $this->createPush($request_data, $app_id, $user_ids);
            }
        }

        return $result;
    }

    protected function createPush($data, $app_id, $user_ids) {
        $api_key = $this->getSettings(self::APP_KEY . '_' . $app_id);
        if (empty($api_key) && !$this->isLegacyV1()) {
            $api_key = self::EMPTY_API_KEY_TEMPLATE;
        }
        $this->setApiKey($api_key);
        $data['app_id'] = $app_id;
        $data['include_player_ids'] = $user_ids;
        $res = $this->request('notifications', $data, waNet::METHOD_POST);
        $this->clearApiKey();

        return [
            'status' => !empty($res['id']),
            'has_errors' => !empty($res['errors']),
            'invalid_player_ids' => ifset($res['errors']['invalid_player_ids']),
            'response' => $res,
        ];
    }

    protected function prepareRequestData(array $data)
    {
        $request_data = array(
            'headings' => array(
                'en' => (string)ifempty($data, 'title', null)
            ),
            'contents' => array(
                'en' => (string)ifempty($data, 'message', null)
            ),
            'url'      => (string)ifempty($data, 'url', null),
            'data'     => (array)ifempty($data, 'data', array(1 => 1)),
        );

        if (!empty($data['image_url'])) {
            $request_data['icon'] = (string)$data['image_url'];
        }

        return $request_data;
    }

    /**
     * @param string $field — one field from `wa_push_subscriber`
     * @param mixed $value
     * @return array
     * @throws waException
     */
    protected function getSubscriberListByField($field, $value)
    {
        $fields = array(
            'provider_id' => $this->getId(),
            $field        => $value
        );
        $rows = $this->getPushSubscribersModel()->getByField($fields, 'id');

        $scope_app = wa()->getApp();
        $subscriber_list = array();
        foreach ($rows as $row) {
            $scope = $row['scope'];
            if (!empty($row['subscriber_data']) && (empty($scope) || in_array($scope_app, explode(',', $scope)))) {
                $subscriber_data = json_decode($row['subscriber_data'], true);
                if (!empty($subscriber_data)) {
                    $subscriber_list[] = $subscriber_data;
                }
            }
        }

        $apps = array();
        foreach ($subscriber_list as $subscriber) {
            if (!empty($subscriber['api_app_id']) && !empty($subscriber['api_user_id'])) {
                $apps[$subscriber['api_app_id']][] = $subscriber['api_user_id'];
            }
        }

        return $apps;
    }

    //
    // Dispatch actions
    //

    public function dispatch($action)
    {
        switch ($action) {
            // push.php/onesignal/OneSignalSDKWorker.djs
            case 'OneSignalSDKWorker.djs':
            case 'OneSignalSDKUpdaterWorker.djs':
                wa()->getResponse()->addHeader('Service-Worker-Allowed', '/');
                wa()->getResponse()->addHeader('Content-type', 'application/javascript');
                wa()->getResponse()->sendHeaders();
                echo $this->getStaticContent($action);
                break;

            // push.php/onesignal/manifest.json
            case 'manifest.json':
                wa()->getResponse()->addHeader('Content-type', 'application/json');
                wa()->getResponse()->sendHeaders();
                echo $this->getStaticContent($action);
                break;
        }
    }

    protected function getStaticContent($action)
    {
        switch ($action) {
            case 'OneSignalSDKWorker.js':
            case 'OneSignalSDKWorker.djs':
            case 'OneSignalSDKUpdaterWorker.js':
            case 'OneSignalSDKUpdaterWorker.djs':
                return 'importScripts("https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.sw.js");';
            case 'manifest.json':
                return json_encode(array(
                    "name"                  => wa()->accountName()." WebPush",
                    "short_name"            => wa()->accountName(),
                    "start_url"             => wa()->getAppUrl('webasyst'),
                    "gcm_sender_id"         => "482941778795",
                    "gcm_sender_id_comment" => "Do not change the GCM Sender ID",
                    "display"               => "browser",
                ));
        }
    }

    //
    // Setup methods
    //

    public function setup()
    {
        try {
            $domains = $this->getConnectedDomains();
            $current_domain = wa()->getConfig()->getHostUrl();
            if (empty($domains[$current_domain])) {
                $this->addApp($current_domain);
            }

            return array('reload' => true);
        } catch (waException $e) {
            wa_dump($e);
            return array('errors' => $e->getMessage());
        }
    }

    public function addApp($domain)
    {
        $org_id = $this->getSettings(self::ORG_ID);
        $org_key = $this->getSettings(self::ORG_KEY);
        if (!$this->isLegacyV1() && empty($org_key) && empty($org_id)) {
            return [];
        }
        if (strpos($domain, 'http://') === 0) {
            // Support httpS only
            return [];
        }
        if (!$this->isLegacyV1() && (empty($org_key) || empty($org_id))) {
            throw new waException(_ws('OneSignal Organization ID and API Auth Key are required.'));
        }

        // Fetch data from API
        try {
            $sub_domain = str_replace(array('http://', 'https://'), '', $domain);
            $sub_domain = str_replace('.', '-', $sub_domain);

            $request_data = [
                'name'                                 => 'WA '.$sub_domain,
                'chrome_web_origin'                    => $domain,
                'chrome_web_default_notification_icon' => wa()->getRootUrl(true).'wa-content/img/wa-logo.png',
                'safari_site_origin'                   => $domain,
                'safari_icon_256_256'                  => wa()->getRootUrl(true).'wa-content/img/wa-logo.png',
                'site_name'                            => wa()->accountName(),
            ];
            if (!$this->isLegacyV1()) {
                $request_data['organization_id'] = $org_id;
            }

            // First create the app without chrome subdomain
            $app = $this->request('apps', $request_data, waNet::METHOD_POST);
        } catch (waException $e) {
            $result = @json_decode($e->getMessage(), 1);
            if (!empty($result['errors'])) {
                throw new waException(join("\n", (array)$result['errors']), $e->getCode(), $e);
            }
            throw $e;
        }

        /* No more support for http (Support httpS only)

        // Then edit the app, trying to add a (semi-random) subdomain until there's no error.
        // This is only required for non-HTTPS domains.
        if (substr($domain, 0, 8) !== 'https://' && !empty($app)) {
            $i = 0;
            $updated = false;
            while (!$updated && $i < 4) {
                $i++;
                try {
                    $request_data = [
                        'chrome_web_origin'                    => $domain,
                        'chrome_web_sub_domain'                => $sub_domain,
                        'chrome_web_default_notification_icon' => '',
                        'safari_site_origin'                   => $domain,
                        'safari_icon_256_256'                  => '',
                        'site_name'                            => wa()->accountName(),
                    ];
                    $app = $this->request('apps/'.$app['id'], $request_data, waNet::METHOD_PUT);
                    $updated = true;
                } catch (waException $e) {
                    $sub_domain .= mt_rand(0, 99);
                    sleep(mt_rand(1, $i));
                }
            }
        } */

        self::clearCache();

        return $app;
    }

    /**
     * @return array
     * @throws waException
     */
    public function getApps()
    {
        $org_id = $this->getSettings(self::ORG_ID);
        $org_key = $this->getSettings(self::ORG_KEY);
        if (!$this->isLegacyV1() && empty($org_key) && empty($org_id)) {
            return [];
        }
        if (!$this->isLegacyV1() && (empty($org_key) || empty($org_id))) {
            throw new waException(_ws('OneSignal Organization ID and API Auth Key are required.'));
        }
        $api_key = $this->isLegacyV1() ? $this->getSettings(self::API_TOKEN) : $org_key;

        $this->lock();

        // Valid cache exists?
        $cache = $this->getCache('apps', $api_key);
        if ($cache->isCached()) {
            $this->unlock();
            return $cache->get();
        }

        // Fetch data from API
        try {
            $result = (array)$this->request('apps');
        } catch (waException $e) {
            $this->unlock();
            $result = @json_decode($e->getMessage(), 1);
            if (!empty($result['errors'])) {
                throw new waException(join("\n", (array)$result['errors']), $e->getCode(), $e);
            }
            throw $e;
        }

        try {
            // Save to cache
            $cache->set($result);
        } catch (Exception $e) {
            // Oh, well...
        }

        $this->unlock();

        return $result;
    }

    /**
     * @param null|string $domain
     * @return null|array
     * @throws waException
     */
    public function getAppByDomain($domain = null)
    {
        if (!$domain) {
            $domain = wa()->getConfig()->getHostUrl();
        }

        if (strpos($domain, 'http://') === 0) {
            // Support httpS only
            return null;
        }

        foreach ($this->getApps() as $app) {
            if (is_array($app) && isset($app['chrome_web_origin']) && $app['chrome_web_origin'] == $domain) {
                return $app;
            }
        }

        return null;
    }

    protected function getConnectedDomains()
    {
        $domains = array();
        foreach ($this->getApps() as $app) {
            $domain = is_array($app) && isset($app['chrome_web_origin']) ? $app['chrome_web_origin'] : null;
            if (!empty($domain) && strpos($domain, 'https://') === 0) {
                $api_key = $this->getSettings(self::APP_KEY . '_' . $app['id']);
                $domains[$domain] = array(
                    'name'      => $domain,
                    'id'        => $app['id'],
                    'api_key'   => $api_key,
                    'connected' => true,
                );
            }
        }
        return $domains;
    }

    //
    // API
    // If $api_key === false no authorization header will be used
    //
    protected function request($api_method, $request_data = array(), $request_method = waNet::METHOD_GET)
    {
        $res = null;
        try {
            $url = ($this->isLegacyV1() ? self::API_URL_V1 : self::API_URL_V2) .$api_method;
            $content = !empty($request_data) ? json_encode($request_data) : null;
            $net = $this->getNet();
            if (empty($net)) {
                // Do not send notification
                return null;
            }
            $res = $net->query($url, $content, $request_method);
            if (waSystemConfig::isDebug()) {
                $log = array(
                    'api_method'     => $api_method,
                    'data'           => $request_data,
                    'request_method' => $request_method,
                    'response'       => $res,
                );
                waLog::dump($log, 'push/onesignal.log');
            }
        } catch (Exception $e) {
            $log = array(
                'api_method'     => $api_method,
                'data'           => $request_data,
                'request_method' => $request_method,
                'error'          => $e->getMessage(),
                'error_code'     => $e->getCode(),
            );
            waLog::dump($log, 'push/onesignal.log');
            if ($this->force_expose_exceptions || $api_method !== 'notifications' && waRequest::method() === waRequest::METHOD_GET) {
                throw $e;
            }
        }

        return $res;
    }

    protected function setApiKey($api_key = null)
    {
        if ($api_key === null) {
            $api_key = $this->isLegacyV1() ? $this->getSettings(self::API_TOKEN) : $this->getSettings(self::ORG_KEY);
        }
        if ($this->net_api_token !== $api_key) {
            $this->net = null;
            $this->net_api_token = $api_key;
        }

        return $this->net_api_token;
    }

    protected function clearApiKey()
    {
        $this->net = null;
        $this->net_api_token = null;
    }

    /**
     * @return waNet
     */
    protected function getNet()
    {
        if (empty($this->net)) {
            if (empty($this->net_api_token) && $this->net_api_token !== false) {
                if (empty($this->setApiKey())) {
                    throw new waException(_ws('OneSignal API Key is required.'));
                }
            }
            if ($this->net_api_token === self::EMPTY_API_KEY_TEMPLATE) {
                // No api key for required app_id
                // Do not send notifications
                return null;
            }
            $options = [
                'timeout' => 7,
                'format' => waNet::FORMAT_JSON
            ];
            $custom_headers = [];
            if ($this->net_api_token !== false) {
                $custom_headers['Authorization'] = ($this->isLegacyV1() ? 'Basic ' : 'Key ') . $this->net_api_token;
            }
            $this->net = new waNet($options, $custom_headers);
        }

        return $this->net;
    }

    //
    // Cache
    //

    /**
     * @param string $cache_type
     * @param null $cache_params
     * @return waVarExportCache
     */
    protected function getCache($cache_type, $cache_params = null)
    {
        $cache_key = 'push/onesignal/'.$cache_type;
        if ($cache_params) {
            $cache_key .= '_';
            $cache_params = json_encode($cache_params);
            if (function_exists('hash')) {
                $cache_key .= hash("crc32b", $cache_params);
            } else {
                $cache_key .= str_pad(dechex(crc32($cache_params)), 8, '0', STR_PAD_LEFT);
            }
        }
        return new waVarExportCache($cache_key, 3600, 'webasyst');
    }

    public static function clearCache()
    {
        waFiles::delete(waSystem::getInstance()->getCachePath('cache/push/onesignal', 'webasyst'), true);
    }

    protected function lock()
    {
        $filename = wa()->getDataPath('onesignal_api.lock', false, 'webasyst');
        waFiles::create($filename);
        @touch($filename);
        @chmod($filename, 0666);
        $this->lock_fd = @fopen($filename, "r+");
        if (!$this->lock_fd || !flock($this->lock_fd, LOCK_EX)) {
            $this->lock_fd && fclose($this->lock_fd);
            $this->lock_fd = null;
            return false;
        }
        return true;
    }

    protected function unlock()
    {
        if ($this->lock_fd) {
            flock($this->lock_fd, LOCK_UN);
            fclose($this->lock_fd);
            $this->lock_fd = null;
        }
        return true;
    }

    protected function isLegacyV1()
    {
        $api_token = $this->getSettings(self::API_TOKEN);
        $org_id = $this->getSettings(self::ORG_ID);
        $org_key = $this->getSettings(self::ORG_KEY);
        return !empty($api_token) && (empty($org_id) || empty($org_key));
    }
}
