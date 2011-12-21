<?php 

class siteSitemapConfig extends waSitemapConfig
{
    public function execute()
    {
        $domain_model = new siteDomainModel();
        $domain = $domain_model->getByName(waSystem::getInstance()->getRouting()->getDomain());
        
        if (!$domain) {
            return;
        }
        
        $routing = wa()->getRouting();
        $routes = $routing->getRoutes($domain['name']);
        $route_pages = array();
        foreach ($routes as $r_id => $r) {
        	if (isset($r['_pages'])) {
        		$u = $routing->getUrlByRoute($r, $domain['name']);
        		foreach ($r['_pages'] as $page_id) {
        			$route_pages[$page_id] = $u;
        		}
        	}
        }
         
        $page_model = new sitePageModel();
        $pages = $page_model->select('id,name,url,status,create_datetime,update_datetime')->where('domain_id = '.$domain['id'])->order('sort')->fetchAll('id');
        
        foreach ($pages as &$p) {
        	if (isset($route_pages[$p['id']])) {
        		$p['url'] = $route_pages[$p['id']].$p['url'];
        		if (strpos($p['url'], '<') === false) {
        			$this->addUrl($p['url'], $p['update_datetime'], 'monthly');
        		}
        	}
        }
        
    }
}