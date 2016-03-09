<?php

class siteSettingsDeleteController extends waJsonController
{
    public function execute()
    {
        $domain_id = waRequest::post('domain_id');
        if ($domain_id) {
            // check domain
            $domain_model = new siteDomainModel();
            $domain = $domain_model->getById($domain_id);
            $route = waRequest::post('route');
            if ($domain) {
                // delete from routing
                $path = $this->getConfig()->getPath('config', 'routing');
                if (file_exists($path)) {
                    $routes = include($path);
                    if (isset($routes[$domain['name']])) {
                        // delete route
                        if ($route && isset($routes[$domain['name']][$route])) {
                            unset($routes[$domain['name']][$route]);
                            // save new routing config
                            waUtils::varExportToFile($routes, $path);
                        }
                        // delete site/domain
                        elseif (!$route) {
                            unset($routes[$domain['name']]);
                            // save new routing config
                            waUtils::varExportToFile($routes, $path);
                        }
                    }
                }
                if (!$route) {
                    // delete site files (favicon, etc.)
                    waFiles::delete(wa()->getDataPath('data/'.$domain['name']), true);
                    // delete site from db
                    $domain_model->deleteById($domain_id);
                    wa('site')->event('domain_delete', $domain);
                    $this->logAction('site_delete');
                }

            }
        }
    }
}