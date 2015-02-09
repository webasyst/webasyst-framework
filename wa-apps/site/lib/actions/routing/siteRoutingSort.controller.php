<?php

class siteRoutingSortController extends waJsonController
{
    public function execute()
    {
        $path = $this->getConfig()->getPath('config', 'routing');
        $all_routes = file_exists($path) ? include($path) : array();
        $domain = siteHelper::getDomain();

        $route = waRequest::post('route');
        $pos = waRequest::post('pos');
        $result = array();
        $i = 0;
        $routes = wa()->getRouting()->getRoutes($domain);
        $apps = wa()->getApps();
        foreach ($routes as $k => $v) {
            if ($pos == $i) {
                $result[$route] = $routes[$route];
            }
            if ($route != $k) {
                if ((isset($v['app']) && isset($apps[$v['app']]))|| isset($v['redirect'])) {
                    $i++;
                }
                $result[$k] = $v;
            }
        }
        if ($pos == $i) {
            $result[$route] = $routes[$route];
        }        
        $all_routes[$domain] = $result;
        waUtils::varExportToFile($all_routes, $path);
    }
}