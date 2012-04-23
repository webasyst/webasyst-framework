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
                }
                // create user
                $user = new waUser();
                $user['firstname'] = $login;
                $user['is_user'] = 1;
                $user['login'] = $login;
                $user['password'] = $password;
                $user['email'] = $email;
                $user['create_method'] = 'install';
                if ($errors = $user->save()) {
                    // log errors
                    waLog::log(implode("\r\n", $errors));
                    // display errors
                    $errors = array('all' => implode('<br />', $errors));
                    $this->view->assign('errors', $errors);
                } else {
                    $user->setRight('webasyst', 'backend', 1);
                    waSystem::getInstance()->getAuth()->auth(array(
                        'login' => $login,
                        'password' => $password
                    ));
                    $this->redirect($this->getConfig()->getBackendUrl(true));
                }

            }
        }

    }
}