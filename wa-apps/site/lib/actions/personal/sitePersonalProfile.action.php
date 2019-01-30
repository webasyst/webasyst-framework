<?php

class sitePersonalProfileAction extends waViewAction
{
    public function execute()
    {
        $domain = siteHelper::getDomain();

        $fields = array();
        $default_fields = array(
            'firstname'  => true,
            'lastname'   => true,
            'middlename' => true,
            'email'      => true,
            'phone'      => true,
            'password'   => true,
        );

        $auth_config = wa()->getAuthConfig($domain);
        if (!empty($auth_config['app']) && $auth_config['app'] == 'shop') {
            $settings = wa('shop')->getConfig()->getCheckoutSettings();
            if (!isset($settings['contactinfo'])) {
                $settings = wa('shop')->getConfig()->getCheckoutSettings(true);
            }
            if (!empty($settings['contactinfo']['fields'])) {
                $default_fields = array();
                foreach ($settings['contactinfo']['fields'] as $field_id => $f) {
                    $default_fields[$field_id] = true;
                }
            }
        }

        $domain_config_path = wa('site')->getConfig()->getConfigPath('domains/'.$domain.'.php');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }

        if (!isset($domain_config['personal_fields'])) {
            $domain_config['personal_fields'] = $default_fields;
        }

        if (!empty($auth_config['app']) && isset($domain_config['personal'][$auth_config['app']]) &&
            !$domain_config['personal'][$auth_config['app']]) {
            $this->view->assign('profile_disabled', true);
            $this->view->assign('auth_app', wa()->getAppInfo($auth_config['app']));
        }

        $contacts_fields = array(
                'photo' => new waContactHiddenField('photo', _ws('Photo')),
            ) + waContactFields::getAll('person', true) + array(
                'password' => new waContactPasswordField('password', _ws('Password')),
            );
        foreach ($contacts_fields as $fiels_name => $field) {
            $name = $field->getName();
            if ($name && $fiels_name !== 'name') {
                $fields[] = array(
                    'id' => $fiels_name,
                    'name' => $name,
                    'checked' => (
                        isset($domain_config['personal_fields'][$fiels_name]) &&
                        $domain_config['personal_fields'][$fiels_name] === true
                    ) ? true : false,
                );
            }
        }

        $this->view->assign('domain', $domain);
        $this->view->assign('fields', $fields);
    }
}