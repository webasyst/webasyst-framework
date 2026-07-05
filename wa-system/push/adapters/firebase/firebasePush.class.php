<?php

use Google\Auth\CredentialsLoader;
use Google\Auth\Middleware\AuthTokenMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

class firebasePush extends waPushAdapter
{
    const PROVIDER_NAME = 'Firebase';
    const API_URL = 'https://fcm.googleapis.com/v1/projects/';
    const API_KEY = 'api_key';
    const SENDER_ID = 'sender_id';
    const PROJECT_ID = 'project_id';
    const APP_ID = 'app_id';
    const VAPID_KEY = 'vapid_key';
    const JSON_KEY = 'json_key';
    const SETTINGS_UPDATED_AT = 'update_ts';
    const SCOPES = ['https://www.googleapis.com/auth/firebase.messaging'];

    protected $net;

    public function __construct($options = [])
    {
        $vendor_dir = dirname(__FILE__).'/vendor/';

        require_once($vendor_dir.'autoload.php');
        return;

        if (!interface_exists('\Psr\Http\Message\UriInterface')) {
            require_once($vendor_dir.'psr/http-message/src/UriInterface.php');
            require_once($vendor_dir.'psr/http-message/src/StreamInterface.php');
            require_once($vendor_dir.'psr/http-message/src/MessageInterface.php');
            require_once($vendor_dir.'psr/http-message/src/ResponseInterface.php');
            require_once($vendor_dir.'psr/http-client/src/ClientExceptionInterface.php');
            require_once($vendor_dir.'psr/http-client/src/RequestExceptionInterface.php');
            require_once($vendor_dir.'psr/http-client/src/ClientInterface.php');
            require_once($vendor_dir.'psr/http-message/src/MessageInterface.php');
            require_once($vendor_dir.'psr/http-message/src/RequestInterface.php');
        }

        require_once($vendor_dir.'google/auth/src/UpdateMetadataInterface.php');
        require_once($vendor_dir.'google/auth/src/FetchAuthTokenInterface.php');
        require_once($vendor_dir.'google/auth/src/GetUniverseDomainInterface.php');
        require_once($vendor_dir.'google/auth/src/UpdateMetadataTrait.php');
        require_once($vendor_dir.'google/auth/src/CredentialsLoader.php');
        require_once($vendor_dir.'google/auth/src/ServiceAccountSignerTrait.php');
        require_once($vendor_dir.'google/auth/src/GetQuotaProjectInterface.php');
        require_once($vendor_dir.'google/auth/src/SignBlobInterface.php');
        require_once($vendor_dir.'google/auth/src/ProjectIdProviderInterface.php');
        require_once($vendor_dir.'guzzlehttp/psr7/src/Uri.php');
        require_once($vendor_dir.'guzzlehttp/psr7/src/Stream.php');
        require_once($vendor_dir.'guzzlehttp/psr7/src/Utils.php');
        require_once($vendor_dir.'google/auth/src/HttpHandler/Guzzle6HttpHandler.php');
        require_once($vendor_dir.'google/auth/src/HttpHandler/Guzzle7HttpHandler.php');
        require_once($vendor_dir.'google/auth/src/HttpHandler/HttpHandlerFactory.php');
        require_once($vendor_dir.'google/auth/src/HttpHandler/HttpClientCache.php');
        require_once($vendor_dir.'firebase/php-jwt/src/JWT.php');
        require_once($vendor_dir.'guzzlehttp/psr7/src/Query.php');
        require_once($vendor_dir.'google/auth/src/OAuth2.php');
        require_once($vendor_dir.'google/auth/src/Credentials/ServiceAccountCredentials.php');
        require_once($vendor_dir.'google/auth/src/Middleware/AuthTokenMiddleware.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Handler/Proxy.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Handler/StreamHandler.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Handler/CurlHandler.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Handler/CurlFactoryInterface.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Handler/HeaderProcessor.php');
        require_once($vendor_dir.'guzzlehttp/psr7/src/MessageTrait.php');
        require_once($vendor_dir.'guzzlehttp/psr7/src/Message.php');
        require_once($vendor_dir.'guzzlehttp/psr7/src/Response.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Handler/EasyHandle.php');
        require_once($vendor_dir.'guzzlehttp/promises/src/TaskQueueInterface.php');
        require_once($vendor_dir.'guzzlehttp/promises/src/TaskQueue.php');
        require_once($vendor_dir.'guzzlehttp/promises/src/Utils.php');
        require_once($vendor_dir.'guzzlehttp/promises/src/PromiseInterface.php');
        require_once($vendor_dir.'guzzlehttp/promises/src/RejectedPromise.php');
        require_once($vendor_dir.'guzzlehttp/promises/src/Is.php');
        require_once($vendor_dir.'guzzlehttp/promises/src/Promise.php');
        require_once($vendor_dir.'guzzlehttp/promises/src/FulfilledPromise.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Handler/CurlFactory.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Handler/CurlMultiHandler.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Utils.php');
        require_once($vendor_dir.'guzzlehttp/psr7/src/MimeType.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/PrepareBodyMiddleware.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/BodySummarizerInterface.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/BodySummarizer.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Exception/GuzzleException.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Exception/TransferException.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Exception/RequestException.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Exception/BadResponseException.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Exception/ClientException.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Exception/InvalidArgumentException.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Middleware.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/HandlerStack.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/ClientTrait.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/ClientInterface.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/RedirectMiddleware.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/RequestOptions.php');
        require_once($vendor_dir.'guzzlehttp/psr7/src/UriResolver.php');
        require_once($vendor_dir.'guzzlehttp/psr7/src/Request.php');
        require_once($vendor_dir.'guzzlehttp/promises/src/Create.php');
        require_once($vendor_dir.'guzzlehttp/guzzle/src/Client.php');
    }

