<?php

class twitterAuth extends waAuthAdapter
{
    const API_VERSION = '1.1';
    const API_HOST = 'https://api.twitter.com';

    protected $oauth_token;
    protected $oauth_token_secret;

    public function getControls()
    {
        return array(
            'app_id' => 'Consumer key',
            'app_secret' => 'Consumer secret'
        );
    }

    protected function oauth($path, array $params = array())
    {
        $url = sprintf('%s/%s', self::API_HOST, $path);
        $response = $this->oAuthRequest($url, 'POST', $params);
        parse_str($response, $result);
        return $result;
    }

    protected function oAuthRequest($url, $method, array $params)
    {
        $defaults = array(
            "oauth_version" => '1.0',
            "oauth_nonce" => md5(microtime() . mt_rand()),
            "oauth_timestamp" => time(),
            "oauth_consumer_key" => $this->options['app_id']
        );
        if (null !== $this->oauth_token) {
            $defaults['oauth_token'] = $this->oauth_token;
        }
        $data = array_merge($defaults, $params);

        // sign data

        $data['oauth_signature_method'] = "HMAC-SHA1";
        $data['oauth_signature'] = $this->getSignature($url, $method, $data);


        if (array_key_exists('oauth_callback', $params)) {
            // Twitter doesn't like oauth_callback as a parameter.
            unset($params['oauth_callback']);
        }
        $authorization = $this->getAuthHeader($data);
        return $this->request($url, $method, $authorization, $params);
    }

    protected function getSignature($url, $method, $params)
    {
        $parts = array(
            strtoupper($method),
            strtolower($url),
            self::buildHttpQuery($params)
        );

        $parts = self::urlencodeRfc3986($parts);
        $str = implode('&', $parts);

        $parts = array($this->options['app_secret'], null !== $this->oauth_token ? $this->oauth_token_secret : "");
        $parts = self::urlencodeRfc3986($parts);
        $key = implode('&', $parts);

        return base64_encode(hash_hmac('sha1', $str, $key, true));
    }

    protected function getAuthHeader($params)
    {
        $first = true;
        $result = 'Authorization: OAuth';
        foreach ($params as $k => $v) {
            if (substr($k, 0, 5) != "oauth") {
                continue;
            }
            $result .= ($first) ? ' ' : ', ';
            $result .= self::urlencodeRfc3986($k) . '="' . self::urlencodeRfc3986($v) . '"';
            $first = false;
        }
        return $result;
    }

    protected static function buildHttpQuery($params)
    {
        if (!$params) {
            return '';
        }

        // Urlencode both keys and values
        $keys = self::urlencodeRfc3986(array_keys($params));
        $values = self::urlencodeRfc3986(array_values($params));
        $params = array_combine($keys, $values);

        // Parameters are sorted by name, using lexicographical byte value ordering.
        // Ref: Spec: 9.1.1 (1)
        uksort($params, 'strcmp');

        $pairs = array();
        foreach ($params as $parameter => $value) {
            if (is_array($value)) {
                // If two or more parameters share the same name, they are sorted by their value
                // Ref: Spec: 9.1.1 (1)
                // June 12th, 2010 - changed to sort because of issue 164 by hidetaka
                sort($value, SORT_STRING);
                foreach ($value as $duplicateValue) {
                    $pairs[] = $parameter . '=' . $duplicateValue;
                }
            } else {
                $pairs[] = $parameter . '=' . $value;
            }
        }
        // For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
        // Each name-value pair is separated by an '&' character (ASCII code 38)
        return implode('&', $pairs);
    }

    protected static function urlencodeRfc3986($var)
    {
        if (is_array($var)) {
            return array_map(array(__CLASS__ , 'urlencodeRfc3986'), $var);
        } elseif (is_scalar($var)) {
            return rawurlencode($var);
        } else {
            return '';
        }
    }

    protected function request($url, $method, $authorization, $postfields)
    {
        /* Curl settings */
        $options = array(
            // CURLOPT_VERBOSE => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => array('Accept: application/json', $authorization, 'Expect:'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => 'TwitterOAuth',
            CURLOPT_ENCODING => 'gzip',
        );

        switch ($method) {
            case 'GET':
                if (!empty($postfields)) {
                    $options[CURLOPT_URL] .= '?' . self::buildHttpQuery($postfields);
                }
                break;
            case 'POST':
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_POSTFIELDS] = self::buildHttpQuery($postfields);
                break;
        }

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        if (!$response) {
            waLog::log('Twitter: ERROR '.curl_errno($ch).', HTTP CODE: '.curl_getinfo($ch, CURLINFO_HTTP_CODE), 'oauth.log');
        }

        return $response;
    }

    public function auth()
    {
        $storage = waSystem::getInstance()->getStorage();

        if (!waRequest::get('oauth_verifier')) {

            $response = $this->oauth("oauth/request_token", array('oauth_callback' => $this->getCallbackUrl()));

            $storage->set('oauth_token', $response['oauth_token']);
            $storage->set('oauth_token_secret', $response['oauth_token_secret']);

            $url = self::API_HOST."/oauth/authorize?oauth_token=".$response['oauth_token'];

            waSystem::getInstance()->getResponse()->redirect($url);
        }
        else {
            if ( waRequest::get('oauth_token') != $storage->get('oauth_token' ) ) {
                throw new waException(_w("Old token"));
            }

            // get access token
            $token = $this->oauth('oauth/access_token',  array(
                'oauth_verifier' => waRequest::get('oauth_verifier'),
                'oauth_token' => waRequest::get('oauth_token')
            ));

            $this->oauth_token = $token['oauth_token'];
            $this->oauth_token_secret = $token['oauth_token_secret'];

            $response = $this->oAuthRequest(self::API_HOST."/1.1/account/verify_credentials.json", 'GET', array('skip_status' => 1));

            $response = json_decode($response, true);

            $storage->remove('oauth_token');
            $storage->remove('oauth_token_secret');

            $data = array(
                'source' => 'twitter',
                'source_id' => $response['id_str'],
                'url' => "http://twitter.com/#!/".$response['screen_name'],
                'name' => $response['name'],
                'about' => $response['description'],
                'photo_url' => $response['profile_image_url']
            );

            if (isset($response['lang']) && $response['lang'] == 'ru') {
                $data['locale'] = 'ru_RU';
            }

            $name = explode(' ', $response['name'], 2);
            if (count($name) == 1) {
                $data['firstname'] = $data['name'];
                $data['lastname'] = '';
            } else {
                $data['firstname'] = $name[0];
                $data['lastname'] = $name[1];
            }

            return $data;
        }
        return array();
    }
}
