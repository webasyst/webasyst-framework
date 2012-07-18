<?php 

class sitePagesAction extends waPageEditAction
{
    public function execute()
    {
        $domain_id = siteHelper::getDomainId();
        $domain = siteHelper::getDomain();

        $this->id = waRequest::get('id');
        $page_model = new sitePageModel();
        if (!$this->id || !($page = $page_model->getById($this->id))) {
            $this->id = null;
            $page = array();
        }

        $this->getPageParams();

        $routes = wa()->getRouting()->getRoutes($domain);
        $site_route = false;

        foreach ($routes as $r_id => $r) {
            if (!isset($r['app']) || ($r['app'] != 'site') || (strpos($r['url'], '<url') !== false)) {
                unset($routes[$r_id]);
                continue;
            }
            $routes[$r_id] = array(
                'url' => waRouting::getUrlByRoute($r, $domain),
                'exclude' => isset($r['_exclude']) ? in_array($this->id, $r['_exclude']) : false
            );
            if (!$routes[$r_id]['exclude'] && !$site_route) {
                $site_route = true;
                $this->view->assign('url', waRouting::getUrlByRoute($r, $domain));
            }
        }

        $this->view->assign('page', $page);
        $this->view->assign('preview_hash', $this->getPreviewHash());

        $this->view->assign('routes', $routes);

        $this->view->assign('domain_id', $domain_id);
        $this->view->assign('domain', $domain);
        $this->view->assign('upload_url', wa()->getDataUrl('img', true));

        $this->view->assign('lang', substr(wa()->getLocale(), 0, 2));
    }
}