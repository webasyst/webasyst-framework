<?php 

class webasystLoginOAuthController extends waController
{
    public function execute()
    {
        $system = waSystem::getInstance();
        
        $provider = waRequest::get('provider');
        
        $domain = $this->getConfig()->getDomain();
        
        $config = $this->getConfig()->getConfigFile('config', 'auth');
        if (isset($config[$domain][$provider])) {
            $params = $config[$domain][$provider];
            $url = $system->getConfig()->getRequestUrl();
            $params['url'] = $system->getRootUrl(true).preg_replace("/\\?.*$/", '', $url).
            				 '?provider='.$provider.'&redirect='.urlencode(waRequest::server('HTTP_REFERER'));
            
            $auth = waSystem::getInstance()->getAuth($provider, $params);
            $auth->auth();
        }
        
        // redirect to main page
        $this->getResponse()->redirect(waSystem::getInstance()->getRootUrl());
    }
}