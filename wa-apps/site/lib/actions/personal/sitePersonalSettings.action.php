<?php

class sitePersonalSettingsAction extends waViewAction
{
    public function execute()
    {
        $apps = wa()->getApps();
        $auth_apps = array();

        $domain = siteHelper::getDomain();
        $routes = wa()->getRouting()->getRoutes($domain);

        $auth_app_id = false;
        foreach ($routes as $route) {
            if (isset($route['app']) && isset($apps[$route['app']])) {
                $auth_apps[$route['app']] = true;
                $auth_app_id = $route['app'];
            }
        }

        $temp = array();
        foreach ($apps as $app_id => $app) {
            if (isset($app['frontend']) || isset($auth_apps[$app_id])) {
                $temp[$app_id] = array(
                    'id' => $app_id,
                    'icon' => $app['icon'],
                    'name' => $app['name']
                );
                if (isset($auth_apps[$app_id])) {
                    if (empty($app['auth'])) {
                        unset($auth_apps[$app_id]);
                    } else {
                        $auth_apps[$app_id] = $temp[$app_id];
                    }
                }
            }
        }

        foreach ($auth_apps as $app_id => &$a) {
            $a['login_url'] = wa()->getRouteUrl($app_id.'/login', array('domain' => $domain), true);
        }
        unset($a);

        $this->view->assign('auth_apps', $auth_apps);

        $auth_config = wa()->getAuthConfig(siteHelper::getDomain());
        $this->view->assign('auth_config', array(
            'auth' => isset($auth_config['auth']) ? $auth_config['auth'] : false ,
            'app' => isset($auth_config['app']) ? $auth_config['app'] : $auth_app_id,
            'signup_captcha' => isset($auth_config['signup_captcha']) ? $auth_config['signup_captcha'] : false,
            'rememberme' => isset($auth_config['rememberme']) ? $auth_config['rememberme'] : false,
            'adapters' => isset($auth_config['adapters']) ? $auth_config['adapters'] : array()
        ));

        $this->view->assign('auth_adapters', $this->getAuthAdapters());

        $this->view->assign('apps', $temp);
        $this->view->assign('domain_id', siteHelper::getDomainId());

        $domain = siteHelper::getDomain();
        $personal_sidebar = wa('site')->event('backend_personal');
        foreach ($personal_sidebar as &$items) {
            foreach ($items as &$item) {
                $item['url'] .= '&domain='. urlencode($domain);
            }
        }
        $this->view->assign('domain', siteHelper::getDomain());
        $this->view->assign('domain_id', siteHelper::getDomainId());
        $this->view->assign('personal_sidebar', $personal_sidebar);

        // signup
        $fields = $enable_fields = array();
        $must_have_fields = array(
            'email',
            'password',
        );
        $default_fields = array_merge(array(
                'firstname',
                'lastname',
                '',
            ), $must_have_fields);
        $unset_fields = array(
            'name'
        );

        // include auth.php
        $domain_config_path = wa('site')->getConfig()->getPath('config', 'auth');
        if (file_exists($domain_config_path)) {
            $domain_config = include($domain_config_path);
        } else {
            $domain_config = array();
        }

        // fields for this form (or default fields)
        $config_fields = isset($domain_config[$domain]['fields']) ? $domain_config[$domain]['fields'] : $default_fields;
        $separators = 0;
        foreach ($config_fields as $field_name => $field) {
            $fld_name = is_array($field) ? $field_name : $field;
            $fld_name = strlen($fld_name) ? $fld_name : $separators++;
            if (!$fld_name) { // todo: don't skip separator
                continue;
            }
            $enable_fields[$fld_name] = $field;
        }

        $available_fields = waContactFields::getAll('person', true) + array(
                'password' => new waContactPasswordField('password', 'Password'),
            );

        foreach ($available_fields as $field_name => $field) {
            $name = $field->getName();
            if ($name && !in_array($field_name, $unset_fields)) {
                $checked = array_key_exists($field_name, $enable_fields);
                $available_fields[$field_name] = array(
                    'id' => $field_name,
                    'name' => $name,
                    'checked' => $checked,
                    'disabled' => false,
                );
                // only for 'must have' fields
                if (in_array($field_name, $must_have_fields)) {
                    $available_fields[$field_name]['disabled'] = true;
                    $available_fields[$field_name]['checked'] = true;
                    // if we don't have 'must have' fields - let's add'em
                    if (!array_key_exists($field_name, $enable_fields)) {
                        $enable_fields[$field_name] = $available_fields[$field_name];
                    }
                }
            } else {
                unset($available_fields[$field_name]);
            }
        }
        $enable_fields = array_merge_recursive($enable_fields, $available_fields);

        $this->view->assign('domain', $domain);
        $this->view->assign('enable_fields', $enable_fields);
        $this->view->assign('available_fields', $available_fields);
        $this->view->assign('fields', $fields);
        $this->view->assign('params', isset($domain_config[$domain]['params']) ? $domain_config[$domain]['params'] : array());
    }

    protected function getAuthAdapters()
    {
        $path = $this->getConfig()->getPath('system').'/auth/adapters/';
        $dh = opendir($path);
        $result = array();
        while (($f = readdir($dh)) !== false) {
            if ($f === '.' || $f === '..' || is_dir($path.$f)) {
                continue;
            } elseif (substr($f, -14) == 'Auth.class.php') {
                require_once($path.$f);
                $id = substr($f, 0, -14);
                $class_name = $id."Auth";
                $result[$id] = new $class_name(array('app_id' => '', 'app_secret' => ''));
            }
        }
        closedir($dh);
        return $result;
    }
}