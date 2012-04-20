<?php

class googleAuth extends waAuthAdapter
{
    public function auth()
    {
        $app_id = $this->options['app_id'];
        $app_secret = $this->options['app_secret'];
        $storage = waSystem::getInstance()->getStorage();
        $redirect_uri = preg_replace("/\\?.*$/", '', $this->options['url']).'?provider=google';


        $code = waRequest::get('code');
        $storage = waSystem::getInstance()->getStorage();

        if (!$code) {
            // random state
            $state = md5(uniqid(rand(), TRUE));
            $storage->set('auth_google_state', $state); //CSRF protection
            // login dialog url
            $url = "https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=".$app_id.
                "&scope=https%3A%2F%2Fwww.googleapis.com%2Fauth%2Fuserinfo.profile&approval_prompt=force".
                "&redirect_uri=".urlencode($redirect_uri)."&state=".$state;
            waSystem::getInstance()->getResponse()->redirect($url);
        }

        if (waRequest::get('state') == $storage->get('auth_google_state')) {
            // token url
            $url = 'https://accounts.google.com/o/oauth2/token';
            $params = array(
                'code' => $code,
                'client_id' => $app_id,
                'client_secret' => $app_secret,
                'redirect_uri' => $redirect_uri,
                'grant_type' => 'authorization_code'
            );
            $retry = 0;
            do {
                $response = $this->post($url, $params);
            } while (!$response && (++$retry <2));
            $params = json_decode($response, true);
            $storage->remove('auth_google_state');
            if ($params && isset($params['access_token']) && $params['access_token']) {


                // get user data
                $url = "https://www.googleapis.com/oauth2/v1/userinfo?access_token=".$params['access_token'];
                $retry = 0;
                do {
                    $data = json_decode(file_get_contents($url), true);
                } while (!$data && (++$retry <2));

                if ($data) {
                    $user_data = array(
                        'source' => 'google',
                        'source_id' => $data['id'],
                        'source_link' => $data['link'],
                        'name' => $data['name'],
                        'firstname' => $data['given_name'],
                        'lastname' => $data['family_name']
                    );
                    if (isset($data['locale'])) {
                        $user_data['locale'] = $data['locale'];
                    }
                    if (isset($data['email'])) {
                        $user_data['email'] = $data['email'];
                    }
                    // save user data
                    $storage->set('auth_user_data', $user_data);
                }
            }
        } else {
            throw new waException("The state does not match. You may be a victim of CSRF.");
        }
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
