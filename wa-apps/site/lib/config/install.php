<?php 

$domain_model = new siteDomainModel();
$domain = $this->getDomain();
if ($d = $domain_model->getByName($domain)) {
    $domain_id = $d['id'];
} else {
    $domain_id = $domain_model->insert(array('name' => $domain));
}

$page_model = new sitePageModel();
$data = array(
    'domain_id' => $domain_id,
    'name' => _w('Welcome'),
    'title' => '',
    'content' => '',
    'url' => '',
    'full_url' => '',
    'status' => 1,
);

$routes = wa()->getRouting()->getRoutes($domain);
foreach ($routes as $r_id => $r) {
    if (is_array($r) && isset($r['app']) && $r['app'] == 'site') {
        $data['route'] = $r['url'];
        $page_model->add($data);
        break;
    }
}