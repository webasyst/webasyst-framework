<?php

/**
 * @see: Yandex Documentation
 *
 * http://api.yandex.ru/oauth/doc/dg/concepts/About.xml
 * http://api.yandex.ru/login/doc/dg/concepts/about.xml
 */

class yandexAuth extends waOAuth2Adapter
{
    protected $check_state = true;

    public function getRedirectUri()
    {
        return "https://oauth.yandex.ru/authorize?response_type=code&client_id=".$this->app_id;
    }

    public function getControls()
    {
        return array(
            'app_id'     => _ws('Yandex app ID'),
            'app_secret' => _ws('Yandex app secret'),
        );
    }

    public function getAccessToken($code)
    {
        $url = "https://oauth.yandex.ru/token";
        $response = $this->post($url, array(
            "grant_type" => "authorization_code",
            "code" => $code,
            "client_id" => $this->app_id,
            "client_secret" => $this->app_secret,
        ));
        $params = json_decode($response, true);
        if ($params && isset($params['access_token']) && $params['access_token']) {
            return $params['access_token'];
        }
        return null;
    }

    public function getUserData($token)
    {
        $url = "https://login.yandex.ru/info?format=json&oauth_token=".$token;
        $response = $this->get($url);
        if ($response && $response = json_decode($response, true)) {
            $data = array(
                'source' => 'yandex',
                'source_id' => $response['id'],
                'url' => 'http://'.$response['display_name'].'.ya.ru',
                'name' => $response['real_name'],
            );
            if (isset($response['first_name'])) {
                $data['firstname'] = $response['first_name'];
            }
            if (isset($response['last_name'])) {
                $data['lastname'] = $response['last_name'];
            }
            if (!isset($data['firstname']) && !isset($data['lastname'])) {
                $name = explode(' ', $response['real_name'], 3);
                if (count($name) == 1) {
                    $data['firstname'] = $name[0];
                    $data['lastname'] = '';
                } else {
                    $data['firstname'] = $name[0];
                    $data['lastname'] = $name[1];
                }
            }
            if (isset($response['default_email'])) {
                $data['email'] = $response['default_email'];
            }
            return $data;
        }
        return array();
    }

    public function getName()
    {
        if (wa()->getLocale() == 'ru_RU') {
            return 'Яндекс';
        } else {
            return parent::getName();
        }
    }
}
