<?php

class siteSettingsAction extends waViewAction
{
    public function execute()
    {
        $apps = wa()->getApps();
        $auth_apps = array();

        $routes = wa()->getRouting()->getRoutes(siteHelper::getDomain());
        foreach ($routes as $route) {
            if (isset($route['app']) && isset($apps[$route['app']])) {
                $auth_apps[$route['app']] = true;
            }
        }

        $temp = array();
        foreach ($apps as $app_id => $app) {
            if (isset($app['frontend']) || isset($auth_apps[$app_id])) {
                $temp[$app_id] = array(
                    'id' => $app_id,
                    'icon' => $app['icon'],
                    'name' => $app['name']
                );
                if (isset($auth_apps[$app_id])) {
                    if (empty($app['auth'])) {
                        unset($auth_apps[$app_id]);
                    } else {
                        $auth_apps[$app_id] = $temp[$app_id];
                    }
                }
            }
        }

        $this->view->assign('auth_apps', $auth_apps);

        $auth_config = wa()->getAuthConfig(siteHelper::getDomain());
        $this->view->assign('auth_config', array(
            'auth' => isset($auth_config['auth']) ? $auth_config['auth'] : false ,
            'app' => isset($auth_config['app']) ? $auth_config['app'] : false,
            'signup_captcha' => isset($auth_config['signup_captcha']) ? $auth_config['signup_captcha'] : false,
            'adapters' => isset($auth_config['adapters']) ? $auth_config['adapters'] : array()
        ));

        $this->view->assign('apps', $temp);
        $this->view->assign('domain_id', siteHelper::getDomainId());
        $this->view->assign('domain', siteHelper::getDomain());
        $this->view->assign('domain_idn', waIdna::dec(siteHelper::getDomain()));
        $this->view->assign('title', siteHelper::getDomain('title'));
        $this->view->assign('is_https', waRequest::isHttps());

        $domain = siteHelper::getDomain();
        $domain_alias = wa()->getRouting()->isAlias($domain);
        $this->getRobots($domain, $domain_alias);

        /**
         * Backend settings page
         * UI hook allow extends backend settings page
         * @event backend_settings
         * @param array $domain
         * @return array[string][string]string $return[%plugin_id%]['action_button_li'] html output
         * @return array[string][string]string $return[%plugin_id%]['section'] html output
         */
        $domain_info = siteHelper::getDomainInfo();
        $this->view->assign('backend_settings', wa()->event('backend_settings', $domain_info, array(
            'action_button_li',
            'section'
        )));

        if ($domain_alias) {
            $this->view->assign('domain_alias', $domain_alias);
            return;
        }

        $this->getStaticFiles($domain);

        $s = siteHelper::getDomain('style');
        $this->view->assign('style', $s ? $s : 'white');
        $domain_config_path = $this->getConfig()->getConfigPath('domains/'.$domain.'.php');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }
        $u = parse_url('http://'.$domain);
        //$path = isset($u['path']) ? $u['path'] : '';
        if (!isset($domain_config['apps']) || !$domain_config['apps']) {
            $this->view->assign('domain_apps_type', 0);
            $domain_name = !empty($domain_config['name']) ? $domain_config['name'] : null;
            $domain_config['apps'] = wa()->getFrontendApps($domain, $domain_name);
        } else {
            $this->view->assign('domain_apps_type', 1);
        }
        $this->view->assign('domain_apps', $domain_config['apps']);
        $this->view->assign('cdn_list', ifset($domain_config['cdn_list'], array()));
        foreach (array('head_js') as $key) {
            $this->view->assign($key, isset($domain_config[$key]) ? $domain_config[$key] : '');
        }
        if (isset($domain_config['google_analytics'])) {
            if (!is_array($domain_config['google_analytics'])) {
                $domain_config['google_analytics'] = array('code' => $domain_config['google_analytics']);
            }
        } else {
            $domain_config['google_analytics'] = array('code' => '');
        }
        $this->view->assign('google_analytics', $domain_config['google_analytics']);
        $this->view->assign('url', $this->getDomainUrl($domain));
        $this->view->assign('ssl_all', ifset($domain_config, 'ssl_all', null));
        $this->view->assign('url_notification', ifset($domain_config, 'url_notification', false));

        // Confirm when a site is deleted
        $domains = wa()->getRouting()->getDomains();
        // Apps on the current domain
        $route_apps = array();
        foreach ($routes as $_r) {
            if (isset($_r['app']) && isset($apps[$_r['app']])) {
                $route_apps[] = $_r['app'];
            }
        }

        // from here we will remove applications,
        // the rules for which are also on other sites
        // "latter apps"
        $route_apps = array_unique($route_apps);

