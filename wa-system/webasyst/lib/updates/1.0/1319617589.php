<?php

$path = waConfig::get('wa_path_config').'/routing.php';
if (file_exists($path)) {
    $routes = include($path);
    foreach ($routes as $domain => $rules) {
        $result = array();
        foreach ($rules as $rule_id => $r) {
            if (strpos($r['url'], '[') !== false) {
                $r['url'] = preg_replace('/\[i:([a-z_]+)\]/ui', '<$1:\d+>', $r['url']);
                $r['url'] = preg_replace('/\[s?:([a-z_]+)\]/ui', '<$1>', $r['url']);
            }
            $result[] = $r;
        }
        $routes[$domain] = $result;
    }
    waUtils::varExportToFile($routes, $path);
}