    //
    // Init methods
    //
    public function getName()
    {
        return self::PROVIDER_NAME;
    }

    public function isEnabled()
    {
        $settings = $this->getSettings();
        return !empty($settings[self::API_KEY])
            && !empty($settings[self::PROJECT_ID])
            && !empty($settings[self::APP_ID])
            && !empty($settings[self::SENDER_ID])
            && !empty($settings[self::JSON_KEY])
            && !empty($settings[self::VAPID_KEY]);
    }

    public function getSettingsHtml($params = array())
    {
        self::clearCache();
        return parent::getSettingsHtml($params);
    }

    public static function clearCache()
    {
        waFiles::delete(waSystem::getInstance()->getCachePath('cache/push/firebase', 'webasyst'), true);
    }

    protected function initControls()
    {
        $project_id_str = $this->getSettings(self::PROJECT_ID) ?: '_';

        $this->controls[self::PROJECT_ID] = array(
            'title'        => _ws('Project ID'),
            'control_type' => waHtmlControl::INPUT,
            'description'  => sprintf(
                _ws('Create a project in the <a %s>Firebase Console</a> and copy a <strong>Project ID</strong> under the <em>General</em> tab in the project settings.'),
                'href="https://console.firebase.google.com/" target="_blank"'
            ),
            'translate' => false,
        );
        $this->controls[self::SENDER_ID] = array(
            'title'        => _ws('Sender ID'),
            'control_type' => waHtmlControl::INPUT,
            'description'  => sprintf(
                _ws('Copy the <strong>Sender ID</strong> under the <em>Cloud Messaging</em> tab in the <a %s>project settings</a>.'),
                sprintf(
                    'class="js-firebase-sender_id-link" href="https://console.firebase.google.com/project/%s/settings/cloudmessaging" target="_blank"',
                    $project_id_str
                )
            ),
            'translate' => false,
        );
        $this->controls[self::APP_ID] = array(
            'title'        => _ws('App ID'),
            'control_type' => waHtmlControl::INPUT,
            'description'  => sprintf(
                _ws('Open the <em>General</em> tab in the <a %s>project settings</a> and scroll to <em>Your apps</em> section. Select the <strong>Web</strong> platform and create the app. Copy the <strong>App ID</strong> value of the created app.'),
                sprintf(
                    'class="js-firebase-app_id-link" href="https://console.firebase.google.com/project/%s/settings/general/" target="_blank"',
                    $project_id_str
                )
            ),
            'translate' => false,
        );
        $this->controls[self::API_KEY] = array(
            'title'        => _ws('API key'),
            'control_type' => waHtmlControl::INPUT,
            'description'  => sprintf(
                _ws('Copy the <strong>API key</strong> at <em>APIs & Services › <a %s>Credentials</a></em> in the Google Cloud console. <strong>Do not create a new API key</strong>, use the one auto-created by Firebase.'),
                sprintf(
                    'class="js-firebase-api_key-link" href="https://console.cloud.google.com/apis/credentials?project=%s" target="_blank"',
                    $this->getSettings(self::PROJECT_ID)
                )
            ),
            'translate' => false,
        );
        $this->controls[self::VAPID_KEY] = array(
            'title'        => _ws('VAPID Public Key'),
            'control_type' => waHtmlControl::INPUT,
            'description'  =>
            _ws('To generate a Voluntary Application Server Identification (VAPID) key pair:')
                . '<ol><li>' .
                sprintf(
                    _ws('Open the <em><a %s>Cloud Messaging</a></em> tab in the Firebase console’s <em>Settings</em> section and scroll to the <em>Web configuration</em> section.'),
                    sprintf(
                        'class="js-firebase-vapid_key-link" href="https://console.firebase.google.com/project/%s/settings/cloudmessaging/" target="_blank"',
                        $project_id_str
                    )
                )
                . '</li><li>' .
                _ws('Under the <em>Web Push certificates</em> tab, click on <strong>Generate Key Pair</strong>. The console will display a notice about the generated key pair as well as the public key and its adding date.')
                . '</li><li>' .
                _ws('Copy & paste the public key value into the field above.')
                . '</li></ol>',
            'translate' => false,
        );

        $view = wa('webasyst')->getView();
        $view->assign([
            'validator_path' => $this->getActionUrl('settings_validate.djs'),
        ]);
        $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/firebase/settings-addon.html';
        $description = $view->fetch($template);
        $this->controls[self::JSON_KEY] = array(
            'title'        => _ws('Private Key JSON'),
            'control_type' => waHtmlControl::TEXTAREA,
            'description'  =>
            _ws('To generate a private key file for your service account:')
                . '<ol><li>'
                . sprintf(
                    _ws('In the Firebase console, open <em>Settings › <a %s>Service Accounts</a></em>.'),
                    sprintf(
                        'class="js-firebase-json_key-link" href="https://console.firebase.google.com/project/%s/settings/serviceaccounts/adminsdk" target="_blank"',
                        $project_id_str
                    )
                )
                . '</li><li>'
                . _ws('Click <strong>Generate New Private Key</strong> and confirm the key creation by clicking <em>Generate Key</em>.')
                . '</li><li>'
                . _ws('Select the downloaded JSON file.')
                . '</li></ol>'
                . $description,
            'translate' => false,
        );

        $this->controls[self::SETTINGS_UPDATED_AT] = [
            'title'        => '',
            'control_type' => waHtmlControl::HIDDEN,
            'description'  => '',
        ];
    }

