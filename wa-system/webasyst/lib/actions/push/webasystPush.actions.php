<?php

class webasystPushActions extends waActions
{
    public function addSubscriberAction()
    {
        $push_adapters = wa()->getPushAdapters();
        $provider_id = waRequest::post('provider_id', null, waRequest::TYPE_STRING_TRIM);
        if (!$provider_id || !array_key_exists($provider_id, $push_adapters)) {
            return $this->displayJson(null, 'The specified provider is not found');
        }

        $data = waRequest::post('data');
        if (empty($data)) {
            return $this->displayJson(null, 'Subscriber data is empty');
        }

        $subscriber = array(
            'provider_id'     => $provider_id,
            'domain'          => waRequest::server('HTTP_HOST'),
            'create_datetime' => date("Y-m-d H:i:s"),
            'contact_id'      => wa()->getUser()->getId(),
            'subscriber_data' => is_array($data) ? json_encode($data) : $data,
        );

        try {
            $psm = new waPushSubscribersModel();
            $data_for_search = $subscriber;
            unset($data_for_search['domain'], $data_for_search['create_datetime']);
            if ($psm->getByField($data_for_search)) {
                return $this->displayJson(true);
            }
            $res = $psm->insert($subscriber);
            return $this->displayJson(!empty($res));
        } catch (waException $e) {
            return $this->displayJson(null, $e->getMessage());
        }
    }

    public function deleteSubscriberAction()
    {
        $push_adapters = wa()->getPushAdapters();
        $provider_id = waRequest::post('provider_id', null, waRequest::TYPE_STRING_TRIM);
        if (!$provider_id || !array_key_exists($provider_id, $push_adapters)) {
            return $this->displayJson(null, 'The specified provider is not found');
        }

        $data = waRequest::post('data');
        if (empty($data)) {
            return $this->displayJson(null, 'Subscriber data is empty');
        }

        $data_for_search = [
            'provider_id'     => $provider_id,
            'contact_id'      => wa()->getUser()->getId(),
            'subscriber_data' => is_array($data) ? json_encode($data) : $data,
        ];

        try {
            $psm = new waPushSubscribersModel();
            $psm->deleteByField($data_for_search);
            return $this->displayJson(null);
        } catch (waException $e) {
            return $this->displayJson(null, $e->getMessage());
        }
    }

    public function checkSubscriberAction()
    {
        $push_adapters = wa()->getPushAdapters();
        $provider_id = waRequest::post('provider_id', null, waRequest::TYPE_STRING_TRIM);
        if (!$provider_id || !array_key_exists($provider_id, $push_adapters)) {
            return $this->displayJson(null, 'The specified provider is not found');
        }

        $data = waRequest::post('data');
        if (empty($data)) {
            return $this->displayJson(null, 'Subscriber data is empty');
        }

        $data_for_search = [
            'provider_id'     => $provider_id,
            'contact_id'      => wa()->getUser()->getId(),
            'subscriber_data' => is_array($data) ? json_encode($data) : $data,
        ];

        try {
            $psm = new waPushSubscribersModel();
            $subscriber = $psm->getByField($data_for_search);
            if (empty($subscriber)) {
                return $this->displayJson(null, _ws('The specified subscriber is not found.'));
            }

            return $this->displayJson(true);
        } catch (waException $e) {
            return $this->displayJson(null, $e->getMessage());
        }
    }

    public function testSubscriberAction()
    {
        $push_adapters = wa()->getPushAdapters();
        $provider_id = waRequest::post('provider_id', null, waRequest::TYPE_STRING_TRIM);
        if (!$provider_id || !array_key_exists($provider_id, $push_adapters)) {
            return $this->displayJson(null, 'The specified provider is not found');
        }

        $data = waRequest::post('data');
        if (empty($data)) {
            return $this->displayJson(null, 'Subscriber data is empty');
        }

        $data_for_search = [
            'provider_id'     => $provider_id,
            'contact_id'      => wa()->getUser()->getId(),
            'subscriber_data' => is_array($data) ? json_encode($data) : $data,
        ];

        try {
            $psm = new waPushSubscribersModel();
            $subscriber = $psm->getByField($data_for_search);
            if (empty($subscriber)) {
                return $this->displayJson(null, _ws('The specified subscriber is not found. Clear the notifications permissions for your domain in the browser settings.'));
            }

            $push = wa()->getPush();
            if (empty($push) || $push->getId() !== $provider_id || !$push->isEnabled()) {
                return $this->displayJson(null, _ws('The specified web push provider is not enabled.'));
            }

            $res = $push->send($subscriber['id'], [
                'title'   => _ws('Subscription confirmed'),
                'message' => _ws('Now you are subscribed to receive notifications! You can unsubscribe at any time.'),
                'url'     => rtrim(wa()->getConfig()->getHostUrl(), '/').wa()->getConfig()->getBackendUrl(true),
                'data' => [ 'test' => true ],
            ]);
            return $this->displayJson(null, (isset($res['status']) && !$res['status']) ? sprintf_wp(
                'The notification was not sent. Please try again in a few minutes. If the problem persists, try to clear the notifications permissions for your domain in the browser settings (<%s>read more<%s>).',
                sprintf(
                    'a href="%s" target="_blank"',
                    _ws('https://support.webasyst.com/33253/push-notifications/') . '#troubleshooting'
                ),
                '/a'
                ) : null);
        } catch (waException $e) {
            return $this->displayJson(null, $e->getMessage());
        }

    }

    public function initJsAction()
    {
        $this->getResponse()->addHeader('Content-type', 'application/javascript');
        $this->getResponse()->sendHeaders();

        try {
            $push = wa()->getPush();
            if (!$push->isEnabled()) {
                return;
            }
            $init_js = $push->getInitJs();

            $loc = json_encode([
                'requestMessage' => _ws('Enable web push notifications to quickly learn about new events in Webasyst apps.'),
                'buttonText' => _ws('Enable') . ' ›',
                'muteText' => _ws('Do not show again'),
                'thanxMessage' => _ws('Now you are subscribed to receive notifications! You can unsubscribe at any time.'),
                'testButtonText' => _ws('Send test notification') . '&nbsp;›',
                'requestTimeoutMessage' => _ws('If your browser does not offer you to enable notifications, try to clear the notifications permissions for your domain in the browser settings.'),
                'testTimeoutMessage' => sprintf_wp(
                    '<%s>The notification has been successfully sent.<%s> If you do not see it, please try again in a few minutes. If the problem persists, try to clear the notifications permissions for your domain in the browser settings and check the web push notifications sending settings in your account (<%s>read more<%s>).',
                    'strong class="text-strong"',
                    '/strong',
                    sprintf(
                        'a href="%s" target="_blank"',
                        _ws('https://support.webasyst.com/33253/push-notifications/') . '#troubleshooting'
                    ),
                    '/a'
                ),
                'deniedPermission' => _ws('Notifications appear to be disabled in the browser settings. Please enable them to receive web push notifications.'),
                'httpNotSupported' => _ws('Please open this page via HTTPS to set up web push notifications. The HTTP connection is not supported.'),
            ]);

            echo <<<JS_LOC
(function($) { "use strict";

    $.wa_push.loc = $loc;

}(window.jQuery));
JS_LOC;
            echo $init_js;
        } catch (waException $e){}
    }

}
