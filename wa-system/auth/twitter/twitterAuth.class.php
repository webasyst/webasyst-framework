<?php

class twitterAuth extends waAuthAdapter
{
    public function auth()
    {
        $app_id = $this->options['app_id'];
        $app_secret = $this->options['app_secret'];
        $url_callback = $this->options['url'];

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

            $oauth_verifier = waRequest::get('oauth_verifier');
            $oauth_token = waRequest::get('oauth_token');

            $response = $this->request("oauth/access_token", array(
                'oauth_verifier' => $oauth_verifier,
                'oauth_token' => $oauth_token
            ));

            // access token
            //$response['oauth_token'];
            //$response['oauth_token_secret'];

            $storage->remove('oauth_token');
            $storage->remove('oauth_token_secret');

            $user_data = array(
                'source' => 'twitter',
                'source_id' => $response['user_id'],
                'source_link' => "http://twitter.com/#!/".$response['screen_name'],
                'name' => $response['screen_name'],
                'firstname' => $response['screen_name'],
                'lastname' => $response['screen_name'],
                'login' => $response['screen_name'],
            //                'locale' => $response['locale']
            );

            $storage->set('auth_user_data', $user_data);
        }
    }

    protected function request($command, $params = array())
    {
        $url = "https://api.twitter.com/".$command;
        $app_id = $this->options['app_id'];
        $app_secret = $this->options['app_secret'];
        $url_callback = $this->options['url'];

        $defaults = array(
            "oauth_version" => "1.0",
            "oauth_nonce" => md5(microtime() . mt_rand()),
            "oauth_timestamp" => time(),
            "oauth_consumer_key" => $app_id,
            "oauth_callback" => urlencode($url_callback),
            "oauth_signature_method" => "HMAC-SHA1",
        );

        $params = array_merge($defaults, $params);
        ksort($params);

        $param_pairs = array();
        foreach($params as $k => $v){$param_pairs[] = "{$k}={$v}";}

        $param_string = "POST&".$this->urlencode_rfc3986($url)."&".$this->urlencode_rfc3986(implode('&', $param_pairs));
        $app_secret = $this->urlencode_rfc3986($app_secret)."&";

        $params['oauth_signature'] = base64_encode(hash_hmac('sha1', $param_string, $app_secret, true));

        $url_pairs = array();
        foreach($params as $k => $v){$url_pairs[] = "{$k}={$v}";}

        $retry = 0;
        do {
            $response = $this->post($url, implode('&', $url_pairs));
        } while (!$response && (++$retry <5));



        $params = null;
        parse_str($response, $params);

        return $params;
    }

    protected function urlencode_rfc3986($input)
    {
        return str_replace('+',' ',str_replace('%7E', '~', rawurlencode($input)));
    }

    protected function post($url, $params)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

        $content = curl_exec( $ch );
        curl_close( $ch );

        return $content;
    }
}
