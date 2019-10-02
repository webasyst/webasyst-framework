<?php

class firebasePush extends waPushAdapter
{
    const PROVIDER_NAME = 'Firebase';
    const API_URL = 'https://fcm.googleapis.com/';
    const API_KEY = 'api_key';
    const SENDER_ID = 'sender_id';

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
        $api_key = $this->getSettings(self::API_KEY);
        return !empty($api_key);
    }

    protected function initControls()
    {
        $this->controls[self::API_KEY] = array(
            'title'        => _ws('Server key'),
            'control_type' => waHtmlControl::INPUT,
        );
        $this->controls[self::SENDER_ID] = array(
            'title'        => _ws('Sender ID'),
            'control_type' => waHtmlControl::INPUT,
            'description'  => _ws('Copy a <strong>Server key</strong> and a <strong>Sender ID</strong> under “Cloud Messaging” tab in <a href="https://console.firebase.google.com/project/" target="_blank">project settings<i class="icon16 new-window"></i></a>.'),
        );
    }

    public function getInitJs()
    {
        $is_enabled = $this->isEnabled();
        if (!$is_enabled || !waRequest::isHttps()) {
            return null;
        }

        $webasyst_app_url = wa()->getConfig()->getBackendUrl(true).'webasyst/';

        $view = wa('webasyst')->getView();
        $view->assign(array(
            'webasyst_app_url'        => $webasyst_app_url,
            'firebase_core_path'      => $this->getActionUrl('firebase_core.djs'),
            'firebase_messaging_path' => $this->getActionUrl('firebase_messaging_sw.djs'),
            'firebase_sender_id'      => $this->getSettings(self::SENDER_ID),
        ));
        $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/firebase/init.js';
        return $view->fetch($template);
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
        }
    }

    protected function getStaticContent($action)
    {
        switch ($action) {
            case 'firebase_messaging_sw.djs':
                $sender_id = $this->getSettings(self::SENDER_ID);
                $view = wa('webasyst')->getView();
                $view->assign('sender_id', $sender_id);
                $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/firebase/firebase-messaging-sw.js';
                return $view->fetch($template);

            case 'firebase_core.djs':
                $view = wa('webasyst')->getView();
                $view->setOptions(array('left_delimiter' => '{{{', 'right_delimiter' => '}}}'));
                $view->assign('messaging_sw_url', $this->getActionUrl('firebase_messaging_sw.djs'));
                $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/firebase/firebase-core.js';
                return $view->fetch($template);
        }
    }

    /*
    public function setup()
    {
        $sender_id = $this->getSettings(self::SENDER_ID);
        if (empty($sender_id)) {
            return array('errors' => 'Sender ID обязателен!';
        }

        $firebase_messaging_path = wa()->getConfig()->getRootPath().'/firebase_messaging_sw.js';
        if (is_dir($firebase_messaging_path) || (file_exists($firebase_messaging_path) && !is_writable($firebase_messaging_path))) {
            return array('errors' => $firebase_messaging_path.'Не удаётся записать файл'.' /firebase_messaging_sw.js');
        }

        $view = wa('webasyst')->getView();
        $view->assign('sender_id', $sender_id);
        $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/firebase/firebase_messaging_sw.js';
        $firebase_messaging = $view->fetch($template);

        try {
            $length = waFiles::write($firebase_messaging_path, $firebase_messaging);
        } catch (Exception $e) {
            $length = false;
        }
        if (empty($length)) {
            return array('errors' => 'Не удаётся записать файл'.' /firebase_messaging_sw.js');
        }

        return array('reload' => true);
    }
    */

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

        $sender_id = $this->getSettings(self::SENDER_ID);
        $subscriber_list = array();
        foreach ($rows as $row) {
            if (!empty($row['subscriber_data'])) {
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
        $errors = array();
        $request_data = $this->prepareRequestData($data);
        if (!empty($subscriber_list)) {
            foreach ($subscriber_list as $subscriber) {
                try {
                    $request_data['to'] = $subscriber;
                    $this->request('fcm/send', $request_data, waNet::METHOD_POST);
                } catch (Exception $e) {
                    $errors[$subscriber] = $e->getMessage();
                }
            }
        }

        return $errors;
    }

    protected function prepareRequestData(array $data)
    {
        $request_data = array(
            'data' => array(
                'title'        => (string)ifempty($data, 'title', null),
                'body'         => (string)ifempty($data, 'message', null),
                'click_action' => (string)ifempty($data, 'url', null),
            ),
        );

        if (!empty($data['image_url'])) {
            $request_data['data']['image'] = (string)$data['image_url'];
            $request_data['data']['icon'] = (string)$data['image_url'];
        }

        return $request_data;
    }

    //
    // API
    //

    protected function request($api_method, $request_data = array(), $request_method = waNet::METHOD_GET)
    {
        $is_debug = waSystemConfig::isDebug();
        $res = null;
        try {
            $url = self::API_URL.$api_method;
            $content = !empty($request_data) ? json_encode($request_data) : null;
            $res = $this->getNet()->query($url, $content, $request_method);
            if ($is_debug) {
                waLog::dump([$url, $content, $request_method, $res], 'push/firebase_request.log');
            }
        } catch (Exception $e) {
            $log = array(
                'api_method'     => $api_method,
                'data'           => $request_data,
                'request_method' => $request_method,
                'error'          => $e->getMessage(),
                'error_code'     => $e->getCode(),
            );
            waLog::dump($log, 'push/firebase_error.log');
        }

        return $res;
    }

    /**
     * @return waNet
     */
    protected function getNet()
    {
        if (empty($this->net)) {
            $api_key = $this->getSettings(self::API_KEY);

            $options = array(
                'format' => waNet::FORMAT_JSON,
            );

            $custom_headers = array(
                'timeout'       => 7,
                'Authorization' => 'key='.(string)$api_key,
            );

            $this->net = new waNet($options, $custom_headers);
        }

        return $this->net;
    }
}