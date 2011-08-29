<?php 

class facebookAuth extends waAuthAdapter 
{
    public function auth()
    {
        $app_id = $this->options['app_id'];
        $app_secret = $this->options['app_secret'];
        $url = $this->options['url'];
        
        $code = waRequest::get('code');
        $storage = waSystem::getInstance()->getStorage();
        
        if (!$code) {
            // random state
            $state = md5(uniqid(rand(), TRUE));
            $storage->set('auth_facebook_state', $state); //CSRF protection
            // login dialog url
            $url = "http://www.facebook.com/dialog/oauth?client_id=".$app_id.
                   "&redirect_uri=".urlencode($url)."&state=".$state;
            waSystem::getInstance()->getResponse()->redirect($url);
        }
        
        if (waRequest::get('state') == $storage->get('auth_facebook_state')) {
            // token url
            $url = "https://graph.facebook.com/oauth/access_token?"."client_id=".$app_id. 
            	   "&redirect_uri=".urlencode($url)."&client_secret=".$app_secret."&code=".$code;
            // get oauth token
            $response = file_get_contents($url);
            $params = null;
            parse_str($response, $params);
            $storage->remove('auth_facebook_state');
            // get user data
            $url = "https://graph.facebook.com/me?access_token=".$params['access_token'];
            $data = json_decode(file_get_contents($url), true);
            $user_data = array(
                'source' => 'facebook',
                'source_id' => $data['id'],
                'source_link' => $data['link'],
                'name' => $data['name'],
                'firstname' => $data['first_name'],
                'lastname' => $data['last_name'],
                'login' => $data['username'],
                'locale' => $data['locale']
            ); 
            if (isset($data['email'])) {
                $user_data['email'] = $data['email'];
            }
            // save user data
            $storage->set('auth_user_data', $user_data);
            
            $redirect = waRequest::get('redirect');
            if (!$redirect) {
                $redirect = waSystem::getInstance()->getRootUrl();
            }
            waSystem::getInstance()->getResponse()->redirect($redirect);
        } else {
            throw new waException("The state does not match. You may be a victim of CSRF.");
        }
    }     
    
}
