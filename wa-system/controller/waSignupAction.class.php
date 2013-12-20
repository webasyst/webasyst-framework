<?php

class waSignupAction extends waViewAction
{
    public function execute()
    {
        if (wa()->getAuth()->isAuth()) {
            $this->redirect(wa()->getAppUrl());
        }
        // check auth config
        $auth = wa()->getAuthConfig();
        if (!isset($auth['auth']) || !$auth['auth']) {
            throw new waException(_ws('Page not found'), 404);
        }
        // check auth app and url
        $signup_url = wa()->getRouteUrl((isset($auth['app']) ? $auth['app'] : '').'/signup');
        if (wa()->getConfig()->getRequestUrl(false) != $signup_url) {
            $this->redirect($signup_url);
        }

        $errors = array();
        if (waRequest::method() == 'post') {
            // try sign up
            if ($contact = $this->signup(waRequest::post('data'), $errors)) {
                // assign new contact to view
                $this->view->assign('contact', $contact);
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

        // set unconfirmed status for email
        if (isset($data['email']) && $data['email']) {
            $data['email'] = array('value' => $data['email'], 'status' => 'unconfirmed');
        }

        // check captcha
        $auth_config = wa()->getAuthConfig();
        if (isset($auth_config['signup_captcha']) && $auth_config['signup_captcha']) {
            if (!wa()->getCaptcha()->isValid()) {
                $errors['captcha'] = _ws('Invalid captcha');
                $is_error = true;
            }
        }

        if ($is_error) {
            return false;
        }

        // remove password_confirm field
        unset($data['password_confirm']);
        // set advansed data
        $data['create_method'] = 'signup';
        $data['create_ip'] = waRequest::getIp();
        $data['create_user_agent'] = waRequest::getUserAgent();
        // try save contact
        $contact = new waContact();
        if (!$errors = $contact->save($data, true)) {
            if (!empty($data['email'])) {
                $this->send($contact);
            }
            // after sign up callback
            $this->afterSignup($contact);
            // auth new contact
            wa()->getAuth()->auth($contact);
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
}