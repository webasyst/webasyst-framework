<?php

class sitePersonalSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $data = $this->getData();

        if ($errors = $this->validate($data)) {
            $this->errors = $errors;
            return;
        }

        $domain = siteHelper::getDomain();
        // Save auth config
        $config = waDomainAuthConfig::factory($domain);
        $config->setData($data);
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

    protected function validate($data)
    {
        $errors = array();

        $data = is_array($data) ? $data : array();
        $phone_transform_prefix = isset($data['phone_transform_prefix']) && is_array($data['phone_transform_prefix']) ? $data['phone_transform_prefix'] : array();

        $input_code = isset($phone_transform_prefix['input_code']) && is_scalar($phone_transform_prefix['input_code']) ? (string)$phone_transform_prefix['input_code'] : '';
        $output_code = isset($phone_transform_prefix['output_code']) && is_scalar($phone_transform_prefix['output_code']) ? (string)$phone_transform_prefix['output_code'] : '';

        $input_code_filled = strlen($input_code) > 0;
        $output_code_filled = strlen($output_code) > 0;

        $filled_only_one_code = $input_code_filled && !$output_code_filled || !$input_code_filled && $output_code_filled;
        if ($filled_only_one_code) {
            if (!$input_code_filled) {
                $errors["phone_transform_prefix[input_code]"] = _ws('Required');
            }
            if (!$output_code_filled) {
                $errors["phone_transform_prefix[output_code]"] = _ws('Required');
            }
        }

        if ($input_code_filled && !wa_is_int($input_code)) {
            $errors["phone_transform_prefix[input_code]"] = _w('Enter digits only');
        }
        if ($output_code_filled && !wa_is_int($output_code)) {
            $errors["phone_transform_prefix[output_code]"] = _w('Enter digits only');
        }

        return $errors;
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
                $adapter_params = $post_adapters[$adapter_id];
                $adapter_params = $this->prepareAdapterParams($adapter_params);
                $adapters[$adapter_id] = $adapter_params;
            }
        }
        return $adapters;
    }

    protected function prepareAdapterParams(array $params)
    {
        foreach ($params as $key => $value) {
            $params[$key] = trim($value);
        }
        return $params;
    }
}
