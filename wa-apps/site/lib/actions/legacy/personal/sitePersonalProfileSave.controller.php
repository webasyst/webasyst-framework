<?php

class sitePersonalProfileSaveController extends waJsonController
{
    public function execute()
    {
        $fields = waRequest::post('personal_fields');

        $domain = waRequest::post('domain');
        $domain_config_path = wa('site')->getConfig()->getConfigPath('domains/'.$domain.'.php');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }
        $domain_config['personal_fields'] = array();
        foreach ($fields as $field) {
            $domain_config['personal_fields'][$field] = true;
        }
        waUtils::varExportToFile($domain_config, $domain_config_path);
    }
}
