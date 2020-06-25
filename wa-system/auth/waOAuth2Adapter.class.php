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

        if ($this->check_state) {
            $state = waRequest::get('state');
            $auth_state = wa()->getStorage()->get('auth_state');
            if (!$state || !$auth_state || $state !== wa()->getStorage()->get('auth_state')) {
                // @todo: error
                return array();
            }
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

    /**
     * URL of auth provider endpoint (to where user will be redirected from webasyst)
     * It is not redirect_uri URL of OAuth protocol
     * @return string
     */
    abstract public function getRedirectUri();

    public function getCode()
    {
        return waRequest::get('code');
    }

    /**
     * This where we call OAuth service again with code to get access token
     * @param $code
     * @return mixed
     */
    abstract public function getAccessToken($code);

    /**
     * Get user data from OAuth provider
     * @param $token
     * @return mixed
     */
    abstract public function getUserData($token);

}
