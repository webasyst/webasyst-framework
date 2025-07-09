<?php
/**
 * @see: GitHub Documentation
 *
 * https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps
 * 
 */

class githubAuth extends waOAuth2Adapter
{
    const API_URL = 'https://api.github.com/';
    const AUTH_URL = 'https://github.com/login/oauth/authorize';
    const TOKEN_URL = 'https://github.com/login/oauth/access_token';

    public function getRedirectUri()
    {
        $redirect_uri = $this->getCallbackUrl();
        return self::AUTH_URL."?client_id=".$this->app_id."&scope=user&redirect_uri=".urlencode($redirect_uri);
    }

    public function getAccessToken($code)
    {
        $redirect_uri = $this->getCallbackUrl();
        $url = self::TOKEN_URL;
        $postfields = array(
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret,
            'code' => $code,
            'redirect_uri' => $redirect_uri
        );
        $response = $this->post($url, $postfields, ['Accept: application/json'], $status);
        if ($status >= 400) {
            waLog::log($this->getId(). ':'. $status. ': '.$response, 'auth.log');
            throw new waAuthException('Error fetching github access token: '. $response, $status);
        }
        $params = json_decode($response, true);
        return ifempty($params['access_token'], null);
    }

    public function getUserData($token)
    {
        $url = self::API_URL.'user';
        $response = $this->get($url, $status, ['Authorization: token '.$token]);
        if (!empty($response) && $data = json_decode($response, true)) {
            if ($status >= 400) {
                waLog::log($this->getId(). ':'. $status. ': '.$response, 'auth.log');
                throw new waAuthException('Error fetching github user info: '. ifset($data['message']), $status);
            }
            $result = [
                'source'    => 'github',
                'source_id' => $data['id'],
                'name'      => ifempty($data['name'], $data['login']),
                'firstname' => ifempty($data['name'], $data['login']),
                'photo_url' => ifset($data['avatar_url']),
                'url'       => ifset($data['html_url']),
            ];
            if (!empty($data['email'])) {
                $result['email'] = $data['email'];
            } elseif (!empty($data['notification_email'])) {
                $result['email'] = $data['notification_email'];
            }
            return $result;
        } elseif ($status >= 400) {
            waLog::log($this->getId(). ':'. $status. ': '.$response, 'auth.log');
            throw new waAuthException('Error fetching github user info: '. $response, $status);
        }
        return [];
    }

    public function getControls()
    {
        return array(
            'app_id'     => _ws('Client ID'),
            'app_secret' => _ws('Client Secret'),
        );
    }

    public function getName()
    {
        return 'GitHub';
    }
}