    public function validateSettings($settings = [])
    {
        $json_key = $settings[self::JSON_KEY];
        if (empty($json_key)) {
            return sprintf(
                _ws('A “%s” value is required.'),
                _ws('Private Key JSON')
            );
        }
        $json_key = json_decode($json_key, true);
        if (empty($json_key) || !is_array($json_key)) {
            return sprintf(
                _ws('Invalid “%s” value format. A JSON structure is required.'),
                _ws('Private Key JSON')
            );
        }
        if (empty($json_key['private_key'])) {
            return sprintf(
                _ws('The “%s” value must contain the <em>private_key</em> key.'),
                _ws('Private Key JSON')
            );
        }
        if (empty($json_key['project_id']) || $json_key['project_id'] != $settings[self::PROJECT_ID]) {
            return sprintf(
                _ws('The “%s” value does not correspond to the specified Project ID.'),
                _ws('Private Key JSON')
            );
        }

        set_error_handler(function ($errno, $err_str, $err_file, $err_line) {
            // https://www.php.net/manual/en/language.operators.errorcontrol.php
            if (error_reporting() !== 0 && error_reporting() !== E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR | E_PARSE) {
                waLog::dump([
                    'warning' => $err_str,
                    'code'    => $errno,
                    'file'    => $err_file,
                    'line'    => $err_line,
                    'url'     => waRequest::server('REQUEST_URI')
                ], 'error.log');
            }
        });

        $creds = CredentialsLoader::makeCredentials(self::SCOPES, $json_key);

        // create middleware
        $middleware = new AuthTokenMiddleware($creds);
        $stack = HandlerStack::create();
        $stack->push($middleware);

        // create the HTTP client
        $client = new Client([
            'handler' => $stack,
            'base_uri' => 'https://www.googleapis.com',
            'auth' => 'google_auth'  // authorize all requests
        ]);

        $body = json_encode([
            'validate_only' => true,
            'message' => [
              'notification' => [
                'title' => 'Test',
                'body' => 'Test.',
              ],
              'data' => [
                'link' => wa()->getConfig()->getBackendUrl(true),
              ],
              'topic' => 'foo',
            ],
        ]);
        try {
            $response = $client->post(self::API_URL.$settings[self::PROJECT_ID].'/messages:send', ['body' => $body]);
        } catch (GuzzleHttp\Exception\ClientException $e) {
            return 'Invalid Project ID or JSON Key';
        } catch (DomainException $e) {
            return 'Invalid JSON Key';
        } catch (Exception $e) {
            $log = [
                'subject'        => 'Unable to validate Firebase settings',
                'error'          => $e->getMessage(),
                'error_code'     => $e->getCode(),
            ];
            waLog::dump($log, 'push/firebase_error.log');
            return 'Unable to validate Firebase settings';
        }

        return null;
    }

