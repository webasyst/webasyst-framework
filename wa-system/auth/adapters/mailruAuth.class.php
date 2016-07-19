<?php

/**
 * @see: Mail.ru Documentation
 *
 * http://api.mail.ru/docs/guides/oauth/sites/
 *
 */

class mailruAuth extends waOAuth2Adapter
{

    public function getControls()
    {
        return array(
            'app_id' => 'ID',
            'app_private' => 'Приватный ключ',
            'app_secret' => 'Секретный ключ',
        );
    }

    public function getRedirectUri()
    {
        return "https://connect.mail.ru/oauth/authorize?client_id=".$this->app_id."&response_type=code".
               "&redirect_uri=".$this->getCallbackUrl();
    }

    public function getAccessToken($code)
    {
        $url = "https://connect.mail.ru/oauth/token";
        $response = $this->post($url, array(
            "grant_type" => "authorization_code",
            "code" => $code,
            "client_id" => $this->app_id,
            "client_secret" => $this->app_secret,
            "redirect_uri" => $this->getCallbackUrl()
        ));
        $params = json_decode($response, true);
        if ($params && isset($params['access_token']) && $params['access_token']) {
            return $params;
        }
        return null;
    }

    protected function getSign(array $request_params, $uid)
    {
        ksort($request_params);
        $params = '';
        foreach ($request_params as $key => $value) {
            $params .= "$key=$value";
        }
        return md5($uid . $params . $this->options['app_private']);
    }

    public function getUserData($token)
    {
        $params = array(
            'method' => 'users.getInfo',
            'app_id' => $this->app_id,
            'session_key' => $token['access_token'],
            'uids' => $token['x_mailru_vid']
        );
        $url = "http://www.appsmail.ru/platform/api?";
        foreach($params as $k => $v) {
            $url .= $k.'='.$v.'&';
        }
        $url .= 'sig='.$this->getSign($params, $token['x_mailru_vid']);
        $response = $this->get($url);
        if ($response && $response = json_decode($response, true)) {
            $response = $response[0];
            $data = array(
                'source' => 'mailru',
                'source_id' => $response['uid'],
                'url' => $response['link'],
                'firstname'=> $response['first_name'],
                'lastname'=> $response['last_name'],
                'name' => $response['first_name']." ".$response['last_name'],
            );
            if (isset($response['email'])) {
                $data['email'] = $response['email'];
            }
            return $data;
        }
        return array();
    }

    public function getName()
    {
        return 'Mail.Ru';
    }

}
