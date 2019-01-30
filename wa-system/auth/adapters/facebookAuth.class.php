<?php

class facebookAuth extends waOAuth2Adapter
{
    protected $check_state = true;

    const LOGIN_URL = "http://www.facebook.com/v2.9/dialog/oauth";
    const API_URL   = "https://graph.facebook.com/v2.9/";

    public function __construct($options = array())
    {
        $this->options['redirect_uri'] = wa()->getRootUrl(true).'oauth.php?provider='.$this->getId();
        parent::__construct($options);
    }

    public function getControls()
    {
        return array(
            'app_id'     => _ws('Facebook app ID'),
            'app_secret' => _ws('Facebook app secret'),
        );
    }

    public function getRedirectUri()
    {
        // Login dialog url
        $redirect_uri = $this->getOption('redirect_uri');
        return self::LOGIN_URL."?client_id=".$this->app_id."&display=popup&scope=email&redirect_uri=".urlencode($redirect_uri);
    }

    public function getAccessToken($code)
    {
        // check state
        $redirect_uri = $this->getOption('redirect_uri');
        $url = self::API_URL."oauth/access_token?client_id=".$this->app_id."&client_secret=".$this->app_secret.
            "&redirect_uri=".urlencode($redirect_uri)."&code=".$code;
        $response = $this->get($url);
        $params = json_decode($response, true);
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
        $url = self::API_URL."me?access_token=".$token."&fields=id,picture,link,first_name,last_name,email,name,locale,gender";
        $response = $this->get($url);
        if ($response && $response = json_decode($response, true)) {
            if (isset($response['error'])) {
                waLog::dump(
                    'Error fetching facebook user info by token',
                    $response,
                    'auth.log'
                );
                return array();
            }
            $data = array(
                'source'    => 'facebook',
                'source_id' => $response['id'],
                'url'       => $response['link'],
                'name'      => $response['name'],
                'firstname' => $response['first_name'],
                'lastname'  => $response['last_name'],
                'locale'    => $response['locale'],
            );
            if (!empty($response['picture']) && isset($response['picture']['data']['url'])) {
                $data['photo_url'] = self::API_URL."me/picture?access_token=".$token."&type=normal";
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
