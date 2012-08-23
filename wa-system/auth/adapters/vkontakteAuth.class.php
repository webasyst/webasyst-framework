<?php

/**
 * @see: http://vk.com/developers.php?oid=-1&p=%D0%90%D0%B2%D1%82%D0%BE%D1%80%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D1%8F_%D1%81%D0%B0%D0%B9%D1%82%D0%BE%D0%B2
 */
class vkontakteAuth extends waOAuth2Adapter
{
    const OAUTH_URL = "https://api.vk.com/oauth/";
    const API_URL = "https://api.vk.com/method/";

    public function getRedirectUri()
    {
        $url = $this->getCallbackUrl();
        // &scope=
        // http://vk.com/developers.php?oid=-1&p=%D0%9F%D1%80%D0%B0%D0%B2%D0%B0_%D0%B4%D0%BE%D1%81%D1%82%D1%83%D0%BF%D0%B0_%D0%BF%D1%80%D0%B8%D0%BB%D0%BE%D0%B6%D0%B5%D0%BD%D0%B8%D0%B9
        return self::OAUTH_URL."authorize?client_id=".$this->app_id."&redirect_uri=".urlencode($url);
    }

    public function getAccessToken($code)
    {
        $url = self::OAUTH_URL."token?client_id=".$this->app_id."&code=".$code."&client_secret=".$this->app_secret;
        $response = $this->get($url);
        return json_decode($response, true);
    }

    public function getUserData($token)
    {
        $url = self::API_URL."users.get?uid={$token['user_id']}&fields=sex,bdate,timezone,photo_medium&access_token={$token['access_token']}";
        $response = $this->get($url);
        if ($response && $response = json_decode($response, true)) {
            $response = $response['response'][0];
            $data = array(
                'source' => 'vkontakte',
                'source_id' => $response['uid'],
                'url' => "http://vk.com/id".$response['uid'],
                'name' => $response['first_name']." ".$response['last_name'],
                'firstname' => $response['first_name'],
                'lastname' => $response['last_name'],
                'photo_url' => $response['photo_medium']
            );
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
        return array();
    }

    public function getName()
    {
        return 'ВКонтакте';
    }
}
