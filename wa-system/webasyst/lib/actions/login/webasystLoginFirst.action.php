<?php

class webasystLoginFirstAction extends waViewAction
{
    public function execute()
    {
        $contact_model = new waContactModel();
        if ($contact_model->countAll()) {
            $this->redirect($this->getConfig()->getBackendUrl(true));
        }
        if (($locale = waRequest::get('lang')) && waLocale::getInfo($locale)) {
            // set locale
            wa()->setLocale($locale);
            // save to database default locale
            $app_settings_model = new waAppSettingsModel();
            $app_settings_model->set('webasyst', 'locale', $locale);
        }
        if (file_exists($this->getConfig()->getRootPath().'/install.php')) {
            @unlink($this->getConfig()->getRootPath().'/install.php');
        }
        if (waRequest::getMethod() == 'post') {
            $errors = array();
            $login = waRequest::post('login');
            $validator = new waLoginValidator();
            if (!$validator->isValid($login)) {
                $errors['login'] = implode("<br />", $validator->getErrors());
            }
            $password = waRequest::post('password');
            $password_confirm = waRequest::post('password_confirm');

            if ($password !== $password_confirm) {
                $errors['password'] = _w('Passwords do not match');
            }

            $email = waRequest::post('email');
            $validator = new waEmailValidator();
            if (!$validator->isValid($email)) {
                $errors['email'] = implode("<br />", $validator->getErrors());
            }

            if ($errors) {
                $this->view->assign('errors', $errors);
            } else {
                // save account name
                $app_settings_model = new waAppSettingsModel();
                $app_settings_model->set('webasyst', 'name', waRequest::post('account_name'));
                if ($email) {
                    $app_settings_model->set('webasyst', 'email', $email);
                    $app_settings_model->set('webasyst', 'sender', $email);
                }
                // create user
                $user = new waUser();
                $firstname = waRequest::post('firstname');
                $user['firstname'] = $firstname ? $firstname : $login;
                $user['lastname'] = waRequest::post('lastname');
                $user['is_user'] = 1;
                $user['login'] = $login;
                $user['password'] = $password;
                $user['email'] = $email;
                $user['locale'] = wa()->getLocale();
                $user['create_method'] = 'install';
                if ($errors = $user->save()) {
                    $result = array();
                    foreach ($errors as $k => $v) {
                        $result['all'][] = $k.": ".(is_array($v) ? implode(', ', $v) : $v);
                    }
                    $result['all'] = implode("\r\n", $result['all']);
                    $this->view->assign('errors', $result);
                } else {
                    $user->setRight('webasyst', 'backend', 1);
                    waSystem::getInstance()->getAuth()->auth(array(
                        'login' => $login,
                        'password' => $password
                    ));

                    $path = $this->getConfig()->getPath('config');
                    // check routing.php
                    if (!file_exists($path.'/routing.php')) {
                        $apps = wa()->getApps();
                        $data = array();
                        $domain = $this->getConfig()->getDomain();
                        $site = false;
                        foreach ($apps as $app_id => $app) {
                            if ($app_id == 'site') {
                                $site = true;
                            } elseif (!empty($app['frontend'])) {
                                $routing = array(
                                    'url' => $app_id.'/*',
                                    'app' => $app_id,
                                );

                                if (!empty($app['routing_params']) && is_array($app['routing_params'])) {
                                    $routing = array_merge($routing, $app['routing_params']);
                                }
                                $data[$domain][] = $routing;
                            }
                        }
                        if ($site) {
                            $data[$domain][] = array('url' => '*', 'app' => 'site');
                        }
                        waUtils::varExportToFile($data, $path.'/routing.php');
                    }
                    // redirect to backend
                    $this->redirect($this->getConfig()->getBackendUrl(true));
                }

            }
        }

    }
}