<?php 

$domain_model = new siteDomainModel();
$domain = $this->getDomain();
if ($d = $domain_model->getByName($domain)) {
    $domain_id = $d['id'];
} else {
    $domain_id = $domain_model->insert(array('name' => $domain));
}

$page_model = new sitePageModel();
$page_id = $page_model->add(array(
    'domain_id' => $domain_id,
    'name' => _w('Welcome'),
    'title' => '',
    'content' => file_get_contents(dirname(__FILE__).'/data/welcome.html'),
    'url' => '',
    'status' => 1,
));

$routes = wa()->getRouting()->getRoutes($domain);
foreach ($routes as $r_id => $r) {
    if (is_array($r) && isset($r['app']) && $r['app'] == 'site') {
        // add page to routing
        if (!isset($r['_pages'])) {
            $routes[$r_id]['_pages'] = array();
        }
        $routes[$r_id]['_pages'][] = $page_id;
        // save routing
        $path = $this->getPath('config', 'routing');
        $all_routes = file_exists($path) ? include($path) : array();
        $all_routes[$domain] = $routes;
        waUtils::varExportToFile($all_routes, $path);                
        break;
    }
}