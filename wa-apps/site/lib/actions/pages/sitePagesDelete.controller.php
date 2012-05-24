<?php 

class sitePagesDeleteController extends waPageDeleteController
{
    public function execute()
    {
        $id = waRequest::get('id');
        $domain_id = siteHelper::getDomainId();
        if ($domain_id && $id) {
            $page_model = new sitePageModel();
            $page = $page_model->getById($id);
            if ($page && $page['domain_id'] == $domain_id) {
                $page_model->delete($id);
                $this->removeFromRouting($id);
            }
        }
    }

    protected function removeFromRouting($id)
    {
        $save = false;
        $domain = siteHelper::getDomain();
        $routes = wa()->getRouting()->getRoutes();
        foreach ($routes as $r_id => $r) {
            if (isset($r['app']) && $r['app'] == 'site' && isset($r['_exclude']) && in_array($id, $r['_exclude'])) {
                unset($r['_exclude'][array_search($id, $r['_exclude'])]);
                $routes[$r_id] = $r;
                $save = true;
            }
        }
        if ($save) {
            $path = $this->getConfig()->getPath('config', 'routing');
            $all_routes = file_exists($path) ? include($path) : array();
            $all_routes[$domain] = $routes;
            waUtils::varExportToFile($all_routes, $path);
        }
    }
}