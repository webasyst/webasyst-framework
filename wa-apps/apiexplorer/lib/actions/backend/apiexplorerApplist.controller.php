<?php

class apiexplorerApplistController extends apiexplorerJsonController
{
    public function execute()
    {
        $api_version = waRequest::get('version', 'v1');
        $force_renew = waRequest::get('renew', false);
        $login = waRequest::get('user', false, waRequest::TYPE_STRING_TRIM);
        $user = ($login && wa()->getUser()->isAdmin()) ? waUser::getByLogin($login) : wa()->getUser();

        $key = 'app_list/' . $user->getId();
        $cache = new waVarExportCache($key, 3600);
        $app_data = $cache->get();

        if (!$app_data || $force_renew) {
            $apps = ($user == null) ? [] : $user->getApps();
            $app_data = [];
            foreach ($apps as $app_id => $app) {
                $file = wa()->getAppPath('api/swagger/' . $api_version . '.yaml', $app_id);
                $app_data[$app_id] = [
                    'id' => $app['id'],
                    'name' =>  $app['name'],
                    'icon' => $app['icon'],
                    "version" => $app['version'],
                    'img' => $app['img'],
                    'swagger' => file_exists($file)
                ];
                if (isset($app['description'])) {
                    $app_data[$app_id]['description'] = $app['description'];
                }
            }
            $cache->set($app_data);
        }

        $this->response = ['apps' => $app_data];
    }
}
