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
            echo $init_js;
        } catch (waException $e){}
    }
}