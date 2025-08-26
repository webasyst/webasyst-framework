<?php

/**
 * @see: OK & VK ID Documentation
 *
 * https://id.vk.com/about/business/go/docs/ru/vkid/latest/oauth/oauth-ok/server
 * https://apiok.ru/dev/app/create
 *
 */

class okAuth extends waOAuth2Adapter
{
    const OAUTH_URL = 'https://connect.ok.ru/oauth/authorize';
    const TOKEN_URL = 'https://api.ok.ru/oauth/token.do';
    const API_URL = 'https://api.ok.ru/api/';

    public function getRedirectUri()
    {
        $url_params = [
            'client_id' => $this->app_id,
            'response_type' => 'code',
            'redirect_uri' => $this->getCallbackUrl(),
            'scope' => 'VALUABLE_ACCESS;GET_EMAIL',
        ];
        return self::OAUTH_URL.'?'.http_build_query($url_params);
    }

    public function getAccessToken($code)
    {
        $params = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret,
            'redirect_uri' => $this->getCallbackUrl()
        );
        $url = self::TOKEN_URL.'?'.http_build_query($params);
        $response = $this->post($url, [], ['Accept: application/json'], $status);
        if ($status >= 400) {
            waLog::log($this->getId(). ':'. $status. ': '.$response, 'auth.log');
            throw new waAuthException('Error fetching ok access token: '. $response, $status);
        }
        $params = json_decode($response, true);
        return ifempty($params['access_token'], null);
    }

    public function getUserData($token)
    {
        $params = [
            'application_key' => $this->getOption('app_key'),
            'fields' => 'UID,FIRST_NAME,LAST_NAME,NAME,EMAIL,PIC_FULL,BIRTHDAY,GENDER,LOCALE,URL_PROFILE',
            'format' => 'json',
        ];
        $params['sig'] = $this->calculateSig($token, $params);
        $params['access_token'] = $token;
        $url = self::API_URL.'users/getCurrentUser?'.http_build_query($params);
        $response = $this->get($url, $status);
        if ($status >= 400) {
            waLog::log($this->getId(). ':'. $status. ': '.$response, 'auth.log');
            throw new waAuthException('Error fetching ok user info: '. $response, $status);
        }
        $data = json_decode($response, true);
        if (!empty($data['error_code'])) {
            waLog::log($this->getId(). ':'. $data['error_code']. ': '.$data['error_msg'], 'auth.log');
            throw new waAuthException($data['error_msg'], $data['error_code']);
        }

        $result = [
            'source' => 'odnoklassniki',
            'source_id' => $data['uid'],
            'name' => $data['name'],
            'firstname' => $data['first_name'],
            'lastname' => $data['last_name'],
            'url' => $data['url_profile'],
            'locale' => ifset($data['locale']) != 'ru' ? 'en_US' : 'ru_RU',
        ];
        if (!empty($data['email'])) {
            $result['email'] = $data['email'];
        }
        if (!empty($data['pic_full'])) {
            $result['photo_url'] = $data['pic_full'];
        }
        if (!empty($data['birthdaySet']) && !empty($data['birthday'])) {
            $result['birthday'] = $data['birthday'];
        }
        if (!empty($data['gender'])) {
            $result['sex'] = $data['gender'] == 'male' ? 'm' : 'f';
        }
        return $result;
    }

    protected function calculateSig($token, $params)
    {
        ksort($params);
        $paramString = '';
        foreach ($params as $key => $value) {
            if ($key != 'access_token') {
                $paramString .= $key.'='.$value;
            }
        }
        $sig = md5($paramString.md5($token.$this->app_secret));
        return $sig;
    }

    public function getControls()
    {
        return array(
            'app_id' => _ws('Application ID'),
            'app_key' => _ws('Public app key'),
            'app_secret' => _ws('Secret app key'),
        );
    }

    public function getName()
    {
        return 'OK.ru';
    }
}
