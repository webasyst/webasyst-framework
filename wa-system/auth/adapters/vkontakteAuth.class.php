<?php

/**
 * @see https://id.vk.ru/about/business/go/docs/ru/vkid/latest/vk-id/connection/api-integration/api-description
 */
class vkontakteAuth extends waOAuth2Adapter
{
    const OAUTH_URL = "https://id.vk.ru/";

    protected $check_state = true;

    /**
     * @return string
     * @see http://vk.ru/dev/oauth_dialog
     */
    public function getRedirectUri()
    {
        $url_params = [
            'response_type' => 'code',
            'client_id' => $this->app_id,
            'redirect_uri' => $this->getCallbackUrl(),
            'code_challenge' => $this->generateCodeChallenge(),
            'code_challenge_method' => 'S256',
            'scope' => 'email',
            'lang_id' => (wa()->getLocale() == 'ru_RU' ? '0' : '3'),
        ];
        return self::OAUTH_URL.'authorize?'.http_build_query($url_params);
    }

    public function getControls()
    {
        return [
            'app_id' => _ws('VK app ID'),
        ];
    }

    public function getAccessToken($code)
    {
        $device_id = waRequest::get('device_id');
        $post_params = [
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'client_id'     => $this->app_id,
            'code_verifier' => $this->getCodeVerifier(),
            'device_id'     => $device_id,
            'redirect_uri'  => $this->getCallbackUrl(),
        ];

        $response = $this->post(self::OAUTH_URL.'oauth2/auth', $post_params, [], $status);
        if (!$response) {
            waLog::log($this->getId(). ":'. $status. ': Can't get access token from VK", 'auth.log');
            throw new waAuthException("Can't get access token from VK", $status ? $status : 500);
        }
        $response = json_decode($response, true);
        if (isset($response['error']) && !isset($response['access_token'])) {
            waLog::log($this->getId(). ':'. $status. ': '.$response['error']." (".$response['error_description'].')', 'auth.log');
            throw new waAuthException($response['error']." (".$response['error_description'].')', $status ? $status : 500);
        }
        return $response;
    }

    public function getUserData($token)
    {
        $post_params = [
            'client_id' => $this->app_id,
            'access_token' => $token['access_token'],
        ];
        $response = $this->post(self::OAUTH_URL.'oauth2/user_info', $post_params, ['Accept-Language: ru,en-us'], $status);
        if ($response && $response = json_decode($response, true)) {
            if (isset($response['error'])) {
                waLog::log($this->getId(). ':'. $status. ': Error '.$response['error']." (".$response['error_description'].')', 'auth.log');
                throw new waAuthException($response['error_description'], $status ? $status : 500);
            }
            $response = ifset($response['user']);
            if ($response) {
                $data = [
                    'source'                  => 'vkontakte',
                    'source_id'               => $response['user_id'],
                    'socialnetwork.vkontakte' => 'id'.$response['user_id'],
                    'url'                     => 'https://vk.com/id'.$response['user_id'],
                    'name'                    => trim(ifset($response['first_name'], '')." ".ifset($response['last_name'], '')),
                    'firstname'               => ifset($response['first_name'], ''),
                    'lastname'                => ifset($response['last_name'], ''),
                ];
                if (!empty($response['email'])) {
                    $data['email'] = $response['email'];
                }
                if (!empty($response['phone'])) {
                    $data['phone.home'] = $response['phone'];
                }
                if (!empty($response['sex'])) {
                    $data['sex'] = $response['sex'] == 2 ? 'm' : 'f';
                }
                if (!empty($response['birthday'])) {
                    $b = explode('.', $response['birthday']);
                    if (count($b) == 3) {
                        $data['birthday'] = $b[2].'-'.$b[1].'-'.$b[0];
                    }
                }
                if (!empty($response['avatar'])) {
                    $url_parts = parse_url($response['avatar']);
                    parse_str(ifempty($url_parts['query'], ''), $query);
                    if (!empty($query['as'])) {
                        $sizes = explode(',', $query['as']);
                        $sizes = array_map(function($el) {
                            $el = explode('x', $el);
                            return intval($el[0]);
                        }, $sizes);
                        $max_size = max($sizes);
                        $query['cs'] = $max_size . 'x' . $max_size;
                        $data['photo_url'] = $url_parts['scheme'].'://'.$url_parts['host'].$url_parts['path'].'?'.http_build_query($query);
                    } else {
                        $data['photo_url'] = $response['avatar'];
                    }
                }
                return $data;
            }
        }
        waLog::log($this->getId(). ':'. $status. ': '."Can't get user info from VK API", 'auth.log');
        throw new waAuthException("Can't get user info from VK API", $status ? $status : 500);
    }

    public function getName()
    {
        return wa()->getLocale() == 'en_US' ? 'VK' : 'VK';
    }

    protected function generateCodeChallenge()
    {
        $code_verifier = $this->generateCodeVerifier(128);
        return waUtils::urlSafeBase64Encode(hash('sha256', $code_verifier, true));
    }

    protected function getCodeVerifier()
    {
        return wa()->getStorage()->get(get_class($this) . '/code_verifier');
    }

    protected function generateCodeVerifier($length = 48)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890-_';
        $numChars = strlen($chars);
        $code_verifier = '';
        for ($i = 0; $i < $length; $i++) {
            $ch = substr($chars, mt_rand(1, $numChars) - 1, 1);
            $code_verifier .= $ch;
        }
        wa()->getStorage()->set(get_class($this) . '/code_verifier', $code_verifier);
        return $code_verifier;
    }
}
