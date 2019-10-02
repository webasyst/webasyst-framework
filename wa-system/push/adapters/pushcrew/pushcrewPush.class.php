<?php

class pushcrewPush extends waPushAdapter
{
    const PROVIDER_NAME = 'PushCrew';

    const API_URL = 'https://pushcrew.com/api/v1/';
    const API_ACCOUNT_ID = 'account_id';
    const API_TOKEN = 'api_token';

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
        $account_id = $this->getSettings(self::API_ACCOUNT_ID);
        $api_token = $this->getSettings(self::API_TOKEN);
        return !empty($account_id) && !empty($api_token);
    }

    protected function initControls()
    {
        $this->controls[self::API_ACCOUNT_ID] = array(
            'title'        => _ws('Account ID'),
            'description'  => _ws('Get an account ID <a href="https://pushcrew.com/admin/app.php#/settings/account/general" target="_blank">in your profile</a>.'),
            'control_type' => waHtmlControl::INPUT,
        );
        $this->controls[self::API_TOKEN] = array(
            'title'        => _ws('API token'),
            'description'  => _ws('Get an API token <a href="https://pushcrew.com/admin/app.php#/settings/account/general" target="_blank">in your profile</a>.'),
            'control_type' => waHtmlControl::INPUT,
        );
    }

    public function getInitJs()
    {
        $is_enabled = $this->isEnabled();
        if (!$is_enabled) {
            return null;
        }

        $webasyst_app_url = wa()->getConfig()->getBackendUrl(true).'webasyst/';

        $view = wa('webasyst')->getView();
        $view->assign(array(
            'webasyst_app_url' => $webasyst_app_url,
            'account_id'       => $this->getSettings(self::API_ACCOUNT_ID),
        ));
        $template = wa()->getConfig()->getRootPath().'/wa-system/push/adapters/pushcrew/init.js';
        return $view->fetch($template);
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

    protected function sendPush($data, $subscriber_list)
    {
        $errors = array();
        $request_data = $this->prepareRequestData($data);
        if (!empty($subscriber_list)) {
            foreach ($subscriber_list as $subscriber) {
                try {
                    $request_data['subscriber_id'] = $subscriber;
                    $this->request('send/individual', $request_data, waNet::METHOD_POST);
                } catch (Exception $e) {
                    waLog::log("PushCrew: invalid subscriber_id: ".$subscriber."\n".$e->getMessage(), 'push/pushcrew_errors.log');
                    $errors[$subscriber] = $e->getMessage();
                }
            }
        }

        return $errors;
    }

    protected function prepareRequestData(array $data)
    {
        $request_data = array(
            'title'   => (string)ifempty($data, 'title', null),
            'message' => (string)ifempty($data, 'message', null),
            'url'     => (string)ifempty($data, 'url', null),
        );

        if (!empty($data['image_url'])) {
            $request_data['image_url'] = (string)$data['image_url'];
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

        $api_account_id = $this->getSettings(self::API_ACCOUNT_ID);
        $subscriber_list = array();
        foreach ($rows as $row) {
            if (!empty($row['subscriber_data'])) {
                $subscriber_data = json_decode($row['subscriber_data'], true);
                if (!empty($subscriber_data) && $api_account_id == $subscriber_data['account_id']) {
                    $subscriber_list[] = (string)$subscriber_data['subscriber_id'];
                }
            }
        }
        return $subscriber_list;
    }

    //
    // API
    //

    protected function request($api_method, $request_data = array(), $request_method = waNet::METHOD_GET)
    {
        $url = self::API_URL.$api_method;

        $request_data = json_encode($request_data);
        $res = $this->getNet()->query($url, $request_data, $request_method);
        return $res;
    }

    /**
     * @return waNet
     */
    protected function getNet()
    {
        if (empty($this->net)) {
            $options = array(
                'format' => waNet::FORMAT_JSON,
            );

            $custom_headers = array(
                'Authorization' => 'key='.(string)$this->getSettings(self::API_TOKEN),
            );

            $this->net = new waNet($options, $custom_headers);
        }

        return $this->net;
    }
}