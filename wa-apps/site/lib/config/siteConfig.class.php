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
        if ($this->_routes === null || $dispatch) {
            $routes = parent::getRouting($route);
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
            if ($dispatch) {
                return $routes;
            }
            $this->_routes = $routes;
        }
        return $this->_routes;
    }
}