<?php

class facebookAuth extends waOAuth2Adapter
{

    protected $check_state = true;

    const LOGIN_URL = "http://www.facebook.com/dialog/oauth";
    const ACCESS_TOKEN_URL = "https://graph.facebook.com/oauth/access_token";
    const API_URL = "https://graph.facebook.com/";

    public function __construct($options = array())
    {
        $this->options['redirect_uri'] = wa()->getRootUrl(true).'oauth.php?provider='.$this->getId();
        parent::__construct($options);
    }

    public function getRedirectUri()
    {
        // Login dialog url
        $redirect_uri = $this->getOption('redirect_uri');
        return self::LOGIN_URL."?client_id=".$this->app_id."&scope=email&redirect_uri=".urlencode($redirect_uri);
    }

    public function getAccessToken($code)
    {
        // check state
        $redirect_uri = $this->getOption('redirect_uri');
        $url = self::ACCESS_TOKEN_URL."?client_id=".$this->app_id."&client_secret=".$this->app_secret.
            "&redirect_uri=".urlencode($redirect_uri)."&code=".$code;
        $response = $this->get($url);
        $params = null;
        parse_str($response, $params);
        // remove state from session
        wa()->getStorage()->remove('auth_facebook_state');
        if ($params && isset($params['access_token']) && $params['access_token']) {
            return $params['access_token'];
        }
        return null;
    }


    public function getUserData($token)
    {
        // get user data
        $url = "https://graph.facebook.com/me?access_token=".$token."&fields=id,picture,link,first_name,last_name,email,name,locale,gender";
        $response = $this->get($url);
        if ($response && $response = json_decode($response, true)) {
            if (isset($response['error'])) {
                throw new waException($response['error']['message'], $response['error']['code']);
            }
            $data = array(
                'source' => 'facebook',
                'source_id' => $response['id'],
                'url' => $response['link'],
                'name' => $response['name'],
                'firstname' => $response['first_name'],
                'lastname' => $response['last_name'],
                'locale' => $response['locale'],
            );
            if (!empty($response['picture']) && isset($response['picture']['data']['url'])) {
                $data['photo_url'] = "https://graph.facebook.com/me/picture?access_token=".$token."&type=normal";
            }
            if (!empty($response['gender'])) {
                $data['sex'] = $response['gender'] == 'male' ? 'm' : 'f';
            }
            if (isset($response['email'])) {
                $data['email'] = $response['email'];
            }
            return $data;
        }
        return array();
    }

}
