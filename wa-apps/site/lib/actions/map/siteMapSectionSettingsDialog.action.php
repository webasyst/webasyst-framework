<?php
/**
 * Create/Edit Dialog for Redirect routes in Settings tab for a single Site (domain) in UI 2.0
 */

class siteMapSectionSettingsDialogAction extends waViewAction
{

    protected $page_model;

    /**
     * @throws waException
     */
    public function execute()
    {
        $route_id = waRequest::request('route', '');
        $routes = wa()->getRouting()->getRoutes(siteHelper::getDomain());

        $has_route_id = boolval($route_id || strlen($route_id));
        if ($has_route_id) {
            if (!isset($routes[$route_id])) {
                throw new waException('Route not found.', 404);
            }
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
            $app_id = waRequest::request('app', key($apps));
            if ($app_id == ':text') {
                $route['static_content'] = '';
                $route['static_content_type'] = '';
            }

            if ($app_id === 'site') {
                $app_url = siteHelper::getIncrementUrl($routes);
            } else {
                $app_url = siteHelper::getAlternativeAppUrl($app_id);
                $max_index = -1;
                foreach ($routes as $r) {
                    if (isset($r['app']) && $app_id === $r['app']) {
                        $m = [];
                        $url = rtrim($r['url'], '/*');
                        if (preg_match('/^'.$app_url.'(-\d*)?$/', $url, $m)) {
                            $i = intval(ltrim($m[1] ?? 0, '-'));
                            $max_index = $i > $max_index ? $i : $max_index;
                        }
                    }
                }
                if ($max_index > -1) {
                    $app_url = $app_url . '-' . ++$max_index;
                }
            }
        }

        if ($app_id) {

            if ($app_id == ':text') {
                $app = array();
                $this->fixRouteUrl($route_id, $route);
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
                        if (wa()->appExists($app_id)) {
                            // Make sure routing params are not cached, as wa()->getAppInfo() does.
                            // This makes difference for routing params generated on-the-fly (e.g. shop checkout_storefront_id).
                            $app['routing_params'] = wa($app_id)->getConfig()->getInfo('routing_params');
                        }
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

                    if ($has_route_id) {
                        $route_name = ifset($route['_name'], '');
                        if (!$route_name) {
                            if ($app_id === 'site') {
                                $app_settings_model = new waAppSettingsModel();
                                $route_name = $app_settings_model->get('webasyst', 'name', _ws('My company'));
                            } else {
                                $route_name = ifset($app['name'], '');
                            }
                        }
                    } else {
                        if ($app_id === 'site') {
                            $route['show_over_another_section'] = false;
                        }
                        $route_name = siteHelper::getAlternativeAppNames($app['id'], $app['name']);
                        $app['name'] = $route_name;
                    }

                    $this->fixRouteUrl($route_id, $route);
                    $this->view->assign('route_name', $route_name);
                    $this->view->assign('params', $params);
                } else {
                    $app = false;
                }
            }

        } else {
            $app = array();
        }

