<?php

/**
 * @see: OK.ru Documentation
 *
 * https://apiok.ru/ext/oauth/
 *
 */

class okruAuth extends waOAuth2Adapter
{
    /**
     * @return array
     */
    public function getControls()
    {
        return array(
            'app_id' => 'ID',
            'app_public' => 'Публичный ключ',
            'app_secret' => 'Секретный ключ'
        );
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return 'http://www.odnoklassniki.ru/oauth/authorize?client_id=' .$this->app_id. '&response_type=code' .
            '&redirect_uri=' .$this->getCallbackUrl();
    }

    /**
     * @param $code
     * @return array|mixed|null
     */
    public function getAccessToken($code)
    {
        $url = 'http://api.odnoklassniki.ru/oauth/token.do';
        $params = array(
            'code' => $code,
            'redirect_uri' => $this->getCallbackUrl(),
            'grant_type' => 'authorization_code',
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret
        );

        $net = new waNet();
        try {
            $response = $net->query($url, $params, waNet::METHOD_POST);
        } catch (Exception $e) {
            waLog::log($e->getMessage(), $this->app_id.'.log');
            return null;
        }
        $params = json_decode($response, true);
        if ($params && isset($params['access_token']) && $params['access_token']) {
            return $params;
        }
        return null;
    }

    /**
     * @param $token
     * @return array
     */
    public function getUserData($token)
    {
        $sign = md5("application_key={$this->options['app_public']}format=jsonmethod=users.getCurrentUser" . md5("{$token['access_token']}{$this->app_secret}"));
        $params = array(
            'method'          => 'users.getCurrentUser',
            'access_token'    => $token['access_token'],
            'application_key' => $this->options['app_public'],
            'format'          => 'json',
            'sig'             => $sign
        );
        $url = 'https://api.ok.ru/fb.do?';
        $url .= urldecode(http_build_query($params));
        $response = $this->get($url);
        if ($response && $response = json_decode($response, true)) {
            $data = array(
                'source' => 'okru',
                'source_id' => $response['uid'],
                'firstname'=> $response['first_name'],
                'lastname'=> $response['last_name'],
                'name' => $response['first_name']. ' ' .$response['last_name'],
            );
            if (isset($response['email'])) {
                $data['email'] = $response['email'];
            }
            if (isset($response['pic_3'])) {
                $data['photo_url'] = $response['pic_3'];
            }
            if (isset($response['birthday'])) {
                $b = explode('-', $response['birthday']);
                if (count($b) === 3) {
                    $data['birthday'] = $b[2].'-'.$b[1].'-'.$b[0];
                }
            }
            return $data;
        }
        return array();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'OK.Ru';
    }
}
