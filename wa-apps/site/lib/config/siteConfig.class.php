<?php

class siteConfig extends waAppConfig
{
    protected $_routes = null;

    public function checkRights($module, $action)
    {
        switch ($module) {
            case 'files':
                if ($action == 'uploadimage') {
                    return true;
                }
                return wa()->getUser()->isAdmin($this->application);
            case 'domains':
            case 'themes':
            case 'blocks':
                return wa()->getUser()->isAdmin($this->application);
        }
        return true;
    }

    public function getRouting($route = array(), $dispatch = true)
    {
        if ($this->_routes === null || $dispatch) {
            $routes = parent::getRouting($route);
            /**
             * Extend routing via plugin routes
             * @event routing
             * @param array $routes
             * @return array routes collected for every plugin
             */
            $result = wa()->event(array('site', 'routing'), $routes);
            $all_plugins_routes = array();
            foreach ($result as $plugin_id => $routing_rules) {
                if ($routing_rules) {
                    $plugin = str_replace('-plugin', '', $plugin_id);
                    if ($plugin == $plugin_id) {
                        // apps can not add routes to other apps
                        continue;
                    }
                    foreach ($routing_rules as $url => &$route) {
                        if (!is_array($route)) {
                            list($route_ar['module'], $route_ar['action']) = explode('/', $route);
                            $route = $route_ar;
                        }
                        $route['plugin'] = $plugin;
                        $route['app'] = $this->application;
                        $all_plugins_routes[$url] = $route;
                    }
                    unset($route);
                }
            }
            $routes = array_merge($all_plugins_routes, $routes);
            if ($dispatch) {
                return $routes;
            }
            $this->_routes = $routes;
        }
        return $this->_routes;
    }


    public function explainLogs($logs)
    {
        $page_ids = array();
        foreach ($logs as $l_id => $l) {
            if (in_array($l['action'], array('page_add', 'page_edit', 'page_move'))) {
                $page_ids[] = $l['params'];
            } elseif (substr($l['action'], 0, 8) == 'template' || substr($l['action'], 0, 5) == 'theme' ||
                substr($l['action'], 0, 5) == 'route') {
                $logs[$l_id]['params_html'] = htmlspecialchars(waIdna::dec($l['params']));
            }
        }
        if ($page_ids) {
            $class_name = $this->application.'PageModel';
            /**
             * @var waPageModel $model
             */
            $model = new $class_name();
            $pages = $model->query('SELECT id, domain_id, name FROM '.$model->getTableName().' WHERE id IN (i:ids)', array('ids' => $page_ids))->fetchAll('id');
            $app_url = wa()->getConfig()->getBackendUrl(true).$l['app_id'].'/';
            foreach ($logs as &$l) {
                if (in_array($l['action'], array('page_add', 'page_edit', 'page_move')) && isset($pages[$l['params']])) {
                    $l['params_html'] = '<div class="activity-target"><a href="'.$app_url.'?domain_id='.$pages[$l['params']]['domain_id'].'#/pages/'.$l['params'].'">'.
                        htmlspecialchars($pages[$l['params']]['name']).'</a></div>';
                }
            }
            unset($l);
        }
        return $logs;
    }

    /**
     * @return null|string
     */
    public function onCount()
    {

    }

    /**
     * Search routing problem to each domain.
     * @return array() || array(domain_name => array(not_install=> array(), incorrect => array()), etc ;
     */
    public function getRoutingErrors()
    {
        // Get from cache.
        $cache_domain = new waVarExportCache('problem_domains', 3600, 'site/settings/');
        // If not cached, this is null.
        // If cached and there's no routing errors then this is an empty array.
        $cached = $cache_domain->get();
        if (is_array($cached)) {
            return $cached;
        }

        // If not cached, look for routing errors
        $routes = wa()->getConfig()->getConfigFile('routing');
        $domains = siteHelper::getDomains(true);

        $error_domains_data = array();

        $notifications_enabled = array();

        if ($routes) {
            $apps = wa()->getApps();
            $valid_app = array();
            //get app which can show in frontend
            foreach ($apps as $app_id => $app) {
                if (ifset($app, 'frontend', null) === true) {
                    $valid_app[$app_id] = true;
                }
            }

            foreach ($routes as $domain_name => $settlements) {
                if (empty($settlements) || !is_array($settlements)) {
                    continue;
                }

                // This will contain a list of applications
                // settled after a wildcar rule * that catches everything.
                // null = no wildcard rule found yet.
                // array = list of incorrectly settled apps (may be empty array).
                $tmp_incorrect_app = null;

                foreach ($settlements as $s_id => $settlement) {
                    //delete installed apps
                    if (isset($settlement['app'])) {
                        unset($valid_app[$settlement['app']]);
                    }

                    if ($tmp_incorrect_app === null) {
                        if ($settlement['url'] == '*') {
                            $tmp_incorrect_app = array();
                        }
                    } else {
                        $tmp_incorrect_app[$s_id] = ifset($settlement, 'app', null);

                        //Add redirect to incorrect
                        if (empty($settlement['app']) && isset($settlement['redirect'])) {
                            $tmp_incorrect_app[$s_id] = ':redirect';
                        }

                        //Add text file to incorrect
                        if (empty($settlement['app']) && isset($settlement['static_content'])) {
                            $tmp_incorrect_app[$s_id] = ':text';
                        }
                    }
                }


                //Get domain settings
                $domain_config_path = wa('site')->getConfig()->getConfigPath('domains/'.$domain_name.'.php');
                if (file_exists($domain_config_path)) {
                    //apps_notification and url_notification have inverted value. false == on, true == off.
                    $domain_config = include($domain_config_path);
                }

                //get problem domain_id. Because all controller and layout use domain_id =(
                $domain_id = null;
                foreach ($domains as $id => $domain_data) {
                    if ($domain_data['name'] == $domain_name) {
                        $domain_id = $id;
                        break;
                    }
                }

                if (!$domain_id || ifempty($domain_config, 'url_notification', false)) {
                    continue;
                } else {
                    //Save status. We do not know all the problems until we finish the foreach.
                    $notifications_enabled[$domain_id] = true;
                }

                //Save problem url count
                if ($tmp_incorrect_app && count($tmp_incorrect_app) > 0) {
                    $error_domains_data['apps'][$domain_id]['incorrect'] = $tmp_incorrect_app;
                }

            }

            if (!empty($valid_app) && !empty($notifications_enabled)) {
                //Save not installed apps
                foreach ($notifications_enabled as $d_id => $bool) {
                    $error_domains_data['apps'][$d_id]['not_install'] = array_keys($valid_app);
                }
            }
        }

        $cache_domain->set($error_domains_data);

        return $error_domains_data;
    }
}