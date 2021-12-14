<?php

class uiConfig extends waAppConfig
{
    // see also a hack in FrontController->dispatch()
    public function getRouting($route = array())
    {
        if ($this->routes === null) {
            $this->routes = $this->getRoutingRules();
        }
        return $this->routes;
    }

    protected function getRoutingRules($route = array())
    {
        $routes = array();
        if ($this->getEnvironment() === 'backend') {
            $path = $this->getRoutingPath('backend');
            if (file_exists($path)) {
                $routes = array_merge($routes, include($path));
            }
        }

        $path = $this->getRoutingPath('frontend');
        if (file_exists($path)) {
            $routes = array_merge($routes, include($path));
        }
        return array_merge($this->getPluginRoutes($route), $routes);
    }

    protected function getRoutingPath($type)
    {
        if ($type === null) {
            $type = $this->getEnvironment();
        }
        $filename = ($type === 'backend') ? 'routing.backend.php' : 'routing.php';
        $path = $this->getConfigPath($filename, true, $this->application);
        if (!file_exists($path)) {
            $path = $this->getConfigPath($filename, false, $this->application);
        }
        return $path;
    }

    protected function getPluginRoutes($route)
    {
        /**
         * Extend routing via plugin routes
         * @event routing
         * @param array $routes
         * @return array $routes routes collected for every plugin
         */
        $result = wa()->event(array($this->application, 'routing'), $route);
        $all_plugins_routes = array();
        foreach ($result as $plugin_id => $routing_rules) {
            if (!$routing_rules) {
                continue;
            }
            $plugin = str_replace('-plugin', '', $plugin_id);
            foreach ($routing_rules as $url => & $route) {
                if (!is_array($route)) {
                    list($route_ar['module'], $route_ar['action']) = explode('/', $route) + array(1 => '');
                    $route = $route_ar;
                }
                if (!array_key_exists('plugin', $route)) {
                    $route['plugin'] = $plugin;
                }
                $all_plugins_routes[$url] = $route;
            }
            unset($route);
        }
        return $all_plugins_routes;
    }
}