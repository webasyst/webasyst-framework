<?php
/**
 * Create/Edit Dialog for Redirect routes in Settings tab for a single Site (domain) in UI 2.0
 */

class siteConfigureRedirectDialogAction extends waViewAction
{    
    public function execute()
    {
        $route_id = waRequest::request('route', '');
        $domain_id = waRequest::request('domain_id', siteHelper::getDomain(), waRequest::TYPE_INT);
        $routes = wa()->getRouting()->getRoutes(siteHelper::getDomain());
        if ($route_id && !isset($routes[$route_id])) {
            throw new waException('Route not found', 404);
        }

        if ($route_id || strlen($route_id)) {
            $route = $routes[$route_id];
            //$app_id = ifset($route['app']);
        } else {
            $route = array();
        }
        $domain_name = siteHelper::getDomain();

        $this->view->assign(array(
            'route_id'        => $route_id,
            'route'           => $route,
            'site_url'        => wa()->getAppUrl('site'),
            'domain_name'     => $domain_name,
            'domain_id'       => $domain_id,
            //'html' => 'templates/actions/configure/RedirectConfigureDialog.html'
        ));
    }
}