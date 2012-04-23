<?php

class webasystBackendDataController extends waController
{
    public function execute()
    {

        $app_id = $this->getRequest()->get('app');
        $path = $this->getRequest()->get('path');

        if ($this->getRequest()->get('temp')) {
            $file = waSystem::getInstance()->getTempPath($path, $app_id);
        } else {
            $file = waSystem::getInstance()->getDataPath($path, false, $app_id);
        }

        waFiles::readFile($file);
    }
}