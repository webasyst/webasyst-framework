<?php

class twitterAuth extends waAuthAdapter
{
    public function auth()
    {
        $storage = waSystem::getInstance()->getStorage();

        if (!waRequest::get('oauth_verifier')) {

            $response = $this->request("oauth/request_token");

            $storage->set('oauth_token', $response['oauth_token']);
            $storage->set('oauth_token_secret', $response['oauth_token_secret']);

            $url = "https://api.twitter.com/oauth/authorize?oauth_token=".$response['oauth_token'];

            waSystem::getInstance()->getResponse()->redirect($url);
        }
        else {
            if ( waRequest::get('oauth_token') != $storage->get('oauth_token' ) ) {
                throw new waException(_w("Old token"));
            }

            // get access token
            $access_token = $this->request("oauth/access_token", array(
                'oauth_verifier' => waRequest::get('oauth_verifier'),
                'oauth_token' => waRequest::get('oauth_token')
            ));
            // get user info
            $response = $this->request("1/account/verify_credentials.json", array(
                'oauth_token' => $access_token['oauth_token'],
                'oauth_token_secret' => $access_token['oauth_token_secret'],
                'skip_status' => 1
            ), 'GET');

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

    protected function request($command, $params = array(), $method = 'POST')
    {
        $url = "https://api.twitter.com/".$command;
        $app_id = $this->options['app_id'];
        $app_secret = $this->options['app_secret'];

        $defaults = array(
            "oauth_version" => "1.0",
            "oauth_nonce" => md5(microtime() . mt_rand()),
            "oauth_timestamp" => time(),
            "oauth_consumer_key" => $app_id,
            "oauth_callback" => urlencode($this->getCallbackUrl()),
            "oauth_signature_method" => "HMAC-SHA1",
        );

        $params = array_merge($defaults, $params);
        ksort($params);

        $param_pairs = array();
        foreach($params as $k => $v){$param_pairs[] = "{$k}={$v}";}

        $param_string = $method."&".$this->urlencode_rfc3986($url)."&".$this->urlencode_rfc3986(implode('&', $param_pairs));
        $app_secret = $this->urlencode_rfc3986($app_secret)."&";

        $params['oauth_signature'] = base64_encode(hash_hmac('sha1', $param_string, $app_secret, true));

        $url_pairs = array();
        foreach($params as $k => $v){
            $url_pairs[] = "{$k}={$v}";
        }

        $retry = 0;
        do {
            if ($method == 'POST') {
                $response = $this->post($url, implode('&', $url_pairs));
            } else {
                $response = $this->get($url.'?'.implode('&', $url_pairs));
            }
        } while (!$response && (++$retry <5));


        $params = null;
        parse_str($response, $params);

        return $params;
    }

    protected function urlencode_rfc3986($input)
    {
        return str_replace('+',' ',str_replace('%7E', '~', rawurlencode($input)));
    }
}
