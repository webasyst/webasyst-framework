<?php

class siteConfigureRedirectSaveController extends waJsonController
{
    public function execute()
    {
        $path = $this->getConfig()->getPath('config', 'routing');

        if (file_exists($path)) {
            $routes = include($path);
            if (!is_writable($path)) {
                $this->errors = sprintf(_w('Settings could not be saved due to the insufficient file write permissions for the file "%s".'), 'wa-config/routing.php');
                return;
            }
        } else {
            $routes = array();
        }
        $domain = siteHelper::getDomain();
        if (empty($routes[$domain])) {
            $routes[$domain] = array();
        }
        $route_id = waRequest::get('route', '');
        $unchanged_routes = $routes[$domain];

        // new route
        if (!strlen($route_id)) {
            $route = $this->getRoute();
            if (!empty($route['url'])) {
                $route['url'] = urldecode($route['url']);
            }
            if (!empty($route['app'])) {
                $route_id = $this->getRouteId($routes[$domain]);

                if ($route['app'] === ':text') {
                    $routes[$domain] = array($route_id => $route) + $routes[$domain];
                    $this->response['add'] = 'bottom';
                } else {
                    if (!$route['url']) {
                        $route['url'] = '*';
                    }
                    $this->syncRouteName($route);

                    if ($route['url'] == '*') {
                        if (!empty($route['show_over_another_section'])) {
                            $route['url'] = '';
                            $routes[$domain] = array($route_id => $route) + $routes[$domain];
                        } else {
                            $routes[$domain][$route_id] = $route;
                        }
                        $this->response['add'] = 'bottom';
                    } else {
                        if (!empty($route['show_over_another_section'])) {
                            if (strpos(substr($route['url'], -5), '.') === false) {
                                $route['url'] = rtrim($route['url'], '/*').'/';
                            }
                        } else if (strpos($route['url'], '*') === false) {
                            if (strpos(substr($route['url'], -5), '.') === false) {
                                $route['url'] = rtrim($route['url'], '/*').'/*';
                            }
                        }
                        $routes[$domain] = array($route_id => $route) + $routes[$domain];
                        $this->response['add'] = 'bottom';
                    }
                    unset($route['show_over_another_section']);
                }
            } elseif (isset($route['redirect'])) {
                if ($route['url'] && substr($route['url'], -1) != '*' && substr($route['url'], -1) != '/' && strpos(substr($route['url'], -5), '.') === false) {
                    $route['url'] .= '/';
                }
                if (!$route['redirect']) {
                    $route['redirect'] = '/';
                }
                if (substr($route['redirect'], -1) != '*' && substr($route['redirect'], -1) != '/' && strpos(substr($route['redirect'], -5), '.') === false) {
                    $route['redirect'] .= '/';
                }
                $redirect_parts = @parse_url($route['redirect']);
                if (!isset($redirect_parts['scheme']) && preg_match("/^[^\/]+\./uis", $route['redirect'])) {
                    $route['redirect'] = 'http://'.$route['redirect'];
                }
                $route_id = $this->getRouteId($routes[$domain]);
                $routes[$domain] = array($route_id => $route) + $routes[$domain];
                $this->response['add'] = 'bottom';
            }

            if ($this->hasDupeRouteUrl($unchanged_routes, $route)) {
                return;
            }

            if (!empty($route['app'])) {
                // add robots
                $robots = new siteRobots($domain);
                $robots->add($route['app'], $route['url']);
            }

            // Save new route
            $params = array(
                'domain'   => $domain,
                'route'    => &$route,
                'route_id' => $route_id,
                'is_new'   => true
            );

            wa('site')->event('route_save.before', $params);
            $routes[$domain][$route_id] = $route;
            self::ensurePrioritySettlement($routes);
            waUtils::varExportToFile($routes, $path);
            wa('site')->event('route_save.after', $params);

            $this->response['route_id'] = $route_id;

            // log
            $this->logAction('route_add', $domain.'/'.$route['url']);

            $html = '<tr id="route-'.$route_id.'">
                        <td class="s-url">
                            <span><a style="display:inline" href="#"><i class="icon16 sort"></i></a></span> <span class="s-domain-url">'.waIdna::dec($domain).'/</span><span class="s-editable-url" style="color:#000">'.htmlspecialchars($route['url']).'</span>
                        </td>
                        <td class="s-app'.(!empty($route['private']) ? ' gray' : '').'">';
            $root_url = wa()->getRootUrl();
            if (!empty($route['app'])) {
                if ($route['app'] == ':text') {
                    $html .= '<img src="'.$root_url.'wa-apps/site/img/script-code.png?v=1" class="s-app24x24icon-menu-v" alt="">
                                <span class="text">'.htmlspecialchars(substr($route['static_content'], 0, 32)).(strlen($route['static_content'])>32?'...':'').'</span>';
                } else {
                    $app = wa()->getAppInfo($route['app']);
                    $html .= '<img src="'.$root_url.$app['icon'][24].'" class="s-app24x24icon-menu-v" alt="">'.$app['name'];
                }
            } else {
                $html .= '<img src="'.$root_url.'wa-apps/site/img/arrow.png" class="s-app24x24icon-menu-v" alt="">
                                <span class="redirect">'.htmlspecialchars($route['redirect']).'</span>';
            }
            $html .= '</td>
                        <td class="s-actions align-right">
                            <a href="#" class="s-route-action s-route-settings" title="'._w('Settings').'"><i class="icon16 settings"></i></a>
                        </td>
                    </tr>';
            $this->response['html'] = $html;
        } else {
            if (empty($routes[$domain][$route_id])) {
                $routes[$domain][$route_id] = array();
            }

            $old = $routes[$domain][$route_id];
            $new = $this->getRoute($old);
            if (!empty($new['url'])) {
                $new['url'] = urldecode($new['url']);
            }

            if (!isset($old['redirect']) && ifset($old, 'app', '') !== ':text') {
                $is_old_rule_broken = siteHelper::isBrokenAppRouteUrl($old);
                if (!waRequest::post('correct_url') && $is_old_rule_broken) {
                    // do not allow to change route URL if app rule was broken
                    $new['url'] = $old['url'];
                } else if (!empty($new['show_over_another_section'])) {
                    if (strpos(substr($new['url'], -5), '.') === false) {
                        $new['url'] = rtrim($new['url'], '/*');
                        if ($new['url']) {
                            $new['url'] .= '/';
                        }
                    }
                } else {
                    if ($new['url'] !== '*' && strpos(substr($new['url'], -5), '.') === false) {
                        $new['url'] = rtrim($new['url'], '/*').'/*';
                    }
                }
            }
            unset($new['show_over_another_section']);

            if (
                ($new['url'] != $old['url'])
                || (ifset($new, 'theme', '') != ifset($old, 'theme', ''))
                || (ifset($new, 'theme_mobile', '') != ifset($old, 'theme_mobile', ''))
            ) {
                $this->response['change'] = 1;
            }

            if ($replace_id = waRequest::get('replace')) {
                $this->response['delete'] = $replace_id;
                $tmp = array();
                foreach ($routes[$domain] as $r_id => $r) {
                    if ($r_id == $replace_id) {
                        $tmp[$route_id] = $new;
                    } else {
                        $tmp[$r_id] = $r;
                    }
                }
                $routes[$domain] = $tmp;
            } else if (waRequest::request('set_main_page')) {
                // make silence for current homepage
                $domain_id = siteHelper::getDomainId();
                $main_page = new siteMainPage($domain_id, $routes);
                $main_page->silenceMainPage();
                if ($new['url'] === '*') {
                    unset($routes[$domain][$route_id]);
                }
            } else {
                if ($this->hasDupeRouteUrl($routes[$domain], $new, $route_id)) {
                    return;
                }

                $this->syncRouteName($new);
                $routes[$domain][$route_id] = $new;
            }

            if (!$this->validate($new)) {
                return;
            }

            if (waRequest::request('fix_incorrect_rule')) {
                $routes[$domain] = $this->fixIncorrectRule($route_id, $routes[$domain]);
            }

            $params = array(
                'domain'   => $domain,
                'route'    => &$new,
                'route_id' => $route_id,
                'is_new'   => false
            );

            wa('site')->event('route_save.before', $params);
            $routes[$domain][$route_id] = $new;
            $route = $new;
            self::ensurePrioritySettlement($routes);
            waUtils::varExportToFile($routes, $path);
            wa('site')->event('route_save.after', $params);

            $this->response['url'] = $routes[$domain][$route_id]['url'];
            $this->response['private'] = !empty($routes[$domain][$route_id]['private']);

            if (isset($routes[$domain][$route_id]['redirect'])) {
                $this->response['redirect'] = $routes[$domain][$route_id]['redirect'];
                $this->response['disabled'] = (ifset($routes[$domain][$route_id]['disabled']) > 0 ) ? true : false;
            } elseif (isset($routes[$domain][$route_id]['text'])) {
                $this->response['text'] = $routes[$domain][$route_id]['text'];
            }

            if (!isset($routes[$domain][$route_id]['redirect']) && $routes[$domain][$route_id]['url'] != $old['url']) {
                // update pages
                $this->updatePagesRoute($old, $routes[$domain][$route_id]['url']);
                // update robots
                $robots = new siteRobots($domain);
                $robots->update($routes[$domain][$route_id]['app'], $old['url'], $routes[$domain][$route_id]['url']);
            }

            // log
            $this->logAction('route_edit', $domain.'/'.$routes[$domain][$route_id]['url']);
        }

        $config_cache = waConfigCache::getInstance();
        $config_cache->setFileContents($path, $routes);

        //Delete cache problem domains
        $cache_domain = new waVarExportCache('problem_domains', 3600, 'site/settings/');
        $cache_domain->delete();
        $this->response['routing_errors'] = siteHelper::getRoutingErrorsInfo();

        $is_site_app = waRequest::post('is_site_app', 0, waRequest::TYPE_INT);
        if ($is_site_app) {
            $page_id = waRequest::post('page_id', 0, 'int');
            $page_data = waRequest::post('page', [], 'array');
            $page_data['route'] = $route['url'];
            if (!$page_id && waRequest::post('translit') && empty($page_data['url'])) {
                $page_data['url'] = $this->translit($page_data['name']);
            }

            $save_result = (new siteSavePage())->savePage($page_id, $page_data);
            if(isset($save_result['error'])) {
                $this->errors = $save_result['error'];
                return;
            } else {
                $this->response = $this->response + [
                    'page_id' => $save_result['id'],
                    'name' => $save_result['name'],
                    'status' => $save_result['status'],
                    'is_route' => true,
                ];
            }
        }
    }

