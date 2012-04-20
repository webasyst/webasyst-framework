<?php

class vkontakteAuth extends waAuthAdapter
{
    public function auth()
    {
        $app_id = $this->options['app_id'];
        $app_secret = $this->options['app_secret'];
        $url = $this->options['url'];

        $code = waRequest::get('code');
        $storage = waSystem::getInstance()->getStorage();

        if (!$code) {
            $url = "http://api.vk.com/oauth/authorize?client_id=".$app_id.
                   "&redirect_uri=".urlencode($url);
            waSystem::getInstance()->getResponse()->redirect($url);
        }
        else {
            $url = "https://api.vk.com/oauth/token?client_id={$app_id}&code={$code}&client_secret={$app_secret}";
            $retry = 0;
            do {
                $data = json_decode(file_get_contents($url), true);
            } while (!$data && (++$retry <5));

            $url = "https://api.vk.com/method/getProfiles?uid={$data['user_id']}&access_token={$data['access_token']}";
            $retry = 0;
            do {
                $data = json_decode(file_get_contents($url), true);
            } while (!$data && (++$retry <5));

            if (isset($data['response']) && isset($data['response'][0])) {
                $data = $data['response'][0];
                $user_data = array(
	                'source' => 'vkontakte',
	                'source_id' => $data['uid'],
	                'source_link' => "http://vkontakte.ru/id".$data['uid'],
	                'name' => $data['first_name']." ".$data['last_name'],
	                'firstname' => $data['first_name'],
	                'lastname' => $data['last_name'],
	                'login' => $data['first_name'],
                //	                'locale' => $data['locale']
                );
                if (isset($data['email'])) {
                    $user_data['email'] = $data['email'];
                }
                // save user data
                $storage->set('auth_user_data', $user_data);
            }
        }

    }


    public function getName()
    {
        return 'ВКонтакте';
    }
}
