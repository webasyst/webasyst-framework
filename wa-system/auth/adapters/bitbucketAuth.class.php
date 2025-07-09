<?php
/**
 * @see: Bitbucket Documentation
 *
 * https://support.atlassian.com/bitbucket-cloud/docs/use-oauth-on-bitbucket-cloud/
 * 
 */

class bitbucketAuth extends waOAuth2Adapter
{
    const OAUTH_URL = 'https://bitbucket.org/site/oauth2';
    const API_URL = 'https://api.bitbucket.org/2.0';

    public function getRedirectUri()
    {
        return self::OAUTH_URL.'/authorize?client_id='.$this->app_id.'&response_type=code&scope=account';
    }

    public function getAccessToken($code)
    {
        $url = self::OAUTH_URL.'/access_token';
        $postfields = array(
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->app_id,
            'client_secret' => $this->app_secret
        );
        $response = $this->post($url, $postfields, ['Accept: application/json'], $status);
        if ($status >= 400) {
            waLog::log($this->getId(). ':'. $status. ': '.$response, 'auth.log');
            throw new waAuthException('Error fetching bitbucket access token: '. $response, $status);
        }
        $params = json_decode($response, true);
        return ifempty($params['access_token'], null);
    }

    public function getUserData($token)
    {
        $url = self::API_URL.'/user';
        $response = $this->get($url, $status, ['Authorization: Bearer '.$token]);
        if (!empty($response) && $data = json_decode($response, true)) {
            if ($status >= 400) {
                waLog::log($this->getId(). ':'. $status. ': '.$response, 'auth.log');
                throw new waAuthException('Error fetching bitbucket user info: '. ifset($data['error_description']), $status);
            }
            $result = array(
                'source' => 'bitbucket',
                'source_id' => $data['uuid'],
                'name' => ifempty($data['display_name'], $data['username']),
                'url' => $data['links']['html']['href'],
            );
            if (!empty($data['email'])) {
                $result['email'] = $data['email'];
            }
            if (!empty($data['links']['avatar']['href'])) {
                $result['photo_url'] = $data['links']['avatar']['href'];
            }
            return $result;
        } elseif ($status >= 400) {
            waLog::log($this->getId(). ':'. $status. ': '.$response, 'auth.log');
            throw new waAuthException('Error fetching bitbucket user info: '. $response, $status);
        }
        return array();
    }

    public function getName()
    {
        return 'Bitbucket';
    }

    public function getControls()
    {
        return array(
            'app_id'     => _ws('Key'),
            'app_secret' => _ws('Secret'),
        );
    }
}
