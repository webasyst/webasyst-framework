<?php
/**
 * Settings tab for a single Site (domain) in UI 2.0
 */
class siteConfigureAction extends waViewAction
{
    public function execute()
    {
        $domain_id = waRequest::request('domain_id', siteHelper::getDomain(), waRequest::TYPE_INT);
        $domains = siteHelper::getDomains(true);

        if (!empty($domains[$domain_id]['is_alias'])) {
            siteHelper::saveLastDomainId();
        }

        $apps = wa()->getApps();
        $routes = wa()->getRouting()->getRoutes(siteHelper::getDomain());
        $sitemap_app_ids = siteHelper::getSitemapAppIds();

        $auth_apps = array();
        $route_apps = array();
        $redirects = array();
        $custom_texts = array();
        $has_root_settlement = false;
        foreach ($routes as $route_id => &$route) {
            if (!isset($route['app'])) {
                if (isset($route['redirect'])) {
                    $redirects[$route_id] = $routes[$route_id];
                }
                unset($routes[$route_id]);
                continue;
            }
            if ($route['app'] === ':text') {
                $custom_texts[$route_id] = $routes[$route_id];
                unset($routes[$route_id]);
                continue;
            }
            if (in_array($route['app'], $sitemap_app_ids)) {
                unset($routes[$route_id]);
                continue;
            }

            if (isset($apps[$route['app']])) {
                $auth_apps[$route['app']] = true;
                $route_apps[] = $route['app'];
                $route['app'] = $apps[$route['app']];
            } elseif ($route['app'] !== ':text') {
                $route['app'] = [
                    'id' => $route['app'],
                    'disabled' => true
                ];
            }
            if ($has_root_settlement) {
                $route['disabled'] = true;
                $route['misconfigured_settlement'] = true;
            } else if ($route['url'] === '*' && !$has_root_settlement) {
                $has_root_settlement = true;
            }
        }

        $routing_errors = wa()->getConfig()->getRoutingErrors();
        if ($routing_errors) {
            $this->view->assign('routing_error', $routing_errors);
        }

        $apps_to_add = array();
        foreach ($apps as $app_id => $app) {
            if (!empty($app['frontend']) || isset($auth_apps[$app_id])) {
                if (in_array($app_id, $sitemap_app_ids)) {
                    continue;
                }
                $apps_to_add[$app_id] = array(
                    'id' => $app_id,
                    'icon' => $app['icon'],
                    'name' => $app['name']
                );
                if (isset($auth_apps[$app_id])) {
                    if (empty($app['auth'])) {
                        unset($auth_apps[$app_id]);
                    } else {
                        $auth_apps[$app_id] = $apps_to_add[$app_id];
                    }
                }
            }
        }

        //$this->view->assign('auth_apps', $auth_apps);

        $domain = $domains[$domain_id];
        $this->setLayout(new siteBackendLayout());

        $this->view->assign('apps', $apps_to_add);
        $this->view->assign('domain_id', $domain_id);
        $this->view->assign('domain', $domain);
        $this->view->assign('domain_idn', waIdna::dec(siteHelper::getDomain()));
        $this->view->assign('title', siteHelper::getDomain('title'));
        $this->view->assign('is_https', waRequest::isHttps());
        $this->view->assign('routes', $routes);
        $this->view->assign('redirects', $redirects);
        $this->view->assign('custom_texts', $custom_texts);

        $domain = siteHelper::getDomain();
        $domain_alias = wa()->getRouting()->isAlias($domain);
        $this->getRobots($domain, $domain_alias);
        $this->getStaticFiles($domain);

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

        $domain_config_path = $this->getConfig()->getConfigPath('domains/'.$domain.'.php');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }

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
        $this->view->assign('ssl_all', ifset($domain_config, 'ssl_all', null));
        $this->view->assign('url_notification', ifset($domain_config, 'url_notification', false));
        $this->view->assign('touchicon_title', ifset($domain_config, 'touchicon_title', ''));

        // Confirm when a site is deleted
        $domains = wa()->getRouting()->getDomains();
        // Apps on the current domain

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
    /**
     * Prepare favicon and Touch icon
     *
     * @param string $domain
     */
    protected function getStaticFiles($domain)
    {
        // Favicon
        $favicon = [];
        $path = wa()->getDataPath(null, true).'/data/'.$domain.'/favicon.ico';
        if (file_exists($path)) {
            $favicon = [
                'name' => 'favicon.ico',
                'icon' => wa()->getDataUrl('data/'.$domain.'/favicon.ico', true).'?'.filemtime($path)
            ];
        }


        // Touch icon
        $touchicon = [];
        $path = wa()->getDataPath(null, true).'/data/'.$domain.'/apple-touch-icon.png';
        if (file_exists($path)) {
            $touchicon = [
                'name' => 'apple-touch-icon.png',
                'icon' => wa()->getDataUrl('data/'.$domain.'/apple-touch-icon.png', true).'?'.filemtime($path)
            ];
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
