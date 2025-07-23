<?php

class siteConfigureRedirectDeleteController extends waJsonController
{
    public function execute()
    {
        $domain = siteHelper::getDomain();
        $routes = wa()->getRouting()->getRoutes($domain);

        $route_id = waRequest::post('route');
        $route = ifset($routes, $route_id, null);

        if ($route) {
            $old_route = $domain.'/'.$routes[$route_id]['url'];
            if (isset($routes[$route_id]['app'])) {
                $robots = new siteRobots($domain);
                $robots->delete($routes[$route_id]['app'], $routes[$route_id]['url']);
            }
            unset($routes[$route_id]);
            // save
            $path = $this->getConfig()->getPath('config', 'routing');
            $all_routes = file_exists($path) ? include($path) : array();
            $all_routes[$domain] = $routes;
            try {
                wa('site')->getConfig()->ensureSettlementForDomain($domain, null, $all_routes);
            } catch (Throwable $e) {
            }

            $params = array(
                'domain' => $domain,
                'route'  => $route,
            );

            wa()->event('route_delete.before', $params);

            $app_id = waRequest::request('app_id', ifset($route, 'app', ''));
            $domain_field = [];
            if ($app_id === 'site' && ($domain_id = waRequest::request('domain_id'))) {
                $domain_field = ['domain_id' => $domain_id];
            } elseif ($domain) {
                $domain_field = ['domain' => $domain];
            }

            $page_model = siteHelper::getPageModel($app_id);
            if (!waRequest::request('confirm_multiple_delete') && $page_model) {
                $min_count = $app_id === 'site' ? 1 : 0;
                if ($page_model->countByField($domain_field + ['route' => $route['url']]) > $min_count) {
                    $this->response = ['multiple_delete' => true];
                    return;
                }
            }

            if (!waUtils::varExportToFile($all_routes, $path)) {
                $this->errors = sprintf(_w('Settings could not be saved due to the insufficient file write permissions for the file "%s".'), 'wa-config/routing.php');
            } else {
                $this->logAction('route_delete', $old_route);
                wa()->event('route_delete.after', $params);

                $config_cache = waConfigCache::getInstance();
                $config_cache->setFileContents($path, $all_routes);

                if ($page_model && isset($route['url'])) {
                    $page_model->deleteByField($domain_field + ['route' => $route['url']]);
                }
            }
        }

        //Delete cache problem domains
        $cache_domain = new waVarExportCache('problem_domains', 3600, 'site/settings/');
        $cache_domain->delete();
        $this->response['routing_errors'] = siteHelper::getRoutingErrorsInfo();
    }
}
