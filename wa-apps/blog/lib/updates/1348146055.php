<?php

// old files
$files = array(
    'blogPages.action.php',
    'blogPagesDelete.controller.php',
    'blogPagesSave.controller.php',
    'blogPagesSort.controller.php',
    'blogPagesUploadimage.controller.php',
);

// path to wa-apps/blog/lib/actions/pages/
$path = $this->getAppPath('lib/actions/pages/');

// remove old files
foreach ($files as $file) {
    if (file_exists($path.$file)) {
        unlink($path.$file);
    }
}

// try remove cache (for correct autoload)
try {
    $path_cache = waConfig::get('wa_path_cache').'/apps/blog/';
    waFiles::delete($path_cache, true);
} catch (Exception $e) {
}


// add new fields to table pages
$model = new waModel();
try {
    $sql = "ALTER TABLE blog_page
        ADD full_url VARCHAR(255) NULL DEFAULT NULL AFTER url,
        ADD domain VARCHAR(255) NULL DEFAULT NULL,
        ADD route VARCHAR(255) NULL DEFAULT NULL,
        ADD parent_id INT(11) NULL DEFAULT NULL";
    $model->exec($sql);
} catch (waDbException $e) {
    // nothing if fields already exists
}

$model->exec("UPDATE blog_page SET full_url = url WHERE parent_id IS NULL");

// set domain and route for pages
$routing_path = $this->getPath('config', 'routing');
if (file_exists($routing_path)) {
    $routing = include($routing_path);
    $pages = false;
    foreach ($routing as $domain => $domain_routes) {
        foreach ($domain_routes as $route_id => $route) {
            if (isset($route['app']) && $route['app'] == 'blog') {
                $data = array('domain' => $domain, 'route' => $route['url']);
                $sql = "UPDATE blog_page SET domain = s:domain, route = s:route WHERE domain IS NULL";
                // if not exclude pages then settle all pages to first route of the blog
                if (empty($route['_exclude'])) {
                    if ($pages) {
                        $data['ids'] = $pages;
                        $sql .= " AND id IN (i:ids)";
                    }
                    $model->exec($sql, $data);
                    break 2;
                } else {
                    if ($pages === false) {
                        $sql .= " AND id NOT IN (i:ids)";
                        $pages = $data['ids'] = $route['_exclude'];
                    } else {
                        $data['ids'] = array_diff($pages, $route['_exclude']);
                        $sql .= " AND id IN (i:ids)";
                        $pages = array_diff($pages, $data['ids']);
                        if (!$pages) {
                            break 2;
                        }
                    }
                    if (!isset($data['ids']) || $data['ids']) {
                        $model->exec($sql, $data);
                    }
                }
            }
        }
    }
    // remove _exclude from routing
    $save = false;
    foreach ($routing as $domain => $domain_routes) {
        foreach ($domain_routes as $route_id => $route) {
            if (isset($route['app']) && $route['app'] == 'blog' && isset($route['_exclude'])) {
                unset($routing[$domain][$route_id]['_exclude']);
                $save = true;
            }
        }
    }
    if ($save) {
        waUtils::varExportToFile($routing, $routing_path);
    }
}

