<?php

/**
 * @see https://code.google.com/apis/console/
 */
class googleAuth extends waOAuth2Adapter
{

    protected $check_state = true;

    public function getControls()
    {
        return array(
            'app_id'     => _ws('Google app ID'),
            'app_secret' => _ws('Google app secret'),
        );
    }

    public function getRedirectUri()
    {
        $scope = "https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile";
        // login dialog url
        return "https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=".$this->app_id.
            "&scope=".urlencode($scope)."&approval_prompt=force".
            "&redirect_uri=".urlencode($this->getCallbackUrl());
    }

    public function getAccessToken($code)
    {
        $url = 'https://accounts.google.com/o/oauth2/token';
        $response = $this->post($url, array(
            'code' => $code,
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret,
            'redirect_uri' => $this->getCallbackUrl(),
            'grant_type' => 'authorization_code'
        ));
        $params = json_decode($response, true);
        wa()->getStorage()->remove('auth_google_state');
        if ($params && isset($params['access_token']) && $params['access_token']) {
            return $params['access_token'];
        }
        return null;
    }

    public function getUserData($token)
    {
        $url = "https://www.googleapis.com/oauth2/v1/userinfo?access_token=".$token;
        $response = $this->get($url);

        if ($response && $response = json_decode($response, true)) {
            $data = array(
                'source' => 'google',
                'source_id' => $response['id'],
                'url' => $response['link'],
                'name' => $response['name'],
                'firstname' => $response['given_name'],
                'lastname' => $response['family_name']
            );
            if (isset($response['locale'])) {
                if ($response['locale'] == 'ru') {
                    $response['locale'] = 'ru_RU';
                } elseif ($response['locale'] == 'en') {
                    $response['locale'] = 'en_US';
                }
                $data['locale'] = $response['locale'];
            }
            if (isset($response['email'])) {
                $data['email'] = $response['email'];
            }
            return $data;
        }
        return array();
    }

}
