<?php 

class siteDefaultLayout extends waLayout
{
    protected $domain_id;
	public function execute()
	{	
	    $this->domain_id = siteHelper::getDomainId();	
						
		$this->view->assign('apps', siteHelper::getApps());	
		$this->view->assign('domain_id', $this->domain_id);
		$this->view->assign('domains', siteHelper::getDomains(true));
		$this->view->assign('pages', $this->getPages());
		$this->view->assign('domain_root_url', siteHelper::getDomainUrl());
		$this->view->assign('rights', array(
		    'admin' => $this->getUser()->isAdmin('site'),
		    'files' => $this->getRights('files'),
			'themes' => $this->getRights('themes'),
			'snippets' => $this->getRights('snippets'),
		));
	}	
	
	protected function getPages()
	{
	    $domain = siteHelper::getDomain();
	    $routing = wa()->getRouting();
	    $routes = $routing->getRoutes($domain);
	    $route_pages = array();
	    foreach ($routes as $r_id => $r) {
	        if (isset($r['_pages'])) {
	            $u = $routing->getUrlByRoute($r);
	            foreach ($r['_pages'] as $page_id) {
	                $route_pages[$page_id] = $u;
	            }
	        }
	    }
	    
	    $page_model = new sitePageModel();
	    $pages = $page_model->select('id,name,url,status')->where('domain_id = '.$this->domain_id)->order('sort')->fetchAll('id');
	    
	    foreach ($pages as &$p) {
	        if (isset($route_pages[$p['id']])) {
	            $p['url'] = $route_pages[$p['id']].$p['url'];
	        }
	    }
	    return $pages;	    
	}
}