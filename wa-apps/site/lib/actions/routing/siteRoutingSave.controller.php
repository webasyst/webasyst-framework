<?php

class siteRoutingSaveController extends waJsonController
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
                    $this->response['add'] = 'top';
                } else {
                    if (!$route['url']) {
                        $route['url'] = '*';
                    }

                    if ($route['url'] == '*') {
                        $routes[$domain][$route_id] = $route;
                        $this->response['add'] = 'bottom';
                    } else {
                        if (strpos($route['url'], '*') === false) {
                            if (substr($route['url'], -1) == '/') {
                                $route['url'] .= '*';
                            } elseif (substr($route['url'], -1) != '*' && strpos(substr($route['url'], -5), '.') === false) {
                                $route['url'] .= '/*';
                            }
                        }
                        $routes[$domain] = array($route_id => $route) + $routes[$domain];
                        $this->response['add'] = 'top';
                    }

                    // add robots
                    $robots = new siteRobots($domain);
                    $robots->add($route['app'], $route['url']);
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
                $this->response['add'] = 'top';
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
            waUtils::varExportToFile($routes, $path);
            wa('site')->event('route_save.after', $params);

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
            if (waRequest::post('correct_url') && strpos($new['url'], '*') === false) {
                if (!$new['url']) {
                    $new['url'] = '*';
                } elseif (substr($new['url'], -1) == '/') {
                    $new['url'] .= '*';
                } elseif (substr($new['url'], -1) != '*' && strpos(substr($new['url'], -5), '.') === false) {
                    $new['url'] .= '/*';
                }
            }

            if (($new['url'] != $old['url']) || (ifset($new['theme']) != ifset($old['theme'])) || (ifset($new['theme_mobile']) != ifset($old['theme_mobile']))) {
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
            } else {
                foreach ($routes[$domain] as $r_id => $r) {
                    if (isset($r['app']) && $r_id != $route_id && $r['url'] == $new['url']) {
                        $old_app = $r['app'] ? wa()->getAppInfo($r['app']) : array();
                        $old_app = ifset($old_app['name']);
                        $new_app = ifset($new['app']) ? wa()->getAppInfo($new['app']) : array();
                        $new_app = ifset($new_app['name']);
                        $this->response['confirm'] = sprintf(_w('The URL %s is already used by %s app. If you proceed, this will replace %s app with %s app on this URL.'),
                            $new['url'], $old_app, $old_app, $new_app);
                        $this->response['replace'] = $r_id;
                        return;
                    }
                }
                $routes[$domain][$route_id] = $new;
            }

            $params = array(
                'domain'   => $domain,
                'route'    => &$new,
                'route_id' => $route_id,
                'is_new'   => false
            );

            wa('site')->event('route_save.before', $params);
            $routes[$domain][$route_id] = $new;
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

        //Delete cache problem domains
        $cache_domain = new waVarExportCache('problem_domains', 3600, 'site/settings/');
        $cache_domain->delete();
        $this->response['routing_errors'] = siteHelper::getRoutingErrorsInfo();
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
                    $name = $app_settings_model->get('webasyst', 'name', 'Webasyst');
                }
            } elseif ($app_id == ':text') {
                $params += array(
                    'static_content'      => '',
                    'static_content_type' => '',
                );
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

        return $r;
    }
}
