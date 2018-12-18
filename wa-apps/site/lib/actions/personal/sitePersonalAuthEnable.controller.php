<?php

class sitePersonalAuthEnableController extends waJsonController
{
    public function execute()
    {
        $enabled = $this->getRequest()->post('enabled');

        $domain = siteHelper::getDomain();
        $auth_config = waDomainAuthConfig::factory($domain);
        $auth_config->setAuth($enabled);

        if (!$auth_config->commit()) {
            $this->errors = sprintf(_w('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-config/');
        }

    }
}
