<?php

class siteRoutingEditAction extends waViewAction
{

    public function execute()
    {
        $route_id = waRequest::get('route', '');
        $routes = wa()->getRouting()->getRoutes(siteHelper::getDomain());
        if ($route_id && !isset($routes[$route_id])) {
            throw new waException('Route not found', 404);
        }

        if ($route_id || strlen($route_id)) {
            $route = $routes[$route_id];
            $app_id = ifset($route['app']);
        } else {
            $route = array();
            $apps = wa()->getApps();
            foreach ($apps as $app_id => $app) {
                if (empty($app['frontend'])) {
                    unset($apps[$app_id]);
                }
            }
            reset($apps);
            $this->view->assign('apps', $apps);
            $app_id = waRequest::get('app', key($apps));
            if ($app_id == ':text') {
                $route['static_content'] = '';
                $route['static_content_type'] = '';
            }
        }

        if ($app_id) {

            if ($app_id == ':text') {
                $app = array();
            } else {

                $app = wa()->getAppInfo($app_id);

                if ($app) {
                    $path = $this->getConfig()->getAppsPath($app_id, 'lib/config/site.php');
                    if (file_exists($path)) {
                        // load locale of the app
                        if ($app_id != 'site') {
                            waSystem::getInstance($app_id)->setActive($app_id);
                        }

                        $app['site'] = include($path);
                        // return old locale of the site
                        if ($app_id != 'site') {
                            waSystem::setActive('site');
                        }
                    }

                    if (!$route && isset($app['routing_params']) && is_array($app['routing_params'])) {
                        wa($app_id);
                        foreach ($app['routing_params'] as $routing_param => $routing_param_value) {
                            if (is_callable($routing_param_value)) {
                                $app['routing_params'][$routing_param] = call_user_func($routing_param_value);
                            }
                        }
                        $route = $app['routing_params'];
                    }

                    if (isset($app['site']['params'])) {
                        $params = $this->getParams($route_id, $app['site']['params'], $route);
                    } else {
                        $params = array();
                    }

                    if (!isset($route['_name'])) {
                        if ($app_id == 'site') {
                            if ($title = siteHelper::getDomain('title')) {
                                $route_name = $title;
                            } else {
                                $app_settings_model = new waAppSettingsModel();
                                $route_name = $app_settings_model->get('webasyst', 'name', 'Webasyst');
                            }
                        } else {
                            $route_name = $app['name'];
                        }
                    } else {
                        $route_name = $route['_name'];
                    }

                    $this->view->assign('route_name', $route_name);
                    $this->view->assign('params', $params);
                } else {
                    $app = false;
                }
            }

        } else {
            $app = array();
        }

        $idna = new waIdna();
        $domain_decoded = $idna->decode(siteHelper::getDomain());

        // Warning about deleting the last route for the application
        $last_app_route = false;
        if (!empty($app['id'])) {
            $routes = wa()->getRouting()->getByApp($app['id']);
            $app_routes = array();
            foreach ($routes as $_domain => $_routes) {
                foreach ($_routes as $_route) {
                    $app_routes[] = $_route;
                }
            }
            // If there is only one private rule (current) -
            // Show a warning about this when deleting
            if (count($app_routes) === 1) {
                $last_app_route = true;
            }
        }

        $this->view->assign(array(
            'site_url'        => wa()->getAppUrl('site'),
            'domain_decoded'  => $domain_decoded,
            'route_id'        => $route_id,
            'route'           => $route,
            'app_id'          => $app_id,
            'app'             => $app,
            'domain_id'       => siteHelper::getDomainId(),
            'domain'          => siteHelper::getDomain(),
            'locales'         => array('' => _w('Auto')) + waLocale::getAll('name'),
            'is_https'        => waRequest::isHttps(),
            'last_app_route' => $last_app_route,
        ));
    }

    protected function getParams($route_id, $config, $values)
    {
        $result = array();
        foreach ($config as $id => $info) {
            if (is_array($info)) {
                $info['id'] = $id;
                $result[$id] = array(
                    'name'  => ifset($info['name']),
                    'type'  => $info['type'],
                    'value' => $this->getHTML($route_id, $info, isset($values[$id]) ? $values[$id] : null)
                );
            } else {
                $result[] = $info;
            }
        }
        return $result;
    }

    protected function getHTML($route_id, $info, $value)
    {
        if (!isset($id)) {
            static $id = 0;
        }

        if (($value === null) && isset($info['default'])) {
            $value = $info['default'];
        }

        $view = wa('site')->getView();
        $template = wa()->getAppPath('templates/actions/routing/RoutingRenderSetting.html', 'site');
        $view->assign(array(
            'id'       => ++$id,
            'route_id' => $route_id,
            'info'     => $info,
            'value'    => $value,
        ));

        return $view->fetch($template);
    }
}
