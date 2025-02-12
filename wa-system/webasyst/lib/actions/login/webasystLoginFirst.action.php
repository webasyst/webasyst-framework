<?php

class webasystLoginFirstAction extends waViewAction
{
    public function execute()
    {
        $contact_model = new waContactModel();
        if (!$contact_model->isEmpty()) {
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
        if (waRequest::getMethod() == waRequest::METHOD_GET && waRequest::get('waid_auth') == '1') {
            $inst = new webasystInstaller();
            $waid_connect_result = $inst->connectToWaid('login-first');
            if (empty($waid_connect_result['status'])) {
                $this->view->assign('webasyst_id_auth_result', [
                    'details' => ifset($waid_connect_result['details'], [
                        'error_message' => _ws('Webasyst ID auth error')
                    ])
                ]);
                return;
            }

            $auth_url = $this->getWebasystIDAuthUrl();
            if ($auth_url) {
                $this->redirect($auth_url);
            } else {
                $this->view->assign('webasyst_id_auth_result', [
                    'details' => [
                        'error_message' => _ws('Webasyst ID auth error')
                    ]
                ]);
                return;
            }
        }
        if (waRequest::getMethod() == waRequest::METHOD_GET && waRequest::get('waid_auth') == '2') {

            $waid_contact_info = $this->getWebasystContactInfo();
            if (empty($waid_contact_info) || (empty($waid_contact_info['email']) && empty($waid_contact_info['phone']))) {
                $this->view->assign('webasyst_id_auth_result', [
                    'details' => [
                        'error_message' => _ws('Unable to sign in with Webasyst ID.')
                    ]
                ]);
                return;
            }

            // create user
            $user = new waUser();
            $user['name'] = $waid_contact_info['name'];
            $user['firstname'] = $waid_contact_info['firstname'];
            $user['lastname']  = $waid_contact_info['lastname'];
            $user['middlename'] = $waid_contact_info['middlename'];
            $user['locale'] = $waid_contact_info['locale'];
            $login = null;
            if (!empty($waid_contact_info['email'])) {
                $user->set('email', $waid_contact_info['email']);
                $email = $user->get('email', 'default');
                $login = substr($email, 0, strpos($email, '@'));
            }
            if (!empty($waid_contact_info['phone'])) {
                $user->set('phone', $waid_contact_info['phone']);
                if (empty($login)) {
                    $login = $user->get('phone', 'default');
                }
            }

            if (!empty($user['locale'])) {
                wa()->setLocale($user['locale']);
            }

            if ($errors = $this->createFirstUser($user, $login)) {
                $result = array();
                foreach ($errors as $k => $v) {
                    $result['all'][] = $k.": ".(is_array($v) ? implode(', ', $v) : $v);
                }
                $result['all'] = implode("\r\n", $result['all']);
                $this->view->assign('errors', $result);
                return;
            }

            $this->bindWithWebasystContact();

            if (!empty($waid_contact_info['userpic_uploaded']) && !empty($waid_contact_info['userpic_original_crop'])) {
                try {
                    $this->saveUserpic($waid_contact_info['userpic_original_crop']);
                } catch (Exception $exception) {
                    // Do nothing
                }
            }

            // redirect to backend
            $this->redirect($this->getConfig()->getBackendUrl(true));
        }
        if (waRequest::getMethod() == waRequest::METHOD_POST) {
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
                $errors['email'] = _ws('Enter an email address');
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
                return;
            }

            // create user
            $user = new waUser();
            $firstname = waRequest::post('firstname');
            $user['firstname'] = $firstname ? $firstname : $login;
            $user['lastname'] = waRequest::post('lastname');
            $user['email'] = $email;

            if ($errors = $this->createFirstUser($user, $login, $password, waRequest::post('account_name'))) {
                $result = array();
                foreach ($errors as $k => $v) {
                    $result['all'][] = $k.": ".(is_array($v) ? implode(', ', $v) : $v);
                }
                $result['all'] = implode("\r\n", $result['all']);
                $this->view->assign('errors', $result);
                return;
            }

            // redirect to backend
            $this->redirect($this->getConfig()->getBackendUrl(true));
        }

    }

