<?php

class siteFrontend 
{
    protected $domain;
    protected $domain_id;
    
    public function getPage($url = null)
    {
        if ($url === null) {
            $url = wa()->getRouting()->getCurrentUrl();
        }
        $route = wa()->getRouting()->getRoute('url');
        $page_model = new sitePageModel();
        if (substr($url, -1) !== '/' && strpos(substr($url, -5), '.') === false) {
            if ($page = $page_model->getByUrl($this->getDomainId(), $route, $url.'/')) {
                $url = waSystem::getInstance()->getConfig()->getRequestUrl(false);
                if (($i = strpos($url, '?')) === false) {
                    wa()->getResponse()->redirect($url.'/');
                } else {
                    wa()->getResponse()->redirect(substr($url, 0, $i).'/'.substr($url, $i));
                }
            }
        }
        $page = $page_model->getByUrl($this->getDomainId(), $route, $url);
        if (!$page) {
            return array();
        }
        if (!$page['status']) {
            $app_settings_model = new waAppSettingsModel();
            $hash = $app_settings_model->get('site', 'preview_hash');
            if (!$hash || md5($hash) != waRequest::get('preview')) {
                return array();
            }
        }
        $params_model = new sitePageParamsModel();
        if ($params = $params_model->getById($page['id'])) {
            $page += $params;
        }
        if (!$page['title']) {
            $page['title'] = $page['name'];
        }
        //$page['url'] = wa()->getAppUrl().$page['url'];
        foreach ($page as $k => $v) {
            if ($k != 'content') {
                $page[$k] = htmlspecialchars($v); 
            }
        }
        return $page;
    }
    
    protected function getDomainId()
    {
        if (!$this->domain_id) {
            $domain = $this->getDomain();
            $domain_model = new siteDomainModel();
            if ($d = $domain_model->getByName($domain)) {
                $this->domain_id = $d['id'];
            } else {
                if (substr($domain, 0, 4) == 'www.') {
                    $domain = substr($domain, 4);
                } else {
                    $domain = 'www.'.$domain;
                }
                if ($d = $domain_model->getByName($domain)) {
                    $this->domain_id = $d['id'];
                }
            }
        }
        return $this->domain_id;
    }
    
    protected function getDomain() 
    {
        if (!$this->domain) {
            $this->domain = waSystem::getInstance()->getRouting()->getDomain();
        }
        return $this->domain;        
    }
}