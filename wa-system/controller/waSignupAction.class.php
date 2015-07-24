<?php

class waSignupAction extends waViewAction
{
    public function execute()
    {
        $confirm_hash = waRequest::get('confirm', false);
        if (wa()->getAuth()->isAuth() && !$confirm_hash) {
            $this->redirect(wa()->getAppUrl());
        }
        // check auth config
        $auth = wa()->getAuthConfig();
        if (!isset($auth['auth']) || !$auth['auth']) {
            throw new waException(_ws('Page not found'), 404);
        }
        // check auth app and url
        $signup_url = wa()->getRouteUrl((isset($auth['app']) ? $auth['app'] : '').'/signup');
        if (urldecode(wa()->getConfig()->getRequestUrl(false, true)) != $signup_url) {
            $this->redirect($signup_url);
        }
        $errors = array();
        if (waRequest::method() == 'post') {
            // try sign up
            if ($contact = $this->signup(waRequest::post('data'), $errors)) {
                // assign new contact to view
                $this->view->assign('contact', $contact);
            }
        } elseif ($confirm_hash) {
            if ($contact = $this->confirmEmail($confirm_hash, $errors)) { // if we successfully confirmed email
                // assign contact with confirmed email to view
                $this->view->assign('contact', $contact);
                $this->view->assign('confirmed_email', true);
            } else { // else email is already confirmed or smth else happend
                if (wa()->getAuth()->isAuth()) {
                    // redirect to main page
                    $this->redirect(wa()->getAppUrl());
                }
            }
        }
        $this->view->assign('errors', $errors);
        wa()->getResponse()->setTitle(_ws('Sign up'));
    }

    /**
     * @param array $data
     * @param array $errors
     * @return bool|waContact
     */
    public function signup($data, &$errors = array())
    {
        // check exists contacts
        $auth = wa()->getAuth();
        $field_id = $auth->getOption('login');
        if ($field_id == 'login') {
            $field_name = _ws('Login');
        } else {
            $field = waContactFields::get($field_id);
            if ($field) {
                $field_name = $field->getName();
            } else {
                $field_name = ucfirst($field_id);
            }
        }

        $is_error = false;

        // check passwords
        if ($data['password'] !== $data['password_confirm']) {
            $errors['password'] = array();
            $errors['password_confirm'] = array(
                _ws('Passwords do not match')
            );
            $is_error = true;
        } elseif (!$data['password']) {
            $errors['password'] = array();
            $errors['password_confirm'][] = _ws('Password can not be empty.');
            $is_error = true;
        }

        if (!$data[$field_id]) {
            $errors[$field_id] = array(
                sprintf(_ws("%s is required"), $field_name)
            );
            $is_error = true;
        }
        if (!$is_error) {
            $contact = $auth->getByLogin($data[$field_id]);
            if ($contact) {
                $errors[$field_id] = array(
                    sprintf(_ws('User with the same %s is already registered'), $field_name)
                );
                $is_error = true;
            }
        }

        $auth_config = wa()->getAuthConfig();

        // set unknown or unconfirmed status for email
        if (isset($data['email']) && $data['email']) {
            if (!empty($auth_config['params']['confirm_email'])) {
                $email_status = 'unconfirmed';
            } else {
                $email_status = 'unknown';
            }
            $data['email'] = array('value' => $data['email'], 'status' => $email_status);
        }

        // check captcha
        if (isset($auth_config['signup_captcha']) && $auth_config['signup_captcha']) {
            if (!wa()->getCaptcha()->isValid()) {
                $errors['captcha'] = _ws('Invalid captcha');
                $is_error = true;
            }
        }

        if (!empty($auth_config['fields']) && is_array($auth_config['fields'])) {
            foreach ($auth_config['fields'] as $fld_id => $fld) {
                if (array_key_exists('required', $fld) && !$data[$fld_id] && $fld_id !== 'password') {
                    $field = waContactFields::get($fld_id);
                    if (!empty($fld['caption'])) {
                        $field_name = $fld['caption'];
                    } else if ($field) {
                        $field_name = $field->getName();
                    } else {
                        $field_name = ucfirst($fld_id);
                    }
                    $errors[$fld_id] = array(
                        sprintf(_ws("%s is required"), $field_name)
                    );
                    $is_error = true;
                }
            }
        }


        if ($is_error) {
            return false;
        }

        if(isset($data['birthday']) && is_array($data['birthday']['value'])) {
            foreach ($data['birthday']['value'] as $bd_id => $bd_val) {
                if(strlen($bd_val) === 0) {
                    $data['birthday']['value'][$bd_id] = null;
                }
            }
        }

        // remove password_confirm field
        unset($data['password_confirm']);
        // set advanced data
        $data['create_method'] = 'signup';
        $data['create_ip'] = waRequest::getIp();
        $data['create_user_agent'] = waRequest::getUserAgent();
        // try save contact
        $contact = new waContact();
        if (!$errors = $contact->save($data, true)) {
            if (!empty($data['email'])) {
                $this->send($contact);
            }
            /**
             * @event signup
             * @param waContact $contact
             */
            wa()->event('signup', $contact);
            // after sign up callback
            $this->afterSignup($contact);

            // try auth new contact
            try {
                if (empty($data['email']) || empty($auth_config['params']['confirm_email'])) {
                    if (wa()->getAuth()->auth($contact)) {
                        $this->logAction('signup', wa()->getEnv());
                    }
                }
            } catch (waException $e) {
                $errors = array('auth' => $e->getMessage());
            }

            return $contact;
        }
        if (isset($errors['name'])) {
            $errors['firstname'] = array();
            $errors['middlename'] = array();
            $errors['lastname'] = $errors['name'];
        }
        return false;
    }