    protected function getRouteId($routes)
    {
        $route = 0;
        foreach ($routes as $r_id => $r) {
            if (is_numeric($r_id) && $r_id > $route) {
                $route = $r_id;
            }
        }
        $route++;
        return $route;
    }

    protected function updatePagesRoute($route, $url)
    {
        $app = wa()->getAppInfo($route['app']);
        if (empty($app['pages'])) {
            return;
        }
        // init app
        wa($app['id']);
        $class = $app['id'].'PageModel';
        /**
         * @var waPageModel $model
         */
        $domain = siteHelper::getDomain();
        $model = new $class();
        $model->updateRoute($domain, $route['url'], $url);

        $params = array('domain' => $domain, 'old' => $domain.'/'.$route['url'], 'new' => $domain.'/'.$url);
        wa()->event('update.route', $params);
    }

    protected function getRoute($old = array())
    {
        $params = waRequest::post('params', array());

        if (!isset($params['redirect'])) {

            $app_id = $old ? $old['app'] : $params['app'];

            if ($app_id == 'site') {
                if ($title = siteHelper::getDomain('title')) {
                    $name = $title;
                } else {
                    $app_settings_model = new waAppSettingsModel();
                    $name = $app_settings_model->get('webasyst', 'name', _ws('My company'));
                }
            } elseif ($app_id == ':text') {
                $params += array(
                    'static_content'      => '',
                    'static_content_type' => '',
                );
                $name = '';
            } else {
                $app = wa()->getAppInfo($app_id);
                $name = $app['name'];
            }

            if ($old) {
                $r = array(
                    'url' => $old['url'],
                    'app' => $old['app'],
                );
            } else {
                $r = array();
            }

            foreach ($old as $k => $v) {
                if (substr($k, 0, 1) == '_') {
                    $r[$k] = $old[$k];
                }
            }

            $other_params = waRequest::post('other_params', '');
            $other_params = explode("\n", $other_params);

            foreach ($other_params as $string) {
                $string = trim($string);
                if ($string && strpos($string, '=') !== false) {
                    $string = explode('=', $string, 2);
                    if ($string[0]) {
                        $r[$string[0]] = $string[1];
                    }
                }
            }
        } else {
            if (isset($params['app'])) {
                unset($params['app']);
            }
        }

        foreach ($params as $key => $value) {
            if ($key != '_name' || (isset($name) && $value != $name) || (isset($r['_name']) && $value != $r['_name'])) {
                $r[$key] = $value;
            }
        }

        if (empty($r['locale'])) {
            unset($r['locale']);
        }

        if (!empty($r['app'])) {
            if (!empty($r['theme'])) {
                $theme = new waTheme($r['app'].':'.$r['theme']);
                if ($theme['type'] == waTheme::TRIAL) {
                    $r['theme'] = 'default';
                }
            }
            if (!empty($r['theme_mobile'])) {
                $theme_mobile = new waTheme($r['app'].':'.$r['theme_mobile']);
                if ($theme_mobile['type'] == waTheme::TRIAL) {
                    $r['theme_mobile'] = 'default';
                }
            }
        }

        if (!strlen(ifset($r['url']))) {
            $r['url'] = '*';
        }

        return $r;
    }

