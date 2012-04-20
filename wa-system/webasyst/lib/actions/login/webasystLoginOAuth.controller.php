<?php 

class webasystLoginOAuthController extends waViewController
{
    public function execute()
    {
        $system = waSystem::getInstance();
        
        $provider = waRequest::get('provider');
        
        $domain = $this->getConfig()->getDomain();
        
        $config = $this->getConfig()->getConfigFile('auth');
        if (isset($config[$domain][$provider])) {
            $params = $config[$domain][$provider];
            $url = $system->getConfig()->getRequestUrl();
            if (isset($params['redirect_uri'])) {
                $params['url'] = $params['redirect_uri'];
            } else {
                $params['url'] = $system->getRootUrl(true).preg_replace("/\\?.*$/", '', $url).
            				 '?provider='.$provider.'&redirect='.urlencode(waRequest::server('HTTP_REFERER'));
            }
            
            $auth = waSystem::getInstance()->getAuth($provider, $params);
            $auth->auth();
        }


        $get = waRequest::get();
        if (isset($get['provider'])) {
            unset($get['provider']);
        }

        if ($get) {
            $this->executeAction(new webasystLoginOAuthAction());
        } else {
            $redirect = waRequest::get('redirect');
            if (!$redirect) {
                $redirect = waSystem::getInstance()->getRootUrl();
            }
            $this->getResponse()->redirect($redirect);
        }
    }
}