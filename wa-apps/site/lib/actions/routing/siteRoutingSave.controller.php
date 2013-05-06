<?php

class siteRoutingSaveController extends waJsonController
{
    public function execute()
    {
        $domain = siteHelper::getDomain();
        $routes = wa()->getRouting()->getRoutes($domain);

        $url = waRequest::post('url');
        $app_id = waRequest::post('app');
        if ($app_id) {
            if (!$url) {
                $url = '*';
            }

            $route_id = $this->getRouteCount($routes);
            $route = array('url' => $url, 'app' => $app_id);
            $app_info = wa()->getAppInfo($app_id);
            if (isset($app_info['routing_params']) && is_array($app_info['routing_params'])) {
                foreach ($app_info['routing_params'] as $k => $v) {
                    $route[$k] = $v;
                }
            }

            if ($url == '*') {
                $routes[$route_id] = $route;
            } else {
                if (substr($url, -1) == '/') {
                    $route['url'] .= '*';
                } elseif (substr($url, -1) != '*' && strpos(substr($url, -5), '.') === false) {
                    $route['url'] .= '/*';
                }
                $routes = array($route_id => $route) + $routes;
            }
            $this->response['app'] = array(
                'id' => $app_id,
                'icon' => $app_info['icon'],
                'name' => $app_info['name']
            );
            $this->response['route'] = $route_id;
            $this->response['url'] = htmlspecialchars($route['url']);
            $this->save($domain, $routes);
            $robots = new siteRobots($domain);
            $robots->add($app_id, $route['url']);
            $this->log('route_add');
        } elseif (($redirect = waRequest::post('redirect')) !== null) {
            $route = waRequest::post('route');
            if ($route !== null) {
                $routes[$route]['url'] = $url;
                $routes[$route]['redirect'] = $redirect;
            } else {
                if (substr($url, -1) != '*' && substr($url, -1) != '/' && strpos(substr($url, -5), '.') === false) {
                    $url .= '/';
                }   
                if (!$redirect) {
                    $redirect = '/';
                }
                if (substr($redirect, -1) != '*' && substr($redirect, -1) != '/' && strpos(substr($redirect, -5), '.') === false) {
                    $redirect .= '/';
                }
                $route = $this->getRouteCount($routes);
                $routes = array($route => array('url' => $url, 'redirect' => $redirect)) + $routes;
            }
            $this->response['route'] = $route;
            $this->response['url'] = htmlspecialchars($url);
            $this->response['redirect'] = htmlspecialchars($redirect);
            $this->save($domain, $routes);
            $this->log('route_edit');
        } elseif (($route = waRequest::post('route')) !== null) {
            if (isset($routes[$route]) && $routes[$route]['url'] != $url) {
                $this->updatePagesRoute($routes[$route], $url);
                $robots = new siteRobots($domain);
                $robots->update($routes[$route]['app'], $routes[$route]['url'], $url);
                $routes[$route]['url'] = $url;
                $this->save($domain, $routes);
                $this->log('route_edit');
            }
            $this->response['route'] = $route; 
            $this->response['url'] = htmlspecialchars($url);
        }
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
    
    protected function getRouteCount($routes) 
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
    
    protected function save($domain, $routes)
    {
        $path = $this->getConfig()->getPath('config', 'routing');
        $all_routes = file_exists($path) ? include($path) : array();
        $all_routes[$domain] = $routes;
        waUtils::varExportToFile($all_routes, $path);        
    }
}