    protected function validate($route = [])
    {
        $valid = true;
        if (isset($route['app'])) {
            $path = $this->getConfig()->getAppsPath($route['app'], 'lib/config/site.php');
            if (file_exists($path)) {
                waSystem::getInstance($route['app'])->setActive($route['app']);
                $site = include($path);
                waSystem::setActive('site');

                if (isset($site['params'])) {
                    foreach ($site['params'] as $name => $param) {
                        $type = ifempty($param, 'type', '');
                        if (!empty($type) && 'select' === $type) {
                            $items = ifempty($param, 'items', []);
                            if (isset($route[$name]) && !in_array($route[$name], array_keys($items))) {
                                $valid = false;
                                $this->errors[] = [
                                    'field' => $name,
                                    'description' => sprintf_wp('Invalid value in field “%s”.', ifempty($param, 'name', ''))
                                ];
                            }
                        }
                    }
                }
            }

        }

        return $valid;
    }

    // Return a copy of $domain_routes with $domain_routes[$route_id] rule placed
    // just above the first encountered 'url'=>'*' rule. Does not change anything
    // if there's no '*' or something else went wrong.
    protected function fixIncorrectRule($route_id, $domain_routes)
    {
        if (!isset($domain_routes[$route_id])) {
            return $domain_routes;
        }
        $route_to_fix = $domain_routes[$route_id];

        $result = [];
        foreach ($domain_routes as $r_id => $r) {
            if ($route_to_fix && $r_id != $route_id && ifset($r, 'url', null) === '*') {
                unset($result[$route_id]);
                $result[$route_id] = $route_to_fix;
                $route_to_fix = null;
            }
            if (!isset($result[$r_id])) {
                $result[$r_id] = $r;
            }
        }
        if ($route_to_fix || count($result) != count($domain_routes)) {
            return $domain_routes;
        }
        return $result;
    }