        foreach ($domains as $_d) {
            // except the current domain
            if ($_d == $domain) {
                continue;
            }
            // Apps on the domain
            $domain_routes = wa()->getRouting()->getRoutes($_d);
            foreach ($domain_routes as $_r) {
                if (empty($_r['app']) || !isset($apps[$_r['app']])) {
                    continue;
                }
                // If there is a rule for an application
                // in another domain, delete him from "latter apps"
                $app_index = array_search($_r['app'], $route_apps);
                if ($app_index !== false) {
                    unset($route_apps[$app_index]);
                }
            }
        }

        // Get the names of applications
        $latter_apps_names = array();
        foreach ($route_apps as $_app) {
            $latter_apps_names[] = $apps[$_app]['name'];
        }

        $this->view->assign(array(
            'domains'           => $domains,
            'latter_apps_names' => $latter_apps_names,
        ));
    }

    protected function getDomainUrl($domain)
    {
        $u1 = rtrim(wa()->getRootUrl(false, false), '/');
        $u2 = rtrim(wa()->getRootUrl(false, true), '/');
        $domain_parts = parse_url('http://'.$domain);
        $u = isset($domain_parts['path']) ? $domain_parts['path'] : '';
        if ($u1 != $u2 && substr($u, 0, strlen($u1)) == $u1) {
             $u = $u2.substr($u, strlen($u1));
        }
        return $domain_parts['host'].$u;
    }

    protected function getRouteUrl($path, $route)
    {
        $url = $route['url'];
        $url = preg_replace('/\[([i|s]?:[a-z_]+)\]/ui', '', $url);
        $url = preg_replace('!(/{2,}|/\*)$!i', '/', $url);
        $url = str_replace('*', '', $url);
        return $path.'/'.$url;
    }

    /**
     * Prepare favicon and Touch icon
     *
     * @param string $domain
     */
    protected function getStaticFiles($domain)
    {
        // Favicon
        $path = wa()->getDataPath(null, true).'/data/'.$domain.'/favicon.ico';
        if (file_exists($path)) {
            $favicon = wa()->getDataUrl('data/'.$domain.'/favicon.ico', true);
        } else {
            $favicon = 'http'.(waRequest::isHttps() ? 's' : '').'://'.$domain.'/favicon.ico';
        }

        // Touch icon
        $path = wa()->getDataPath(null, true).'/data/'.$domain.'/apple-touch-icon.png';
        if (file_exists($path)) {
            $touchicon = wa()->getDataUrl('data/'.$domain.'/apple-touch-icon.png', true);
        } else {
            $touchicon = false;
        }
        $this->view->assign('favicon', $favicon);
        $this->view->assign('touchicon', $touchicon);

        if (strpos($domain, '/') !== false) {
            $this->view->assign('touchicon_message', sprintf(_w('Touch icon you upload here will not take effect for you website %s because your website is set for a subfolder on a domain. Touch icon uploaded using the form above will be set only for websites set from the domain root folder.'), $domain));
            $this->view->assign('favicon_message', sprintf(_w('Favicon image you upload here will not take effect for your website %s, because the website is accessible in a subfolder. A favicon uploaded using the form above will be applied only to websites accessible at the domain root.'), $domain));
            } else {
            $root_path = $this->getConfig()->getRootPath();
            if (file_exists($root_path.'/favicon.ico')) {
                $this->view->assign('favicon_message', _w('File favicon.ico exists in the Webasyst framework installation folder. The favicon you upload here will be overridden by the existing icon file unless you delete it.'));
            }
            if (file_exists($root_path.'/apple-touch-icon.png')) {
                $this->view->assign('touchicon_message', _w('File apple-touch-icon.png exists in the Webasyst framework installation folder. The touch icon you upload here will be overridden by the icon uploaded as file unless you delete this file.'));
            }
        }
    }

    /**
     * Prepare robots.txt
     * @param string $domain
     * @param false|string $domain_alias
     */
    protected function getRobots($domain, $domain_alias)
    {
        // Robots
        $path = wa()->getDataPath(null, true).'/data/'.$domain.'/robots.txt';
        $alias_path = !empty($domain_alias) ? wa()->getDataPath(null, true).'/data/'.$domain_alias.'/robots.txt' : null;

        if (file_exists($path)) {
            // Personal robots
            $robots = file_get_contents($path);
        } elseif ($alias_path && file_exists($alias_path)) {
            // Alias robots
            $robots = file_get_contents($alias_path);
        } else {
            // Empty :|
            $robots = '';
        }

        $this->view->assign('robots', $robots);

        if (strpos($domain, '/') !== false) {
            $this->view->assign('robots_message', sprintf(_w('Rules you specify above for robots.txt will not take effect for you website %s, because the website is accessible in a subfolder. Rules for robots.txt entered in the form above will be effective only for websites accessible at the domain root.'), $domain));
        } else {
            $root_path = $this->getConfig()->getRootPath();
            if (file_exists($root_path.'/robots.txt')) {
                $this->view->assign('robots_message', _w('File robots.txt exists in the Webasyst framework installation folder. Rules for robots.txt you specify above will not take effect unless you delete the existing file.'));
            }
        }
    }
}
