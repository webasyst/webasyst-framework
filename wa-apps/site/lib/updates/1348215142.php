<?php


// old files
$files = array(
    'pages/sitePages.action.php',
    'pages/sitePages.controller.php',
    'pages/sitePagesDelete.controller.php',
    'pages/sitePagesSave.controller.php',
    'pages/sitePagesSort.controller.php',
    'pages/sitePagesUploadimage.controller.php',
    'design/siteDesign.action.php',
    'design/siteDesignDelete.controller.php',
    'design/siteDesignSave.controller.php',

);

// path to wa-apps/site/lib/actions/pages/
$path = $this->getAppPath('lib/actions/');

// remove old files
foreach ($files as $file) {
    if (file_exists($path.$file)) {
        unlink($path.$file);
    }
}

// try remove cache (for correct autoload)
try {
    $path_cache = waConfig::get('wa_path_cache').'/apps/site/';
    waFiles::delete($path_cache, true);
} catch (Exception $e) {
}

// add new fields to table pages
$model = new waModel();
try {
    $sql = "ALTER TABLE site_page
        ADD full_url VARCHAR(255) NULL DEFAULT NULL AFTER url,
        ADD route VARCHAR(255) NULL DEFAULT NULL,
        ADD parent_id INT(11) NULL DEFAULT NULL";
    $model->exec($sql);
} catch (waDbException $e) {
    // nothing if fields already exists
}

$model->exec("UPDATE site_page SET full_url = url WHERE parent_id IS NULL");

$domains = $model->query("SELECT id,name FROM site_domain")->fetchAll('name', true);

// set domain and route for pages
$routing_path = $this->getPath('config', 'routing');
if (file_exists($routing_path)) {
    $routing = include($routing_path);
    foreach ($routing as $domain => $domain_routes) {
        if (!isset($domains[$domain])) {
            continue;
        }
        $domain_id = $domains[$domain];
        $pages = false;
        foreach ($domain_routes as $route_id => $route) {
            if (isset($route['app']) && $route['app'] == 'site') {
                $data = array('domain_id' => $domain_id, 'route' => $route['url']);
                $sql = "UPDATE site_page SET route = s:route WHERE domain_id = i:domain_id";
                // if not exclude pages then settle all pages to first route of the site
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
            if (isset($route['app']) && $route['app'] == 'site' && isset($route['_exclude'])) {
                unset($routing[$domain][$route_id]['_exclude']);
                $save = true;
            }
        }
    }
    if ($save) {
        waUtils::varExportToFile($routing, $routing_path);
    }
}

