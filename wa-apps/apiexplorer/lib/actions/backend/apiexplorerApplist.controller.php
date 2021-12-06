<?php

class apiexplorerApplistController extends apiexplorerJsonController
{
    public function execute()
    {
        $api_version = waRequest::get('version', 'v1');
        $force_renew = waRequest::get('renew', false);
        $login = waRequest::get('user', false, waRequest::TYPE_STRING_TRIM);
        $user = $login ? waUser::getByLogin($login) : wa()->getUser();

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
                    'description' => $app['description'],
                    'icon' => $app['icon'],
                    "version" => $app['version'],
                    'img' => $app['img'],
                    'swagger' => file_exists($file)
                ];
            }
            $cache->set($app_data);
            //wa_dumpc('NO CACHE');
        }

        $this->response = ['apps' => $app_data];
    }
}
