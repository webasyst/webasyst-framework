<?php

class sitePersonalAuthEnableController extends waJsonController
{
    public function execute()
    {
        $enabled = waRequest::post('enabled');
        $app_id = waRequest::post('app_id');

        $domain = siteHelper::getDomain();

        $config = wa()->getConfig()->getAuth();
        if (!isset($config[$domain])) {
            if (!$enabled) {
                return;
            }
            $config[$domain] = array();
        }

        if ($enabled && $app_id) {
            $config[$domain]['auth'] = true;
            $config[$domain]['app'] = $app_id;
        } else {
            if (isset($config[$domain]['auth'])) {
                unset($config[$domain]['auth']);
            }
            if (isset($config[$domain]['app'])) {
                unset($config[$domain]['app']);
            }
        }

        if (!$this->getConfig()->setAuth($config)) {
            $this->errors = sprintf(_w('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-config/');
        }
    }
}