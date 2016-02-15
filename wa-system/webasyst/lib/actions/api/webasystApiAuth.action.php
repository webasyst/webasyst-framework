<?php

class webasystApiAuthAction extends waViewAction
{
    protected $client_id;
    protected $client_name;
    protected $contact_id;
    protected $response_type;

    protected $required_fields = array(
        'client_id' => true,
        'client_name' => true,
        'response_type' => array('code', 'token'),
        'scope' => true
    );

    public function execute()
    {
        if (!waRequest::isMobile()) {
            $this->setLayout(new webasystLoginLayout());
        }

        $this->response_type = waRequest::get('response_type');
        $this->client_id = waRequest::get('client_id');
        $this->client_name = waRequest::get('client_name');

        if ($this->response_type === 'token') {
            $this->required_fields['redirect_uri'] = true;
        }
        if (!$this->checkRequest()) {
            $this->template = 'ApiError';
            return;
        }

        $this->contact_id = $this->getUser()->getId();

        if (waRequest::method() == 'post') {
            if (waRequest::post('_csrf') != waRequest::cookie('_csrf')) {
                $this->view->assign('error_code', 'invalid_request');
                $this->view->assign('error', 'CSRF Protection');
                $this->template = 'ApiError';
                return;
            }
            if (waRequest::post('approve')) {
                $this->approve();
            } else {
                $this->deny();
            }
        }
        $this->view->assign('client_name', $this->client_name, true);
        $scope = explode(',', waRequest::get('scope'));

        $apps = array();
        foreach ($scope as $app_id) {
            if (wa()->appExists($app_id)) {
                $apps[] = wa()->getAppInfo($app_id);
            }
        }
        $this->view->assign('scope', $apps);
    }

    protected function getTemplate()
    {
        if (!$this->template) {
            $this->template = 'ApiAuth';
        }
        if (waRequest::isMobile()) {
            $this->template .= 'Mobile';
        }
        return parent::getTemplate();
    }

    protected function checkRequest()
    {
        foreach ($this->required_fields as $field => $values) {
            $v = waRequest::get($field);
            if (!$v) {
                $this->view->assign('error_code', 'invalid_request');
                $this->view->assign('error', 'Required parameter is missing: '.$field);
                return false;
            }
            if (is_array($values) && !in_array($v, $values)) {
                $this->view->assign('error_code', 'invalid_request');
                $this->view->assign('error', 'Invalid '.$field.': '.htmlspecialchars($v));
                return false;
            }
        }
        return true;
    }

    protected function approve()
    {
        $url = waRequest::get('redirect_uri');
        if ($this->response_type == 'token') {
            $token_model = new waApiTokensModel();
            $token = $token_model->getToken($this->client_id, $this->contact_id, waRequest::get('scope'));
            $this->redirect($url.'#access_token='.$token);
        } elseif ($this->response_type == 'code') {
            $code = $this->createAuthCode();
            // redirect
            if ($url) {
                $this->redirect($url.(strpos($url, '?') === false ? '?' : '&').'code='.$code);
            }
            // display auth code
            else {
                $this->view->assign('code', $code);
            }
        }
    }

    protected function deny()
    {
        $url = waRequest::get('redirect_uri');
        if ($this->response_type == 'token') {
            $this->redirect($url.'#error=access_denied');
        } else {
            if ($url) {
                $this->redirect($url.(strpos($url, '?') === false ? '?' : '&').'error=access_denied');
            } else {
                $this->template = 'ApiError';
                $this->view->assign('error_code', 'access_denied');
                $this->view->assign('error', "You've denied access to <b>".htmlspecialchars(waRequest::get('client_name')).'</b>');
            }
        }
    }

    protected function createAuthCode()
    {
        $auth_codes_model = new waApiAuthCodesModel();
        $code = md5(microtime(true).uniqid());
        // + 3 min
        $expires = date('Y-m-d H:i:s', time() + 180);
        $auth_codes_model->insert(array(
            'code' => $code,
            'client_id' => $this->client_id,
            'contact_id' => $this->contact_id,
            'scope' => waRequest::get('scope'),
            'expires' => $expires
        ));
        return $code;
    }
}