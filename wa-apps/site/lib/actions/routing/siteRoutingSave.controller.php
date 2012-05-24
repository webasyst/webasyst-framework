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

            $route = $this->getRouteCount($routes);
            
            if ($url == '*') {
                $routes[$route] = array('url' => $url, 'app' => $app_id);
            } else {
                if (substr($url, -1) == '/') {
                    $url .= '*';
                } elseif (substr($url, -1) != '*' && strpos(substr($url, -5), '.') === false) {
                    $url .= '/*';
                }
                $routes = array($route => array('url' => $url, 'app' => $app_id)) + $routes;
            }
            $app_info = wa()->getAppInfo($app_id);
            $this->response['app'] = array(
                'id' => $app_id,
                'icon' => $app_info['icon'],
                'name' => $app_info['name']
            );
            $this->response['route'] = $route;
            $this->response['url'] = htmlspecialchars($url);
            $this->save($domain, $routes);
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
        } elseif (($route = waRequest::post('route')) !== null) {
            if (isset($routes[$route]) && $routes[$route]['url'] != $url) {
                $routes[$route]['url'] = $url;
                $this->save($domain, $routes);
            }
            $this->response['route'] = $route; 
            $this->response['url'] = htmlspecialchars($url);
        }
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