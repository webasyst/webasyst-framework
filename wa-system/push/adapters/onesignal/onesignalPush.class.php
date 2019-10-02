<?php

class onesignalPush extends waPushAdapter
{
    const PROVIDER_NAME = 'OneSignal';
    const API_URL = 'https://onesignal.com/api/v1/';
    const API_TOKEN = 'api_token';

    protected $net_api_token;
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
            $api_token = $this->getSettings(self::API_TOKEN);
            $app = $this->getAppByDomain();
            return !empty($api_token) && !empty($app);
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

        $options = array(
            'api_app_id'         => $app['id'],
            'api_subdomain_name' => $app['chrome_web_sub_domain'],
        );

        $view = wa('webasyst')->getView();
        $view->assign(array(
            'options'          => $options,
            'actions_url'      => $actions_url,
            'webasyst_app_url' => $webasyst_app_url,
            'api_token'        => $this->getSettings(self::API_TOKEN),
        ));
        $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/onesignal/init.js';
        return $view->fetch($template);
    }

    protected function initControls()
    {
        $api_token = $this->getSettings(self::API_TOKEN);

        $domains = array();

        $is_api_key_ok = !!$api_token;
        if ($is_api_key_ok) {
            try {
                // List of connected domains
                $domains = $this->getConnectedDomains();

                // Add current domain as unconnected if not in the list
                $current_domain = wa()->getConfig()->getHostUrl();
                $domains += array(
                    $current_domain => array(
                        'name'      => $current_domain,
                        'connected' => false,
                    ),
                );

                // Current domain always first, no matter connected or not
                $domains = array(
                        $current_domain => $domains[$current_domain],
                    ) + $domains;
            } catch (Exception $e) {
                $api_token_error = $e->getMessage();
                $is_api_key_ok = false;
            }
        }

        $view = wa('webasyst')->getView();
        $view->assign(array(
            'domains'         => $domains,
            'is_api_key_ok'   => $is_api_key_ok,
            'api_token_error' => ifset($api_token_error),
        ));
        $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/onesignal/api_key_description.html';

        $description = $view->fetch($template);

        $this->controls[self::API_TOKEN] = array(
            'title'        => _ws('OneSignal User Auth Key'),
            'control_type' => waHtmlControl::INPUT,
            'description'  => $description,
        );
    }

    //
    // Senders
    //

    public function send($id, $data)
    {
        $request_data = $this->prepareRequestData($data);

        $subscriber_list = $this->getSubscriberListByField('id', $id);
        $request_data['subscriber_list'] = $subscriber_list;

        foreach ($subscriber_list as $app_id => $user_ids) {
            $push_data = $request_data;
            $push_data['app_id'] = $app_id;
            $push_data['include_player_ids'] = $user_ids;
            $this->request('notifications', $push_data, waNet::METHOD_POST);
        }
    }

    public function sendByContact($contact_id, $data)
    {
        $request_data = $this->prepareRequestData($data);

        $subscriber_list = $this->getSubscriberListByField('contact_id', $contact_id);

        $result = array();
        foreach ($subscriber_list as $app_id => $user_ids) {
            $push_data = $request_data;
            $push_data['app_id'] = $app_id;
            $push_data['include_player_ids'] = $user_ids;
            $result[] = $this->request('notifications', $push_data, waNet::METHOD_POST);
        }

        return $result;
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

        $subscriber_list = array();
        foreach ($rows as $row) {
            if (!empty($row['subscriber_data'])) {
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
                return "importScripts('https://cdn.onesignal.com/sdks/OneSignalSDK.js');";
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
            return array('errors' => $e->getMessage());
        }
    }

    public function addApp($domain)
    {
        $api_token = $this->getSettings(self::API_TOKEN);
        if (!$api_token) {
            throw new waException(_ws('OneSignal User Auth Key is required.'));
        }

        // Fetch data from API
        try {
            $sub_domain = str_replace(array('http://', 'https://'), '', $domain);
            $sub_domain = str_replace('.', '_', $sub_domain);

            $request_data = array(
                'name'                                 => 'WA Push '.$sub_domain,
                'chrome_web_origin'                    => $domain,
                'chrome_web_default_notification_icon' => wa()->getRootUrl(true).'wa-content/img/wa-logo.png',
            );

            // First create the app without chrome subdomain
            $app = $this->request('apps', $request_data, waNet::METHOD_POST);
        } catch (waException $e) {
            $result = @json_decode($e->getMessage(), 1);
            if (!empty($result['errors'])) {
                throw new waException(join("\n", (array)$result['errors']), $e->getCode(), $e);
            }
            throw $e;
        }

        // Then edit the app, trying to add a (semi-random) subdomain until there's no error.
        // This is only required for non-HTTPS domains.
        if (substr($domain, 0, 8) !== 'https://') {
            $i = 0;
            $updated = false;
            while (!$updated && $i < 4) {
                $i++;
                try {
                    $request_data = array(
                        'chrome_web_origin'                    => $domain,
                        'chrome_web_sub_domain'                => $sub_domain,
                        'chrome_web_default_notification_icon' => '',
                    );
                    $app = $this->request('apps', $request_data, waNet::METHOD_PUT);
                    $updated = true;
                } catch (waException $e) {
                    $sub_domain .= mt_rand(0, 99);
                    sleep(mt_rand(1, $i));
                }
            }
        }

        self::clearCache();

        return $app;
    }

    /**
     * @return array
     * @throws waException
     */
    public function getApps()
    {
        $api_token = $this->getSettings(self::API_TOKEN);
        if (!$api_token) {
            throw new waException(_ws('OneSignal User Auth Key is required.'));
        }

        $this->lock();

        // Valid cache exists?
        $cache = $this->getCache('apps', $api_token);
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

        foreach ($this->getApps() as $app) {
            if ($app['chrome_web_origin'] == $domain) {
                return $app;
            }
        }

        return null;
    }

    protected function getConnectedDomains()
    {
        $domains = array();
        foreach ($this->getApps() as $app) {
            $domain = $app['chrome_web_origin'];
            $domains[$domain] = array(
                'name'      => $domain,
                'connected' => true,
            );
        }
        return $domains;
    }

    //
    // API
    //

    protected function request($api_method, $request_data = array(), $request_method = waNet::METHOD_GET)
    {
        $res = null;
        try {
            $url = self::API_URL.$api_method;
            $content = !empty($request_data) ? json_encode($request_data) : null;
            $res = $this->getNet()->query($url, $content, $request_method);
        } catch (Exception $e) {
            $log = array(
                'api_method'     => $api_method,
                'data'           => $request_data,
                'request_method' => $request_method,
                'error'          => $e->getMessage(),
                'error_code'     => $e->getCode(),
            );
            waLog::dump($log, 'push/onesignal.log');
        }

        return $res;
    }

    /**
     * @return waNet
     */
    protected function getNet()
    {
        $api_token = $this->getSettings(self::API_TOKEN);
        if (empty($this->net) || $api_token !== $this->net_api_token) {

            $options = array(
                'format' => waNet::FORMAT_JSON,
            );

            $custom_headers = array(
                'timeout'       => 7,
                'Authorization' => 'Basic '.(string)$api_token,
            );

            $this->net_api_token = $api_token;

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
}