    public function hasDupeRouteUrl(array $routes_by_domain, $curr_route, $route_id = null)
    {
        $curr_is_redirect = isset($curr_route['redirect']);
        foreach ($routes_by_domain as $r_id => $r) {
            if ($curr_is_redirect) {
                if (!isset($r['redirect'])) {
                    continue;
                }
            } elseif (isset($r['redirect'])) {
                continue;
            }

            if ($r_id == $route_id || $r['url'] != $curr_route['url']) {
                continue;
            }

            $this->response['confirm'] = _w('The specified URL already exists.');
            $this->response['replace'] = $r_id;
            return true;
        }
        return false;
    }

    private function syncRouteName(array &$route)
    {
        if (!waRequest::post('sync_name')) {
            return;
        }
        $page_data = waRequest::post('page', [], 'array');
        if ($page_name = ifset($page_data, 'name', '')) {
            $route['_name'] = $page_name;
        }
    }

    protected static function ensurePrioritySettlement(&$all_routes)
    {
        if (!waLicensing::check('site')->isPremium()) {
            return;
        }

        // Make sure every domain has at least one priority_settlement of Site app
        // if at least one Site settlement exists for that domain or it has block pages created
        foreach ($all_routes as $domain => &$domain_routes) {
            if (is_array($domain_routes)) {
                unset($site_route, $tech_route);
                foreach ($domain_routes as &$route) {
                    if (ifset($route, 'app', '') === 'site') {
                        if (!empty($route['site_tech_route'])) {
                            $tech_route =& $route;
                        }
                        if (!empty($route['priority_settlement'])) {
                            continue;
                        }
                        $site_route =& $route;
                    }
                }

                if (!empty($site_route)) {
                    $site_route['priority_settlement'] = true;
                }
                if (empty($site_route) || !empty($tech_route)) {
                    try {
                        wa('site')->getConfig()->ensureSettlementForDomain($domain, null, $all_routes);
                    } catch (Throwable $e) {
                    }
                }
            }
        }
        unset($domain_routes, $route);
    }
}
