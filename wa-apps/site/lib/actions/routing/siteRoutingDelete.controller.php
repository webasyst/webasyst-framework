<?php

class siteRoutingDeleteController extends waJsonController
{
    public function execute()
    {
        $domain = siteHelper::getDomain();
        $routes = wa()->getRouting()->getRoutes($domain);

        $route_id = waRequest::post('route');
        $route = ifset($routes, $route_id, null);
        
        if ($route) {
            $old_route = $domain.'/'.$routes[$route_id]['url'];
            if (isset($routes[$route_id]['app'])) {
                $robots = new siteRobots($domain);
                $robots->delete($routes[$route_id]['app'], $routes[$route_id]['url']);
            }
            unset($routes[$route_id]);
            // save
            $path = $this->getConfig()->getPath('config', 'routing');
            $all_routes = file_exists($path) ? include($path) : array();
            $all_routes[$domain] = $routes;

            $params = array(
                'domain' => $domain,
                'route'  => $route,
            );

            wa()->event('route_delete.before', $params);

            if (!waUtils::varExportToFile($all_routes, $path)) {
                $this->errors = sprintf(_w('Settings could not be saved due to the insufficient file write permissions for the file "%s".'), 'wa-config/routing.php');
            } else {
                $this->logAction('route_delete', $old_route);
                wa()->event('route_delete.after', $params);
            }
        }

        //Delete cache problem domains
        $cache_domain = new waVarExportCache('problem_domains', 3600, 'site/settings/');
        $cache_domain->delete();
        $this->response['routing_errors'] = siteHelper::getRoutingErrorsInfo();
    }
}
