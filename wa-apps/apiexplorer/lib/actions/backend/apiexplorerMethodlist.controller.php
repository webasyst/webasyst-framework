<?php

class apiexplorerMethodlistController extends apiexplorerJsonController
{
    public function execute()
    {
        $api_version = waRequest::get('version', 'v1');
        $force_renew = waRequest::get('renew', false);
        $user = wa()->getUser();

        $key = 'app_methods/' . wa()->getUser()->getId();
        $cache = new waVarExportCache($key, 3600);
        $methods = $cache->get();

        if (!$methods || $force_renew) {
            $methods = [];
            $apps = array_keys($user->getApps());
            foreach($apps as $app) {
                $app2api = new apiexplorerMethods($app);
                $app_methods = $app2api->getMethods();
                foreach($app_methods as $name => $method) {
                    $methods[$app][$name] = ['type' => $method->getType()];
                }
            }
            $cache->set($methods);
        }
        $this->response = ['methods' => $methods];
    }
}