    public function saveSettings($settings = [])
    {
        $settings['update_ts'] = time();
        parent::saveSettings($settings);
    }

    public function getInitJs()
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $webasyst_app_url = wa()->getConfig()->getBackendUrl(true).'webasyst/';

        $view = wa('webasyst')->getView();
        $view->assign($this->getSettings());
        $view->assign([
            'webasyst_app_url'        => $webasyst_app_url,
            'firebase_core_path'      => $this->getActionUrl('firebase_core.djs'),
            'firebase_messaging_path' => $this->getActionUrl('firebase_messaging_sw.djs'),
        ]);
        $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/firebase/init.js';
        return $view->fetch($template);
    }

/*
    public function getInitData()
    {
        $is_enabled = $this->isEnabled();
        if (!$is_enabled) {
            return null;
        }

        return [ ['key' => 'messagingSenderId', 'value' => $this->getSettings(self::SENDER_ID)] ];
    }
*/

    protected function normalizeSubscriberData($data)
    {
        if (!is_array($data) ||
            !ifset($data['firebase_client_token'])
        ) {
            throw new waException(_ws('Invalid subscriber data'));
        }
        return [
            'sender_id' => $this->getSettings(self::SENDER_ID),
            'token' => $data['firebase_client_token'],
        ];
    }

    //
    // Dispatch actions
    //

    public function dispatch($action)
    {
        switch ($action) {
            // push.php/firebase/firebase_messaging_sw.djs
            case 'firebase_messaging_sw.djs':
                wa()->getResponse()->addHeader('Service-Worker-Allowed', '/');
                wa()->getResponse()->addHeader('Content-type', 'application/javascript');
                wa()->getResponse()->sendHeaders();
                echo $this->getStaticContent($action);
                break;

            // push.php/firebase/firebase_core.djs
            case 'firebase_core.djs':
                wa()->getResponse()->addHeader('Content-type', 'application/javascript');
                wa()->getResponse()->sendHeaders();
                echo $this->getStaticContent($action);
                break;

            // push.php/firebase/settings_validate.djs
            case 'settings_validate.djs':
                wa()->getResponse()->addHeader('Content-type', 'application/javascript');
                wa()->getResponse()->sendHeaders();
                echo $this->getStaticContent($action);
                break;

            // push.php/firebase/firebase_messaging_empty_sw.djs
            case 'firebase_messaging_empty_sw.djs':
                wa()->getResponse()->addHeader('Service-Worker-Allowed', '/');
                wa()->getResponse()->addHeader('Content-type', 'application/javascript');
                wa()->getResponse()->sendHeaders();
                echo '';
                break;
        }
    }

    protected function getStaticContent($action)
    {
        switch ($action) {
            case 'firebase_messaging_sw.djs':
                $view = wa('webasyst')->getView();
                $view->assign($this->getSettings());
                $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/firebase/firebase-messaging-sw.js';
                return $view->fetch($template);

            case 'firebase_core.djs':
                $view = wa('webasyst')->getView();
                $view->assign($this->getSettings());
                $view->assign([
                    'webasyst_app_url' => wa()->getConfig()->getBackendUrl(true).'webasyst/',
                    'messaging_sw_url' => $this->getActionUrl('firebase_messaging_sw.djs'),
                    'testSuccessText' => wa()->getLocale() === 'ru_RU' ? 'Тестовое уведомление получено:' : 'Test notification received:',
                ]);
                $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/firebase/firebase-core.js';
                return $view->fetch($template);

            case 'settings_validate.djs':
                $view = wa('webasyst')->getView();
                $view->assign([
                    'messaging_sw_url' => $this->getActionUrl('firebase_messaging_empty_sw.djs'),
                ]);
                $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/firebase/settings-validate.js';
                return $view->fetch($template);

        }
    }

    public function setup()
    {
        return array('reload' => true);
    }

    //
    // Senders
    //

    public function send($id, $data)
    {
        $subscriber_list = $this->getSubscriberListByField('id', $id);
        return $this->sendPush($data, $subscriber_list);
    }

    public function sendByContact($contact_id, $data)
    {
        $subscriber_list = $this->getSubscriberListByField('contact_id', $contact_id);
        return $this->sendPush($data, $subscriber_list);
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
        $sender_id = $this->getSettings(self::SENDER_ID);
        $subscriber_list = array();
        foreach ($rows as $row) {
            $scope = $row['scope'];
            if (!empty($row['subscriber_data']) && (empty($scope) || in_array($scope_app, explode(',', $scope)))) {
                $subscriber_data = json_decode($row['subscriber_data'], true);
                if (!empty($subscriber_data) && $sender_id == $subscriber_data[self::SENDER_ID]) {
                    $subscriber_list[] = (string)$subscriber_data['token'];
                }
            }
        }
        return $subscriber_list;
    }

    protected function sendPush($data, $subscriber_list)
    {
        if (empty($subscriber_list)) {
            return [];
        }
        $errors = [];
        $message = $this->prepareRequestData($data);
        foreach ($subscriber_list as $subscriber) {
            $message['message']['token'] = $subscriber;
            try {
                $this->request($message);
            } catch (Exception $e) {
                $errors[$subscriber] = $e->getMessage();
            }
        }
        return $errors;
    }

    protected function prepareRequestData(array $data)
    {
        $notification = [
            'title' => (string)ifempty($data, 'title', ''),
            'body'  => (string)ifempty($data, 'message', ''),
        ];
        $message = [
            'notification' => $notification,
            'data' => $notification,
        ];

        if (isset($data['ttl'])) {
            if (!isset($message['webpush'])) {
                $message['webpush'] = [];
            }
            $message['webpush']['headers'] = [
                'TTL' => strval($data['ttl']),
            ];
            $message['android'] = [
                'ttl' => strval($data['ttl']) . 's',
            ];
        }

        if (ifset($data['image_url'])) {
            $message['notification']['image'] = (string)$data['image_url'];
            $message['data']['image'] = (string)$data['image_url'];
            //$message['webpush']['notification']['image'] = (string)$data['image_url'];
        }

        if (isset($data['data']) && is_array($data['data'])) {
            $message['data'] += array_map(function ($value) { return (string)$value; }, $data['data']);
        }

        if (ifset($data['url'])/* && strpos($data['url'], 'https://') === 0*/) {
            if (!isset($message['webpush'])) {
                $message['webpush'] = [];
            }
            $message['webpush']['fcm_options'] = [
                'link' => (string)$data['url'],
            ];
            $message['data']['link'] = (string)$data['url'];
        }

        return ['message' => $message];
    }

    //
    // API
    //
    protected function request($request_data)
    {
        $is_debug = waSystemConfig::isDebug();
        $project_id = $this->getSettings(self::PROJECT_ID);
        $res = null;

        // Define the Google Application Credentials array
        $jsonKey = $this->getSettings(self::JSON_KEY);

        // Load credentials
        $creds = CredentialsLoader::makeCredentials(self::SCOPES, $jsonKey);

        // create middleware
        $middleware = new AuthTokenMiddleware($creds);
        $stack = HandlerStack::create();
        $stack->push($middleware);

        // create the HTTP client
        $client = new Client([
            'handler' => $stack,
            'base_uri' => 'https://www.googleapis.com',
            'auth' => 'google_auth'  // authorize all requests
        ]);

        $body = json_encode($request_data);
        try {
            // make the request
            $response = $client->post(self::API_URL.$project_id.'/messages:send', ['body' => $body]);
            if ($is_debug) {
                waLog::dump([$request_data, $response->getStatusCode()], 'push/firebase_request.log');
            }
        } catch (Exception $e) {
            $log = array(
                'data'           => $request_data,
                'error'          => $e->getMessage(),
                'error_code'     => $e->getCode(),
            );
            waLog::dump($log, 'push/firebase_error.log');
        }
    }
}
