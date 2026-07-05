<?php

$path = waConfig::get('wa_path_config').'/routing.php';
if (file_exists($path)) {
    $save = false;
    $routes = include($path);
    foreach ($routes as $domain => $rules) {
        if (!is_array($rules)) {
            continue;
        }
        foreach ($rules as $rule_id => $r) {
            if (is_array($r) && ifset($r, 'app', '') == 'site' && empty($r['priority_settlement'])) {
                $routes[$domain][$rule_id] = $r + [
                    'priority_settlement' => true,
                ];
                $save = true;
            }
        }
    }
    if ($save) {
        waUtils::varExportToFile($routes, $path);
    }
}
