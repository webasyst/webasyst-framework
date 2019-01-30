<?php

class sitePersonalAppAction extends sitePersonalAction
{
    public function execute()
    {
        $app_id = waRequest::get('app_id');
        $app = wa()->getAppInfo($app_id);
        $this->view->assign('items', $this->getItems($app_id));
        $this->view->assign('app', $app);

        $domain = siteHelper::getDomain();
        $routes = wa()->getRouting()->getByApp($app_id, $domain);

        if ($routes) {
            $event_params = array('app_id' => $app_id, 'domain' => $domain, 'routes' => $routes);
            $this->setLocale($app_id);
            $result = wa($app_id)->event(array($app_id, 'personal.settings'), $event_params);
            $this->setLocale('site');
            if (!empty($result[$app_id])) {
                $this->view->assign('personal_settings', $result[$app_id]);
            }

            $domain = siteHelper::getDomain();
            $domain_config_path = $this->getConfig()->getConfigPath('domains/' . $domain . '.php');
            if (file_exists($domain_config_path)) {
                $domain_config = include($domain_config_path);
            } else {
                $domain_config = array();
            }
        }

        $this->view->assign('domain_url', 'http://'.$domain);

        $this->view->assign('settled', $routes ? true: false);
        $this->view->assign('enabled', !isset($domain_config['personal'][$app_id]) || $domain_config['personal'][$app_id]);
        $this->template = wa()->getAppPath($this->getTemplate(), 'site');
    }

    public function setLocale($app_id)
    {
        $locale = waLocale::getLocale();
        waLocale::loadByDomain($app_id, $locale);
        $locale_path = wa()->getAppPath('locale', $app_id);
        waLocale::load($locale, $locale_path, $app_id);
    }
}