        $has_root_settlement = false;
        $misconfigured_settlement = false;
        foreach ($routes as $_route_id => $_route) {
            if ($route_id == $_route_id) {
                $misconfigured_settlement = $has_root_settlement;
                break;
            } else if ($_route['url'] === '*' && !$has_root_settlement) {
                $has_root_settlement = true;
            }
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

        if ($app_id == 'site') {
            $this->pageData();
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
            'last_app_route'  => $last_app_route,
            'misconfigured_settlement' => $misconfigured_settlement,
            'app_url'         => ifset($app_url, '*'),
            'is_main_page'    => !$misconfigured_settlement && empty($route['show_over_another_section']) && rtrim(ifset($route['url'], ''), '*') === '',
            'preview_hash'    => siteHelper::getPreviewHash(),
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
                    'value' => $this->getHTML($route_id, $info, isset($values[$id]) ? $values[$id] : '')
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

        if (!empty($route_id) && $info['type'] == 'select') {
            if (!isset($info['items'][$value])) {
                $new_value = ifset($value, '');
                $info['items'] = array($new_value => $new_value) + $info['items'];
            }
        }

        $view = wa('site')->getView();
        $template = wa()->getAppPath('templates/actions/configure/ConfigureRenderSetting.html', 'site');
        $view->assign(array(
            'id'       => ++$id,
            'route_id' => $route_id,
            'info'     => $info,
            'value'    => $value,
        ));

        return $view->fetch($template);
    }

    protected function whichUI($app_id = null)
    {
        $ui = $this->getRequest()->get('ui');

        // control UI version of cheat sheet UI block
        // it is all temporary
        if (!$ui) {
            return parent::whichUI($app_id);
        }

        $ui = $ui === '2.0' ? '2.0' : '1.3';
        return $ui;
    }

    protected function pageData()
    {
        $page_id = waRequest::request('page', '');

        $page = array();
        if ($page_id) {
            $page_model = $this->getPageModel();
            $page = $page_model->getById($page_id);
            if (empty($page)) {
                throw new waException('Page not found', 404);
            }
        }

        $url = '';
        if ($page) {
            if ($page['parent_id']) {
                $parent = $page_model->getById($page['parent_id']);
                $url = $parent['full_url'] ? rtrim($parent['full_url'], '/').'/' : '';
            }

            $domain = null;
            if (!empty($page['domain'])) {
                $domain = $page['domain'];
            } elseif (!empty($page['domain_id'])) {
                $domain = ifset(siteHelper::getDomains()[$page['domain_id']]);
            }
            if ($domain) {
                $route = $page['route'];
                $url = 'http://'.$domain.'/'.wa()->getRouting()->clearUrl($route).$url;
            }
        }

        $data = [
                'url'          => $url,
                'page'         => $page,
                'lang'         => substr(wa()->getLocale(), 0, 2),
            ] + $this->getPageParams($page_id);

        $data['page_edit'] = wa()->event('page_edit', $data);

        /**
         * Backend settings page
         * UI hook allow extends backend settings page
         * @event backend_page_edit
         * @param array $page
         * @return array[string][string]string $return[%plugin_id%]['action_button_li'] html output
         * @return array[string][string]string $return[%plugin_id%]['settings_section'] html output
         * @return array[string][string]string $return[%plugin_id%]['section'] html output
         */
        $data['backend_page_edit'] = wa()->event('backend_page_edit', $page, [
            'action_button_li',
            'section',
            'settings_section'
        ]);

        $this->view->assign($data);
    }

    protected function getPageParams($id)
    {
        $params = array();
        $vars = array(
            'keywords' => _ws('META Keywords'),
            'description' => _ws('META Description')
        );

        if ($id) {
            $params = $this->getPageModel()->getParams($id);
        }

        $og_params = array();
        foreach ($params as $k => $v) {
            if (substr($k, 0, 3) == 'og_') {
                $og_params[substr($k, 3)] = $v;
                unset($params[$k]);
            }
        }

        $main_params = array();
        foreach ($vars as $v => $t) {
            if (isset($params[$v])) {
                $main_params[$v] = $params[$v];
                unset($params[$v]);
            } else {
                $main_params[$v] = '';
            }
        }

        return array(
            'vars' => $vars,
            'page_params' => $main_params,
            'other_params' => $params,
            'og_params' => $og_params
        );
    }

    protected function getPageModel()
    {
        if (!$this->page_model) {
            $this->page_model = $this->getAppId().'PageModel';
        }
        return new $this->page_model();
    }

    protected function fixRouteUrl($route_id, array &$route)
    {
        if (empty($route['app'])) {
            return;
        }
        $app_id = $route['app'];
        if (is_array($app_id)) {
            $app_id = $app_id['id'];
        }

        if ($route_id || strlen((string)$route_id)) {
            $route['is_broken_route_url'] = siteHelper::isBrokenAppRouteUrl($route);
            if (!$route['is_broken_route_url']) {
                if ($app_id === 'site') {
                    $route['show_over_another_section'] = substr($route['url'], -1) !== '*';
                }
                $route['url'] = rtrim($route['url'], '/*');
            }
        } else {
            $route['is_broken_route_url'] = false;
            $route['url'] = ifset($route, 'url', '');
            if (!isset($route['show_over_another_section']) && $app_id === 'site') {
                $route['show_over_another_section'] = !$route['url'] || substr($route['url'], -1) !== '*';
            }
        }
    }
}
