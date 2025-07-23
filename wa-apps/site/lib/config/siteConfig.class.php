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
        if (empty($route['is_backend_route'])) {
            $url_type = isset($route['url_type']) ? $route['url_type'] : 0;
        } else {
            $url_type = 'backend';
        }

        if (!isset($this->_routes[$url_type]) || $dispatch) {
            $routes = parent::getRouting($route);
            $routes = ifset($routes, $url_type, $routes[0]);

            if ($routes) {
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
            }
            if ($dispatch) {
                return $routes;
            }
            $this->_routes[$url_type] = $routes;
        }
        return $this->_routes[$url_type];
    }

    protected function getRoutingRules($route = array())
    {
        $routes = [];
        $path = $this->getRoutingPath('frontend');
        if (file_exists($path)) {
            $routes[0] = include($path);
        }

        if ($this->getEnvironment() === 'backend') {
            $path = $this->getRoutingPath('backend');
            if ($path && file_exists($path)) {
                $routes['backend'] = include($path);
            } else {
                // UI 1.3 does not use backend routing
                $routes['backend'] = ['' => 'backend/'];
            }
        }

        return $routes;
    }

    protected function getRoutingPath($type)
    {
        if ($type === null) {
            $type = $this->getEnvironment();
        }
        if ($type === 'backend' && wa($this->application)->whichUI() == '1.3') {
            return null;
        }
        $filename = ($type === 'backend') ? 'routing.backend.php' : 'routing.php';
        $path = $this->getConfigPath($filename, true, $this->application);
        if (!file_exists($path)) {
            $path = $this->getConfigPath($filename, false, $this->application);
        }
        return $path;
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

    public function dispatchPrioritySettlement($route, $url)
    {
        try {
            $domain_id = siteHelper::getDomainId();
            if (!$domain_id) {
                return;
            }
        } catch (waException $e) {
            return;
        }

        // Preview?
        $preview_hash = waRequest::request('preview_hash');
        if ($preview_hash) {
            if ($preview_hash !== $this->getPreviewHash()) {
                return null;
            }
            $status = ['draft', 'final_unpublished'];
        } else {
            $status = ['final_published'];
        }

        $blockpage_model = new siteBlockpageModel();
        $page = $blockpage_model->getByUrl($domain_id, $url, $status);
        if (!$page) {
            return null;
        }
        $blockpage_params_model = new siteBlockpageParamsModel();
        $page_params = $blockpage_params_model->getById($page['id']);
        return [
            'url' => $url,
            'page' => $page,
            'page_params' => $page_params,
            'module' => 'frontend',
            'action' => 'blockpage',
            'locale' => ifset($page_params, 'locale', ifset($route, 'locale', null)),
        ] + $route;
    }

    public function getPreviewHash($app_id = 'site')
    {
        $app_settings_model = new waAppSettingsModel();
        $hash = $app_settings_model->get($app_id, 'preview_hash');
        if ($hash) {
            $hash_parts = explode('.', $hash);
            if (time() - $hash_parts[1] > 14400) {
                $hash = '';
            }
        }
        if (!$hash) {
            $hash = str_replace('.', '', uniqid('sitepreviewhash', true)).'.'.time();
            $app_settings_model->set($app_id, 'preview_hash', $hash);
        }
        return md5($hash);
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

    public function getHiddenTechSettlement()
    {
        return [
            'app' => 'site',
            'url' => 'tech-route-do-not-delete',
            'priority_settlement' => true,
            'site_tech_route' => true,
            'private' => true,
        ];
    }

    /**
     * Will ensure serviceability of block pages on given domain by making sure at least one settlement
     * has the priority_settlement flag. In case it does not, will add flag to existing settlement
     * or (when none exist) create a new settlement marked as site_tech_route. This latter flag
     * makes settlement invisible in site overview page as well as in theme usage dialog selector.
     * @param mixed $domain id or domain name
     * @param ?bool $has_block_pages whether there's at least one blockpage on domain. Will fetch count in DB if null.
     * @param ?array $use_routes modify given routing array in place; if null then will include wa-config/routing.php and save modified values there.
     */
    public function ensureSettlementForDomain($domain, $has_block_pages=null, &$use_routes=null)
    {
        $domains = array_map(function($d) {
            return $d['name'];
        }, siteHelper::getDomains(true));
        if (isset($domain['id'])) {
            $domain_id = $domain['id'];
        } else if (wa_is_int($domain)) {
            $domain_id = $domain;
        } else if (is_string($domain)) {
            $domain_id = ifset(ref(array_flip($domains)), $domain, null);
            if (empty($domain_id)) {
                $domain_id = ifset(ref(array_flip(siteHelper::getDomains())), $domain, null);
            }
        }
        if (empty($domain_id) || empty($domains[$domain_id])) {
            throw new waException('Unknown domain');
        }
        $domain = $domains[$domain_id];
        if ($has_block_pages === null) {
            $has_block_pages = 0 < (new siteBlockpageModel())->countByField('domain_id', $domain_id);
        }

        if ($use_routes !== null) {
            if (isset($use_routes[$domain]) && !is_array($use_routes[$domain])) {
                throw new waException('domain is an alias');
            }
            $routes = array_filter(ifset($use_routes, $domain, []), function($r) {
                return ifset($r, 'app', '') === 'site';
            });
        } else {
            $routes = wa()->getRouting()->getByApp('site', $domain);
        }

        $update_settlements = [];
        $create_settlements = [];
        $delete_settlement_ids = [];
        if (!$has_block_pages) {
            // No block pages on domain: remove hidden settlement and we're done
            foreach ($routes as $r_id => $r) {
                if (!empty($r['site_tech_route'])) {
                    $delete_settlement_ids[] = $r_id;
                }
            }
        } else if (!$routes) {
            // There are block pages but no settlements: create a hidden settlement
            $create_settlements[] = $this->getHiddenTechSettlement();
        } else {
            // There are block pages and settlements already: make sure at least one settlement has priority_settlement flag
            // and remove hidden settlement if another exists
            if (count($routes) > 1) {
                foreach ($routes as $r_id => $r) {
                    if (!empty($r['site_tech_route'])) {
                        $delete_settlement_ids[] = $r_id;
                        unset($routes[$r_id]);
                        break;
                    }
                }
            }
            foreach ($routes as $r_id => $r) {
                if (!empty($r['priority_settlement'])) {
                    $update_settlements = [];
                    break;
                }
                $r['priority_settlement'] = true;
                $update_settlements[$r_id] = $r;
            }
        }

        if ($update_settlements || $create_settlements || $delete_settlement_ids) {
            if ($use_routes !== null) {
                $routes =& $use_routes;
            } else {
                $path = $this->getPath('config', 'routing');
                if (file_exists($path)) {
                    $routes = include($path);
                } else {
                    $routes = [
                        $domain => [],
                    ];
                }
            }

            foreach ($delete_settlement_ids as $r_id) {
                unset($routes[$domain][$r_id]);
            }

            foreach ($update_settlements as $r_id => $r) {
                $routes[$domain][$r_id] = $r;
            }

            foreach ($create_settlements as $r) {
                $routes[$domain][] = $r;
            }

            if (!empty($path)) {
                waUtils::varExportToFile($routes, $path);
            }
        }
    }

    public function throwFrontControllerDispatchException()
    {
        if (wa()->getEnv() == 'backend' && wa()->whichUI() == '1.3') {
            wa()->getResponse()->redirect(wa()->getAppUrl('site'));
        }
        parent::throwFrontControllerDispatchException();
    }

    public function checkUpdates()
    {
        parent::checkUpdates();
        $this->installAfter();
    }

    protected function installAfter()
    {
        $model = new waAppSettingsModel();
        $install_after_trigger = $model->get($this->application, 'install_after_trigger', 0);
        if ($install_after_trigger && wa()->getUser()->isAuth() && wa()->getEnv() == 'backend') {
            $old_active = waSystem::getApp();
            if ($old_active != $this->application) {
                waSystem::setActive($this->application);
            }
            include($this->getAppPath('lib/config/install.after.php'));
            $model->del($this->application, 'install_after_trigger');
            waSystem::setActive($old_active);
        }
    }

    protected function configure()
    {
        if (waRequest::get('module') === 'editor' && waRequest::get('action') === 'body') {
            // Do not remember page inside iframe as last page to return to after backend login
            waRequest::setParam('skip_update_last_page', true);
        }
        parent::configure();
    }
}