    protected function getFrom()
    {
        return null;
    }

    public function send(waContact $contact)
    {
        $email = $contact->get('email', 'default');
        if (!$email) {
            return;
        }
        $subject = _ws("Thank you for signing up!");
        $this->view->assign('email', $email);
        $this->view->assign('name', $contact->getName());

        // send email confirmation link
        $this->sendConfirmationLink($contact);

        $template_file = $this->getConfig()->getConfigPath('mail/Signup.html', true, 'webasyst');
        if (file_exists($template_file)) {
            $body = $this->view->fetch('string:'.file_get_contents($template_file));
        } else {
            $body = $this->view->fetch(wa()->getAppPath('templates/mail/Signup.html', 'webasyst'));
        }
        try {
            $m = new waMailMessage($subject, $body);
            $m->setTo($email, $contact->getName());
            $from = $this->getFrom();
            if ($from) {
                $m->setFrom($from);
            }
            return (bool)$m->send();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param waContact $contact
     */
    protected function afterSignup(waContact $contact)
    {

    }

    private function sendConfirmationLink(waContact $contact)
    {
        $config = wa()->getAuthConfig();
        if (!empty($config['params']['confirm_email'])) {
            $confirmation_hash = md5(time().'rfb2:zfbdbawrsddswr4$h5t3/.`w'.mt_rand().mt_rand().mt_rand());
            $contact->setSettings(wa()->getApp(), "email_confirmation_hash", $confirmation_hash);
            $ce = new waContactEmailsModel();
            $unconfirmed_email = $ce->getByField(array(
                'contact_id' => $contact->getId(),
                'email' => $contact->get('email', 'default'),
                'status' => 'unconfirmed'
            ));
            $hash = substr($confirmation_hash, 0, 16).$unconfirmed_email['id'].substr($confirmation_hash, -16);
            $this->view->assign('email_confirmation_hash', $hash);
            return true;
        }
        return false;
    }

    /**
     * @param $confirmation_hash
     * @param array $errors
     * @return bool|waContact
     */
    protected function confirmEmail($confirmation_hash, &$errors = array())
    {
        $email_id = substr(substr($confirmation_hash, 16), 0, -16);
        $confirmation_hash = substr($confirmation_hash, 0, 16).substr($confirmation_hash, -16);

        $ce = new waContactEmailsModel();
        $contact_email = $ce->getById($email_id);
        $contact = new waContact($contact_email['contact_id']);

        $user_confirm_hash = $contact->getSettings(wa()->getApp(), "email_confirmation_hash", false);

        if ($user_confirm_hash && $confirmation_hash === $user_confirm_hash) {

            // try auth new contact
            try {
                if (wa()->getAuth()->auth($contact)) {
                    $ce->updateById($email_id, array('status' => 'confirmed'));
                    $contact->delSettings(wa()->getApp(), "email_confirmation_hash");
                }
            } catch (waException $e) {
                $errors = array('auth' => $e->getMessage());
            }

            return $contact;
        }
        return false;
    }
}