<?php

class siteDesignLayout extends waLayout
{
    public function execute()
    {
        $route_id = waRequest::get('route');
        $domain = siteHelper::getDomain();
        $routes = wa()->getRouting()->getRoutes($domain);
        $app_id = $routes[$route_id]['app'];
        $app = wa()->getAppInfo($app_id);

        if (!empty($app['themes'])) {
            $themes = siteHelper::getThemes($app_id, false);
        } else {
            $themes = array();
        }

        $hash = $this->getThemeHash();

        $url = wa()->getRouting()->getUrlByRoute($routes[$route_id], $domain);
        $url .= strpos($url, '?') === false ? '?' : '&';
        $url .= 'theme_hash='.$hash.'&set_force_theme=';

        $this->view->assign('preview_url', $url);

        $this->view->assign('route_id', $route_id);
        $route = $routes[$route_id];
        if (!isset($route['theme']) || !$route['theme']) {
            $route['theme'] = 'default';
        }
        if (!isset($route['theme_mobilde'])) {
            $route['theme_mobile'] = 'default';
        }

        $themes = siteHelper::sortThemes($themes, $route);

        $this->view->assign('route', $route);
        $this->view->assign('themes', $themes);

        $this->view->assign('app_id', $app_id);
        $this->view->assign('app', $app);

        $this->view->assign('domain_id', siteHelper::getDomainId());
        $this->view->assign('domain', $domain);
        
        $theme = isset($routes[$route_id]['theme']) ? $routes[$route_id]['theme'] : 'default';
        $this->view->assign('theme', isset($themes[$theme]) ? $theme : 'default');
        $this->view->assign('domain_root_url', siteHelper::getDomainUrl());
    }
    
    protected function getThemeHash()
    {
        $hash = $this->appSettings('theme_hash');
        if ($hash) {
            $hash_parts = explode('.', $hash);
            if (time() - $hash_parts[1] > 14400) {
                $hash = '';
            }
        }
        if (!$hash) {
            $hash = uniqid().'.'.time();
            $app_settings_model = new waAppSettingsModel();
            $app_settings_model->set('site', 'theme_hash', $hash);
        }
        
        return md5($hash);        
    }
}