    protected function createFirstUser(waUser $user, $login, $password = null, $account_name = null)
    {
        if (empty($account_name)) {
            $account_name = _ws('My company');
        }
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->set('webasyst', 'name', $account_name);
        $email = $user->get('email', 'default');
        if ($email) {
            $app_settings_model->set('webasyst', 'email', $email);
            $app_settings_model->set('webasyst', 'sender', $email);
        }

        if (empty($password)) {
            $password = waUtils::getRandomHexString(16);
        }

        $user['locale'] = empty($user['locale']) ? wa()->getLocale() : $user['locale'];
        $user['is_user'] = 1;
        $user['login'] = $login;
        $user['password'] = $password;
        $user['create_method'] = 'install';
        if ($errors = $user->save()) {
            $result = array();
            foreach ($errors as $k => $v) {
                $result['all'][] = $k.": ".(is_array($v) ? implode(', ', $v) : $v);
            }
            $result['all'] = implode("\r\n", $result['all']);
            return $result;
        }

        $user->setRight('webasyst', 'backend', 1);
        waSystem::getInstance()->getAuth()->auth(array(
            'login' => $login,
            'password' => $password
        ));

        $this->createRouting();
        $this->setWebasystEmailTransport();
    }

    protected function createRouting()
    {
        $path = $this->getConfig()->getPath('config');
        if (file_exists($path.'/routing.php')) {
            return;
        }

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
            $data[$domain][] = [
                'url' => '*',
                'app' => 'site',
                '_name' => _ws('Home page')
            ];
        }
        waUtils::varExportToFile($data, $path.'/routing.php');
    }

    protected function setWebasystEmailTransport()
    {
        $path = $this->getConfig()->getPath('config');
        if (file_exists($path.'/mail.php')) {
            return;
        }

        $auth = new waWebasystIDWAAuth();
        if (!$auth->isClientConnected()) {
            return;
        }

        waUtils::varExportToFile([ 'default' => [ 'type' => 'wasender' ] ], $path.'/mail.php');
    }

    protected function getWebasystIDAuthUrl()
    {
        $auth = new waWebasystIDWAAuth();
        if (!$auth->isClientConnected()) {
            return null;
        }

        $webasyst_id_auth_url = $auth->getUrl() . '&backend_auth=1';

        $current_url = $this->getCurrentUrl();
        $current_url = waUtils::urlSafeBase64Encode($current_url);
        $webasyst_id_auth_url .= '&referrer_url=' . $current_url;
        return $webasyst_id_auth_url;
    }

    private function getCurrentUrl()
    {
        $url = wa()->getConfig()->getRequestUrl(true, true);
        $url = ltrim($url, '/') . '?waid_auth=2';
        $domain = wa()->getConfig()->getDomain();

        if (waRequest::isHttps()) {
            return "https://{$domain}/{$url}";
        } else {
            return "http://{$domain}/{$url}";
        }
    }

    protected function getWebasystContactInfo()
    {
        $data = $this->getStorage()->get('webasyst_id_server_data');
        if (!$data || !is_array($data)) {
            return null;
        }
        $api = new waWebasystIDApi();
        return $api->loadProfileInfo($data);
    }

    protected function bindWithWebasystContact()
    {
        $data = $this->getStorage()->get('webasyst_id_server_data');
        webasystLoginAction::clearWebasystIDAuthProcessState();

        if (!empty($data) && is_array($data) && !empty($data['access_token'])) {
            // Extract Webasyst contact
            $m = new waWebasystIDAccessTokenManager();
            $token_info = $m->extractTokenInfo($data['access_token']);
            $contact_id = $token_info['contact_id'];

            // Bind user with Webasyst ID
            $user = wa()->getUser();
            $user->bindWithWaid($contact_id, $data);

            // Force backend auth with Webasyst ID
            (new waWebasystIDClientManager)->setBackendAuthForced(true);
        }
    }

    protected function saveUserpic($photo_url)
    {
        $user = wa()->getUser();
        // Load person photo and save to contact
        $photo = null;
        if (function_exists('curl_init')) {
            $ch = curl_init($photo_url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9);
            $photo = curl_exec($ch);
            curl_close($ch);
        } else {
            $scheme = parse_url($photo_url, PHP_URL_SCHEME);
            if (ini_get('allow_url_fopen') && in_array($scheme, stream_get_wrappers())) {
                $photo = @file_get_contents($photo_url);
            }
        }
        if ($photo) {
            $photo_url_parts = explode('/', $photo_url);
            $path = wa()->getTempPath('auth_photo/'.$user->getId().'.'.md5(end($photo_url_parts)), 'webasyst');
            file_put_contents($path, $photo);
            $user->setPhoto($path);
        }
    }
}
