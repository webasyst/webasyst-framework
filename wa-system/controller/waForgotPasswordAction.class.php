<?php

class waForgotPasswordAction extends waViewAction
{
    public function execute()
    {
        $hash = waRequest::get('key');
        if ($hash) {
            $this->setPassword($hash);
        } else {
            $this->forgotPassword();
        }
    }

    protected function setPassword($hash)
    {
        if ($contact = $this->checkHash($hash)) {
            $auth = wa()->getAuth();
            // set contact locale
            if ($contact['locale']) {
                wa()->setLocale($contact['locale']);
                waLocale::loadByDomain('webasyst', wa()->getLocale());
            }
            $error = '';
            if (waRequest::method() == 'post') {
                $password = waRequest::post('password');
                $password_confirm = waRequest::post('password_confirm');
                if (!$password) {
                    $error = _ws('Password can not be empty.');
                } else if ($password !== $password_confirm) {
                    $error = _ws('Passwords do not match');
                } else {
                    // save new password
                    $contact['password'] = $password;
                    $contact->save();
                    // remove hash
                    $this->deleteHash($contact['id']);
                    // auth
                    $auth->auth(array('id' => $contact['id']));
                    // redirect
                    $this->redirect(wa()->getAppUrl());
                }
            }
            if ($auth->getOption('login') == 'login') {
                $login = $contact['login'];
            } elseif ($auth->getOption('login') == 'email') {
                $login = $contact->get('email', 'default');
            }
            $this->view->assign('login', $login);
            $this->view->assign('error', $error);
            $this->view->assign('set_password', true);
            wa()->getResponse()->setTitle(_ws('Password recovery'));
        } else {
            $this->redirect(wa()->getRouteUrl('/forgotpassword'));
        }
    }

    /**
     * @param string $login
     * @param waAuth $auth
     * @return waContact|bool
     */
    protected function findContact($login, $auth)
    {
        $contact_model = new waContactModel();
        $is_user = $auth->getOption('is_user');
        if (strpos($login, '@')) {
            $sql = "SELECT c.* FROM wa_contact c
            JOIN wa_contact_emails e ON c.id = e.contact_id
            WHERE ".($is_user ? "c.is_user = 1 AND " : "")."e.email LIKE s:email AND e.sort = 0
            ORDER BY c.id LIMIT 1";
            $contact_info = $contact_model->query($sql, array('email' => $login))->fetch();
        } else {
            $contact_info = $contact_model->getByField('login', $login);
        }
        if ($contact_info && (!$is_user || $contact_info['is_user'])) {
            $contact = new waContact($contact_info['id']);
            $contact->setCache($contact_info);
            return $contact;
        }
        return false;
    }


    protected function getResetPasswordUrl($hash)
    {
        if (wa()->getEnv() == 'backend') {
            return wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl(false).'/?forgotpassword&key='.$hash;
        } else {
            return wa()->getRouteUrl('/forgotpassword', array(), true).'?key='.$hash;
        }
    }

    protected function forgotPassword()
    {
        $error = '';
        $auth = wa()->getAuth();
        if (waRequest::method() == 'post' && !waRequest::post('ignore')) {
            if ($contact = $this->findContact(waRequest::post('login', '', waRequest::TYPE_STRING), $auth)) {
                if ($contact->get('is_banned')) {
                    $error = _ws('Password recovery for this email has been banned.');
                } elseif ($email = $contact->get('email', 'default')) {
                    if ($contact['locale']) {
                        wa()->setLocale($contact['locale']);
                        waLocale::loadByDomain('webasyst', wa()->getLocale());
                    }
                    $hash = $this->getHash($contact['id'], true);
                    if ($this->send($email, $this->getResetPasswordUrl($hash))) {
                        $this->view->assign('sent', 1);
                    } else {
                        $error = _ws('Sorry, we can not recover password for this login name or email. Please refer to your system administrator.');
                    }
                }
            } else {
                if ($auth->getOption('login') == 'email') {
                    $error = _ws('No user with this email has been found.');
                } else {
                    $error = _ws('No user with this login name or email has been found.');
                }
            }
        }
        $this->view->assign('options', $auth->getOptions());
        $this->view->assign('error', $error);
        if ($this->layout) {
            $this->layout->assign('error', $error);
        }
        wa()->getResponse()->setTitle(_ws('Password recovery'));
    }

    /**
     * @param string $to - email
     * @param string $url - url to reset password
     * @return bool
     */
    protected function send($to, $url)
    {
        $this->view->assign('url', $url);
        $subject = _ws("Password recovery");
        $template_file = $this->getConfig()->getConfigPath('mail/RecoveringPassword.html', true, 'webasyst');
        if (file_exists($template_file)) {
            $body = $this->view->fetch('string:'.file_get_contents($template_file));
        } else {
            $body = $this->view->fetch(wa()->getAppPath('templates/mail/RecoveringPassword.html', 'webasyst'));
        }
        $this->view->clearAllAssign();
        try {
            $m = new waMailMessage($subject, $body);
            $m->setTo($to);
            return (bool)$m->send();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $hash
     * @return bool|waContact
     */
    protected function checkHash($hash)
    {
        $contact_id = substr($hash, 16, -16);
        $contact_model = new waContactModel();
        $contact = $contact_model->getById($contact_id);
        if ($contact && $hash === $this->getHash($contact_id)) {
            return new waContact($contact_id);
        }
        return false;
    }

    protected function getHash($contact_id, $set_new = false)
    {
        $contact_settings_model = new waContactSettingsModel();
        if ($set_new) {
            $hash = md5(uniqid(null, true));
            $contact_settings_model->set($contact_id, 'webasyst', 'forgot_password_hash', $hash);
            return substr($hash, 0, 16).$contact_id.substr($hash, -16);
        } else {
            $hash = $contact_settings_model->getOne($contact_id, 'webasyst', 'forgot_password_hash');
            return substr($hash, 0, 16).$contact_id.substr($hash, -16);
        }
    }

    protected function deleteHash($contact_id)
    {
        $contact_settings_model = new waContactSettingsModel();
        $contact_settings_model->delete($contact_id, 'webasyst', 'forgot_password_hash');
    }
}