<?php

class contactsConfig extends waAppConfig
{
    public function getRouting($route = array())
    {
        $routes = parent::getRouting($route);
        /**
         * Extend routing via plugin routes
         * @event routing
         * @param array $routes
         * @return array routes collected for every plugin
         */
        $result = wa()->event(array('contacts', 'routing'), $routes);
        $all_plugins_routes = array();
        foreach ($result as $plugin_id => $routing_rules) {
            if ($routing_rules) {
                $plugin = str_replace('-plugin', '', $plugin_id);
                foreach ($routing_rules as $url => &$route) {
                    if (!is_array($route)) {
                        list($route_ar['module'], $route_ar['action']) = explode('/', $route);
                        $route = $route_ar;
                    }
                    $route['plugin'] = $plugin;
                    $all_plugins_routes[$url] = $route;
                }
                unset($route);
            }
        }
        $routes = array_merge($all_plugins_routes, $routes);

        return $routes;
    }
} 