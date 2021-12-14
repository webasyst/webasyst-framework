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
            return;
        }

        if (!wa()->getUser()->get('is_user')) {
            return $this->showError('access_denied', 'Access to API is denied');
        }

        $this->contact_id = $this->getUser()->getId();
        if (waRequest::method() == 'post') {
            if (waRequest::post('_csrf') != waRequest::cookie('_csrf')) {
                return $this->showError('invalid_request', 'CSRF Protection');
            }
            if (waRequest::post('logout')) {
                $this->getUser()->logout();
                if (!class_exists('waLogModel')) {
                    wa('webasyst');
                }
                $log_model = new waLogModel();
                $log_model->insert(array(
                    'app_id' => 'webasyst',
                    'contact_id' => $this->contact_id,
                    'datetime' => date("Y-m-d H:i:s"),
                    'params' => 'api',
                    'action' => 'logout',
                ));
                wa()->getResponse()->redirect(wa()->getConfig()->getRequestUrl(false));
            }
        }

        $apps = array();
        $scope = explode(',', waRequest::get('scope', '', 'string'));
        foreach ($scope as $app_id) {
            if (wa()->appExists($app_id) && wa()->getUser()->getRights($app_id, 'backend')) {
                $apps[$app_id] = wa()->getAppInfo($app_id);
            }
        }

        if (isset($apps['webasyst'])) {
            $apps['webasyst']['icon'] = $apps['webasyst']['header_items']['settings']['icon'];
        }

        if (!$apps) {
            return $this->showError('invalid_request', 'invalid scope');
        }
        $scope = join(',', array_keys($apps));

        if (waRequest::method() == 'post') {
            if (waRequest::post('approve')) {
                $this->approve($scope);
            } else {
                $this->deny();
            }
        }
        $this->view->assign('client_name', $this->client_name, true);
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
                return $this->showError('invalid_request', 'Required parameter is missing: '.$field);
            }
            if (is_array($values) && !in_array($v, $values)) {
                return $this->showError('invalid_request', 'Invalid '.$field.': '.htmlspecialchars($v));
            }
        }
        return true;
    }

    protected function approve($scope)
    {
        $url = waRequest::get('redirect_uri');
        if ($this->response_type == 'token') {
            $token_model = new waApiTokensModel();
            $token = $token_model->getToken($this->client_id, $this->contact_id, $scope);
            $this->redirect($url.'#access_token='.$token);
        } elseif ($this->response_type == 'code') {
            $code = $this->createAuthCode($scope);
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
                return $this->showError('access_denied', "You've revoked API access for <b>".htmlspecialchars(waRequest::get('client_name')).'</b>');
            }
        }
    }

    protected function createAuthCode($scope)
    {
        $auth_codes_model = new waApiAuthCodesModel();
        $code = md5(microtime(true).uniqid());
        // + 3 min
        $expires = date('Y-m-d H:i:s', time() + 180);
        $auth_codes_model->insert(array(
            'code' => $code,
            'client_id' => $this->client_id,
            'contact_id' => $this->contact_id,
            'expires' => $expires,
            'scope' => $scope,
        ));
        return $code;
    }

    protected function showError($code, $description)
    {
        $this->template = 'ApiError';
        $this->view->assign('error_code', $code);
        $this->view->assign('error', $description);
        return false;
    }
}
