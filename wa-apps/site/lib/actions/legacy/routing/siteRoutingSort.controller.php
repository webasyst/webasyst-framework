<?php

class siteRoutingSortController extends waJsonController
{
    public function execute()
    {
        $path = $this->getConfig()->getPath('config', 'routing');
        $all_routes = file_exists($path) ? include($path) : array();
        $domain = siteHelper::getDomain();

        $route = waRequest::post('route');
        $offset = waRequest::post('pos');

        $routes = wa()->getRouting()->getRoutes($domain);
        $sortable = ifset($routes,$route, array());

        if ($sortable) {
            //Delete sortable for routes.
            unset($routes[$route]);

           //Take the non-sortable part, add the desired element and add the remaining
            $result = array_slice($routes, 0, $offset, true)
                + array($route => $sortable)
                + array_slice($routes, $offset, NULL, true);

            $all_routes[$domain] = $result;
            waUtils::varExportToFile($all_routes, $path);
        }

        //Delete cache problem domains
        $cache_domain = new waVarExportCache('problem_domains', 3600, 'site/settings/');
        $cache_domain->delete();

        $this->response['routing_errors'] = siteHelper::getRoutingErrorsInfo();
    }
}
