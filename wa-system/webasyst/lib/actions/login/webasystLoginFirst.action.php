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
            $login = is_scalar($login) ? (string)$login : '';

            if (strlen($login) <= 0) {
                $errors['login'] = _ws('Login is required');
            } else {
                $validator = new waLoginValidator();
                if (!$validator->isValid($login)) {
                    $login_errors = $validator->getErrors();
                    if ($login_errors) {
                        $errors['login'] = implode("<br />", $login_errors);
                    }
                }
            }
            $password = waRequest::post('password');
            $password = is_scalar($password) ? (string)$password : '';
            $password_confirm = waRequest::post('password_confirm');
            $password_confirm = is_scalar($password_confirm) ? (string)$password_confirm : '';

            if (strlen($password) <= 0) {
                $errors['password'] = _ws("Password required");
            } elseif ($password !== $password_confirm) {
                $errors['password'] = _ws('Passwords do not match');
            } elseif (strlen($password) > waAuth::PASSWORD_MAX_LENGTH) {
                $errors['password'] = _ws('Specified password is too long.');
            }

            $email = waRequest::post('email');
            $email = is_scalar($email) ? (string)$email : '';
            if (strlen($email) <= 0) {
                $errors['email'] = _ws('Email is required');
            } else {
                $validator = new waEmailValidator();
                if (!$validator->isValid($email)) {
                    $email_errors = $validator->getErrors();
                    if ($email_errors) {
                        $errors['email'] = implode("<br />", $email_errors);
                    }
                }
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
                                    wa($app_id);
                                    foreach ($app['routing_params'] as $routing_param => $routing_param_value) {
                                        if (is_callable($routing_param_value)) {
                                            $app['routing_params'][$routing_param] = call_user_func($routing_param_value);
                                        }
                                    }
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
