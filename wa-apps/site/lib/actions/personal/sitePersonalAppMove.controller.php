<?php

class sitePersonalAppMoveController extends waJsonController
{
    public function execute()
    {
        $app_id = waRequest::post('app_id');
        $enable = waRequest::post('enable');

        $domain = siteHelper::getDomain();
        $domain_config_path = $this->getConfig()->getConfigPath('domains/'.$domain.'.php');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }

        $domain_config['personal'] = array();
        foreach (waRequest::post('apps') as $app_id) {
            $domain_config['personal'][$app_id] = true;
        }
        waUtils::varExportToFile($domain_config, $domain_config_path);
    }
}