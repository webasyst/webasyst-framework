<?php

class siteRoutingAction extends waViewAction
{
    public function execute()
    {
        $domain = siteHelper::getDomain();
        $routes = wa()->getRouting()->getRoutes($domain);
        $apps = wa()->getApps();

        foreach ($routes as $route_id => &$route) {
            if (isset($route['app']) && isset($apps[$route['app']])) {
                $auth_apps[$route['app']] = true;
                $route['app'] = $apps[$route['app']];
            }
        }

        $this->view->assign(array(
            'routes' => $routes,
            'apps' => $apps,
            'url' => $this->getDomainUrl($domain),
            'domain_id' => siteHelper::getDomainId()
        ));
    }

    protected function getDomainUrl($domain)
    {
        $u1 = rtrim(wa()->getRootUrl(false, false), '/');
        $u2 = rtrim(wa()->getRootUrl(false, true), '/');
        $domain_parts = parse_url('http://'.$domain);
        $u = isset($domain_parts['path']) ? $domain_parts['path'] : '';
        if ($u1 != $u2 && substr($u, 0, strlen($u1)) == $u1) {
            $u = $u2.substr($u, strlen($u1));
        }
        return $domain_parts['host'].$u;
    }
}