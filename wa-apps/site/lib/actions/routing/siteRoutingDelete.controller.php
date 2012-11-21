<?php

class siteRoutingDeleteController extends waJsonController
{
    public function execute()
    {
        $domain = siteHelper::getDomain();
        $routes = wa()->getRouting()->getRoutes($domain);

        $route_id = waRequest::post('route');
        
        if (isset($routes[$route_id])) {
            if (isset($routes[$route_id]['app'])) {
                $robots = new siteRobots($domain);
                $robots->delete($routes[$route_id]['app'], $routes[$route_id]['url']);
            }
            unset($routes[$route_id]);
            // save
            $path = $this->getConfig()->getPath('config', 'routing');
            $all_routes = file_exists($path) ? include($path) : array();
            $all_routes[$domain] = $routes;
            waUtils::varExportToFile($all_routes, $path);
            $this->log('route_delete');
        }
    }
}