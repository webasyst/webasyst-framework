<?php

/**
 * @see http://vk.com/dev/auth_sites
 */
class vkontakteAuth extends waOAuth2Adapter
{
    const OAUTH_URL = "https://oauth.vk.com/";
    const API_URL = "https://api.vk.com/method/";
    const API_VERSION = '5.21';

    /**
     * @return string
     * @see http://vk.com/dev/oauth_dialog
     */
    public function getRedirectUri()
    {
        $url = $this->getCallbackUrl();
        return self::OAUTH_URL."authorize?client_id=".$this->app_id."&scope=email&response_type=code&redirect_uri=".urlencode($url).'&v='.self::API_VERSION;
    }

    public function getControls()
    {
        return array(
            'app_id' => 'ID приложения',
            'app_secret' => 'Защищенный ключ'
        );
    }

    public function getAccessToken($code)
    {
        $url = self::OAUTH_URL."token?client_id=".$this->app_id."&code=".$code."&client_secret=".$this->app_secret."&redirect_uri=".urlencode($this->getCallbackUrl());
        $response = $this->get($url, $status);
        if (!$response) {
            waLog::log($this->getId(). ':'. $status. ': '."Can't get access token from VK", 'auth.log');
            throw new waException("Can't get access token from VK", $status ? $status : 500);
        }
        $response = json_decode($response, true);
        if (isset($response['error']) && !isset($response['access_token'])) {
            waLog::log($this->getId(). ':'. $status. ': '.$response['error']." (".$response['error_description'].')', 'auth.log');
            throw new waException($response['error']." (".$response['error_description'].')', $status ? $status : 500);
        }
        return $response;
    }

    public function getUserData($token)
    {
        $url = self::API_URL."users.get?uid={$token['user_id']}&fields=contacts,sex,bdate,timezone,photo_medium&access_token={$token['access_token']}";
        $response = $this->get($url, $status);
        if ($response && $response = json_decode($response, true)) {
            if (isset($response['error'])) {
                waLog::log($this->getId(). ':'. $status. ': Error '.$response['error']['error_code']." (".$response['error']['error_msg'].')', 'auth.log');
                throw new waException($response['error']['error_msg'], $response['error']['error_code']);
            }
            $response = $response['response'][0];
            if ($response) {
                $data = array(
                    'source' => 'vkontakte',
                    'source_id' => $response['uid'],
                    'url' => "http://vk.com/id".$response['uid'],
                    'name' => $response['first_name']." ".$response['last_name'],
                    'firstname' => $response['first_name'],
                    'lastname' => $response['last_name'],
                    'photo_url' => $response['photo_medium']
                );
                if (!empty($token['email'])) {
                    $data['email'] = $token['email'];
                }
                if ($response['home_phone']) {
                    $data['phone.home'] = $response['home_phone'];
                }
                if ($response['sex']) {
                    $data['sex'] = $response['sex'] == 2 ? 'm' : 'f';
                }
                if ($response['bdate']) {
                    $b = explode('.', $response['bdate']);
                    if (count($b) == 3) {
                        $data['birthday'] = $b[2].'-'.$b[1].'-'.$b[0];
                    }
                }
                return $data;
            }
        }
        waLog::log($this->getId(). ':'. $status. ': '."Can't get user info from VK API", 'auth.log');
        throw new waException("Can't get user info from VK API", $status ? $status : 500);
    }

    public function getName()
    {
        return wa()->getLocale() == 'en_US' ? 'VK' : 'ВКонтакте';
    }
}
