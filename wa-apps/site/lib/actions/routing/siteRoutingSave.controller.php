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
        $route_id = waRequest::get('route', '');

        // new route
        if (!strlen($route_id)) {
            $route = $this->getRoute();
            if (!empty($route['app'])) {
                if (!$route['url']) {
                    $route['url'] = '*';
                }
                $route_id = $this->getRouteId($routes[$domain]);
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
                // save
                waUtils::varExportToFile($routes, $path);
                // add robots
                $robots = new siteRobots($domain);
                $robots->add($route['app'], $route['url']);
                // log
                $this->log('route_add');
            } elseif (isset($route['redirect'])) {
                if (substr($route['url'], -1) != '*' && substr($route['url'], -1) != '/' && strpos(substr($route['url'], -5), '.') === false) {
                    $route['url'] .= '/';
                }
                if (!$route['redirect']) {
                    $route['redirect'] = '/';
                }
                if (substr($route['redirect'], -1) != '*' && substr($route['redirect'], -1) != '/' && strpos(substr($route['redirect'], -5), '.') === false) {
                    $route['redirect'] .= '/';
                }
                $route_id = $this->getRouteId($routes[$domain]);
                $routes[$domain] = array($route_id => $route) + $routes[$domain];
                $this->response['add'] = 'top';
                // save
                waUtils::varExportToFile($routes, $path);
                // log
                $this->log('route_add');
            }

            $html = '<tr id="route-'.$route_id.'">
                        <td class="s-url">
                            <span><a style="display:inline" href="#"><i class="icon16 sort"></i></a></span> <span class="s-domain-url">'.$domain.'/</span><span class="s-editable-url" style="color:#000">'.htmlspecialchars($route['url']).'</span>
                        </td>
                        <td class="s-app'.(!empty($route['private']) ? ' gray' : '').'">';
                            $root_url = wa()->getRootUrl();
                            if (!empty($route['app'])) {
                                $app = wa()->getAppInfo($route['app']);
                                $html .= '<img src="'.$root_url.$app['icon'][24].'" class="s-app24x24icon-menu-v" alt="">'.$app['name'];
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
            $old = $routes[$domain][$route_id];
            $routes[$domain][$route_id] = $this->getRoute($old);

            // save
            waUtils::varExportToFile($routes, $path);

            $this->response['url'] = $routes[$domain][$route_id]['url'];
            $this->response['private'] = !empty($routes[$domain][$route_id]['private']);

            if (isset($routes[$domain][$route_id]['redirect'])) {
                $this->response['redirect'] = $routes[$domain][$route_id]['redirect'];
            }

            if (!isset($routes[$domain][$route_id]['redirect']) && $routes[$domain][$route_id]['url'] != $old['url']) {
                // update pages
                $this->updatePagesRoute($old, $routes[$domain][$route_id]['url']);
                // update robots
                $robots = new siteRobots($domain);
                $robots->update($routes[$domain][$route_id]['app'], $old['url'], $routes[$domain][$route_id]['url']);
            }

            // log
            $this->log('route_edit');
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
        $model = new $class();
        $model->updateRoute(siteHelper::getDomain(), $route['url'], $url);

        $params = array('old' => siteHelper::getDomain().'/'.$route['url'], 'new' => siteHelper::getDomain().'/'.$url);
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
        }

        foreach ($params as $key => $value) {
            if ($key != '_name' || (isset($name) && $value != $name) || (isset($r['_name']) && $value != $r['_name'])) {
                $r[$key] = $value;
            }
        }

        if (empty($r['locale'])) {
            unset($r['locale']);
        }

        return $r;
    }
}