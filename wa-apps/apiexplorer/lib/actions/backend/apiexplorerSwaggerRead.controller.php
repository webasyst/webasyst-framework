<?php

class apiexplorerSwaggerReadController extends apiexplorerJsonController
{
    public function execute()
    {
        $app = waRequest::get('app', false);
        $api_version = waRequest::get('version', 'v1');
        $user = wa()->getUser();
        $apps = array_keys($user->getApps());

        if ($app && in_array($app, $apps)) {
            $file = wa()->getAppPath('api/swagger/' . $api_version . '.yaml', $app);
            if (file_exists($file)) {
                waFiles::readfile($file, $app . '_' . $api_version . '.yaml');
            } else {
                $this->setError('404 Страница бляд не найдена!');
            }
        } else {
            $this->setError('404 Страница бляд не найдена!');
        }
    }
}
