<?php

class sitePersonalSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $domain = siteHelper::getDomain();
        $config = wa()->getConfig()->getAuth();
        if (!isset($config[$domain])) {
            $config[$domain] = array();
        }

        if (waRequest::post('auth_captcha') !== null) {
            if (waRequest::post('auth_captcha')) {
                $config[$domain]['signup_captcha'] = true;
            } elseif (isset($config[$domain]['signup_captcha'])) {
                unset($config[$domain]['signup_captcha']);
            }
        }

        if (waRequest::post('auth_rememberme')) {
            $config[$domain]['rememberme'] = true;
        } elseif (isset($config[$domain]['rememberme'])) {
            unset($config[$domain]['rememberme']);
        }
        
        // save auth adapters
        if (waRequest::post('auth_adapters') && waRequest::post('adapter_ids')) {
            $config[$domain]['adapters'] = array();
            $adapters = waRequest::post('adapters', array());
            foreach (waRequest::post('adapter_ids') as $adapter_id) {
                $config[$domain]['adapters'][$adapter_id] = $adapters[$adapter_id];
            }
        } else {
            if (isset($config[$domain]['adapters'])) {
                unset($config[$domain]['adapters']);
            }
        }

        // signup
        $fields = waRequest::post('fields');
        $params = waRequest::post('params');
        $must_have_fields = array(
            'email',
            'password',
        );
        $default_fields = array_merge(array(
                'firstname',
                'lastname',
                '',
            ), $must_have_fields);

        $config[$domain]['params'] = $params;

        if (!$config[$domain]) {
            $config[$domain]['fields'] = $default_fields;
        }
        else {
            $config[$domain]['fields'] = array();
        }

        foreach ($fields as $field_id => $field) {
            $config[$domain]['fields'][$field_id] = $field;
        }
        foreach ($must_have_fields as $field) {
            if (!in_array($field, array_keys($fields))) {
                $tmp = waContactFields::get($field);
                $config[$domain]['fields'][$field] = array(
                    'required' => true,
                    'caption' => $tmp->getName(),
                );
            } else {
                $config[$domain]['fields'][$field]['required'] = true;
            }
        }

        // save to file
        if (!$this->getConfig()->setAuth($config)) {
            $this->errors = sprintf(_w('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-config/');
        }
    }
}