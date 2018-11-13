<?php

class sitePersonalSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $domain = siteHelper::getDomain();
        // Save auth config
        $config = waDomainAuthConfig::factory($domain);
        $config->setData($this->getData());
        if (!$config->commit()) {
            $this->errors = sprintf(_w('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-config/');
        }
    }

    protected function getData()
    {
        $data = $this->getRequest()->post();
        $data = is_array($data) ? $data : array();
        $data['used_auth_methods'] = (!empty($data['used_auth_methods'])) ? array_keys($data['used_auth_methods']) : array();
        $data['adapters'] = $this->getAuthAdapters();
        $data['app'] = ifempty($data['app_id']);
        return $data;
    }

    protected function getAuthAdapters()
    {
        $used_auth_methods = $this->getRequest()->post('used_auth_methods');
        $adapters = array();
        $post_adapter_ids = $this->getRequest()->post('adapter_ids');
        $post_adapter_ids = is_array($post_adapter_ids) ? $post_adapter_ids : array();
        if ($post_adapter_ids && !empty($used_auth_methods['social'])) {
            $post_adapters = $this->getRequest()->post('adapters');
            $post_adapters = is_array($post_adapters) ? $post_adapters : array();
            foreach ($post_adapter_ids as $adapter_id) {
                $adapters[$adapter_id] = $post_adapters[$adapter_id];
            }
        }
        return $adapters;
    }
}
