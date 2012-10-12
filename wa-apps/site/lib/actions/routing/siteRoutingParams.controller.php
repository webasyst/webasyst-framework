<?php 

class siteRoutingParamsController extends waJsonController
{
    public function execute()
    {
        $path = $this->getConfig()->getPath('config', 'routing');
        if (file_exists($path)) { 
            $routes = include($path);
        } else {
            $routes = array();
        }
        $domain = siteHelper::getDomain();
        $route_id = waRequest::get('route');
        $other_params = waRequest::post('other_params', '');
        $other_params = explode("\n", $other_params);
        
        $app_id = $routes[$domain][$route_id]['app'];
        if ($app_id == 'site') {
            if ($title = siteHelper::getDomain('title')) {
                $name = $title;
            } else {
                $app_settings_model = new waAppSettingsModel();
                $name = $app_settings_model->get('webasyst', 'name', 'Webasyst');
            }            
        } else {
            $app = wa()->getAppInfo($app_id);
            $name = $app['name'];
        }
        
        $t = array(
            'url' => $routes[$domain][$route_id]['url'],
            'app' => $routes[$domain][$route_id]['app'],
        );

        foreach ($routes[$domain][$route_id] as $k => $v) {
            if (substr($k, 0, 1) == '_') {
                $t[$k] = $routes[$domain][$route_id][$k];
            }
        }
        
        $routes[$domain][$route_id] = $t;
        
        foreach ($other_params as $string) {
            $string = trim($string);
            if ($string && strpos($string, '=') !== false) {
                $string = explode('=', $string, 2);
                if ($string[0]) {
                    $routes[$domain][$route_id][$string[0]] = $string[1];
                }
            }
        }

        $params = waRequest::post('params', array());
        foreach ($params as $key => $value) {
            if ($key != '_name' || $value != $name || (isset($routes[$domain][$route_id]['_name']) && $value != $routes[$domain][$route_id]['_name'])) {
                $routes[$domain][$route_id][$key] = $value;
            }
        }
        
        if (!$routes[$domain][$route_id]['locale']) {
            unset($routes[$domain][$route_id]['locale']);
        }
              
        waUtils::varExportToFile($routes, $path);        
    }    
}