<?php

abstract class waOAuth2Adapter extends waAuthAdapter
{
    protected $app_id;
    protected $app_secret;
    // CSRF protection (add random state to redirect URI)
    protected $check_state = false;

    public function __construct($options = array())
    {
        parent::__construct($options);
        // @todo: check required options
        $this->app_id = ifempty($this->options['app_id']);
        $this->app_secret = ifempty($this->options['app_secret']);
    }

    public function auth()
    {
        // check code
        $code = $this->getCode();
        if (!$code) {
            $url = $this->getRedirectUri();
            if ($this->check_state) {
                $state = md5(uniqid(rand(), true));
                wa()->getStorage()->set('auth_state', $state);
                $url .= '&state='.$state;
            }
            // redirect to provider auth page
            wa()->getResponse()->redirect($url);
        }

        if ($this->check_state && waRequest::get('state') != wa()->getStorage()->get('auth_state')) {
            // @todo: error
            return array();
        }
        // close session
        wa()->getStorage()->close();
        // get token
        if ($token = $this->getAccessToken($code)) {
            // get user info
            return $this->getUserData($token);
        }
        return array();
    }

    abstract public function getRedirectUri();

    public function getCode()
    {
        return waRequest::get('code');
    }

    abstract public function getAccessToken($code);

    abstract public function getUserData($token);

}