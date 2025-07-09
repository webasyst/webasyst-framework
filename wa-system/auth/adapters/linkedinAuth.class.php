<?php

/**
 * @see
 * https://learn.microsoft.com/en-us/linkedin/shared/authentication/authorization-code-flow?tabs=HTTPS1
 * https://developer.linkedin.com/
 */

class linkedinAuth extends waOAuth2Adapter
{
    const API_URL = 'https://api.linkedin.com/v2/';
    const OAUTH_URL = 'https://www.linkedin.com/oauth/v2/';

    public function getControls()
    {
        return array(
            'app_id'     => _ws('Client ID'),
            'app_secret' => _ws('Client secret'),
        );
    }

    public function getRedirectUri()
    {
        $url_params = [
            'response_type' => 'code',
            'client_id' => $this->app_id,
            'redirect_uri' => $this->getCallbackUrl(),
            'scope' => 'r_basicprofile r_emailaddress',
        ];
        return self::OAUTH_URL.'authorization?'.http_build_query($url_params);
    }

    public function getAccessToken($code)
    {
        $post_params = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $this->app_id,
            'client_secret' => $this->app_secret,
            'redirect_uri'  => $this->getCallbackUrl(),
        ];
        $url = self::OAUTH_URL.'accessToken';
        $response = $this->post($url, $post_params, [], $status);
        if (!$response) {
            waLog::log($this->getId(). ":'. $status. ': Can't get access token from LinkedIn", 'auth.log');
            throw new waAuthException("Can't get access token from LinkedIn", $status ? $status : 500);
        }
        if ($status >= 400) {
            waLog::log($this->getId(). ":'. $status. ': Can't get access token from LinkedIn: ".$response, 'auth.log');
            throw new waAuthException("Can't get access token from LinkedIn: ".$response, $status ? $status : 500);
        }

        $params = json_decode($response, true);
        if ($params && isset($params['access_token']) && $params['access_token']) {
            return $params['access_token'];
        }
        return null;
    }

    public function getUserData($token)
    {
        $url = self::API_URL.'me/';
        $response = $this->get($url, $status, ['Authorization: Bearer '.$token]);
        if ($response && $response = json_decode($response, true)) {
            if (isset($response['errorCode'])) {
                waLog::log(
                    'Error fetching LinkedIn user info by token: '.$response,
                    'auth.log'
                );
                return array();
            }
            $data = array(
                'source'    => 'linkedin',
                'source_id' => $response['id'],
                'name'      => trim($response['localizedFirstName'].' '.$response['localizedLastName']),
                'firstname' => $response['localizedFirstName'],
                'lastname'  => $response['localizedLastName'],
                'url'       => 'https://www.linkedin.com/in/'.$response['vanityName'],
            );
            if (isset($response['emailAddress'])) {
                $data['email'] = $response['emailAddress'];
            }
            if (!empty($response['profilePicture']['displayImage'])) {
                $data['photo_url'] = str_replace('urn:li:digitalmediaAsset:', 'https://media.licdn.com/dms/image/', $response['profilePicture']['displayImage']);
            }
            return $data;
        }
        return array();
    }

    public function getName()
    {
        return 'LinkedIn';
    }
}
