<?php

/**
 * Class waBaseLoginAction
 *
 * Base action for login in backend & frontend
 * Here common algorithm
 *
 * Must be called waLoginAction
 * But for backward compatibility with old Shop (and other apps) MUST be called waBaseLoginAction
 * (waLoginAction is in use)
 *
 */
abstract class waBaseLoginAction extends waLoginModuleController
{
    /**
     * @var waAuthConfig
     */
    protected $auth_config;
    protected $env;

    public function __construct($params = null)
    {
        parent::__construct($params);
        if ($this->env === null) {
            $this->env = wa()->getEnv();
        }
    }

    public function execute()
    {
        if (wa()->getRequest()->request('send_onetime_password')) {
            $this->trySendOnetimePassword();
            return;
        }

        if (wa()->getRequest()->method() == 'get') {
            // remember enabled flag
            $this->assign('remember', wa()->getRequest()->cookie('remember', 1));
            // save referrer, to redirect there after logging in
            $this->saveReferer();
        }

        if (wa()->getAuth()->isAuth()) {
            $this->afterAuth();
        }

        // check XMLHttpRequest (ajax)
        $this->checkXMLHttpRequest();

        $this->checkAuthConfig();

        if ($this->getRequest()->post()) {
            $this->tryAuth();
        }
    }

    protected function validateLogin($login)
    {
        $login = is_scalar($login) ? (string)$login : '';
        if (strlen($login) <= 0) {
            return _ws('Login is required');
        } else {
            return null;
        }
    }

    protected function validatePassword($password)
    {
        $password = is_scalar($password) ? (string)$password : '';
        if (strlen($password) <= 0) {
            return _ws('Password is required');
        } else {
            return null;
        }
    }

    protected function validateCaptcha()
    {
        if (!$this->auth_config->needLoginCaptcha()) {
            return null;
        }
        if (!wa()->getCaptcha()->isValid()) {
            return _ws('Invalid captcha');
        } else {
            return null;
        }
    }

    protected function validateCode($code, $phone)
    {
        if (!$this->auth_config->getSignupConfirm()) {
            return null;
        }
        if (!$code) {
            return _ws('Enter a confirmation code to complete signup');
        }

        if (!$this->isValidPhoneNumber($phone)) {
            return _ws('Incorrect phone number value');
        }

        $channel = $this->auth_config->getSMSVerificationChannelInstance();

        $result = $channel->validateSignUpConfirmation($code, $phone);
        if (!$result['status']) {
            return _ws('Incorrect or expired confirmation code. Try again or request a new code.');
        } else {
            return null;
        }
    }

    protected function validate($data, $fields = null)
    {
        $fields = is_array($fields) || is_scalar($fields) ? (array)$fields : array(
            'login', 'password', 'captcha', 'confirmation_code'
        );

        $errors = array();
        foreach ($fields as $field_id) {
            $error = null;
            if ($field_id === 'login') {
                $error = $this->validateLogin($data['login']);
            } elseif ($field_id === 'password') {
                $error = $this->validatePassword($data['password']);
            } elseif ($field_id === 'captcha') {
                $error = $this->validateCaptcha();
            } elseif ($field_id === 'confirmation_code' && isset($data['confirmation_code'])) {
                $error = $this->validateCode($data['confirmation_code'], $data['login']);
            }
            if ($error !== null) {
                $errors[$field_id] = $error;
            }
        }
        return $errors;
    }

    protected function tryAuth()
    {
        $data = $this->getData();
        $errors = $this->validate($data);

        if ($errors) {
            $this->assign('errors', $errors);
            return false;
        }

        $channels = $this->auth_config->getVerificationChannelInstances();

        /**
         * @var waVerificationChannel[] $channels
         */
        $is_available = $this->isChannelAvailable($channels, $data['login']);
        if (!$is_available) {
            $msg = _ws('Couldn’t sign in via “%s”.');
            $msg = sprintf($msg, $data['login']);
            $errors = array('auth' => $msg);
            $this->assign('errors', $errors);
            return false;
        }

        $auth = wa()->getAuth();

        if (isset($data['confirmation_code'])) {
            $contact_info = $auth->getByLogin($data['login'], waAuth::LOGIN_FIELD_PHONE);
            if ($contact_info) {
                $dm = new waContactDataModel();
                $dm->updateContactPhoneStatus($contact_info['id'], $data['login'], waContactDataModel::STATUS_CONFIRMED);
            }
        }

        $errors = array();

        try {
            if ($auth->auth($data)) {
                $this->logAction('login', $this->env);
                $this->afterAuth();
            }
        } catch (waAuthConfirmEmailException $e) {

            // EMAIL IS NOT CONFIRMED

            $channel = $this->auth_config->getEmailVerificationChannelInstance();

            $contact_info = $auth->getByLogin($data['login'], waAuth::LOGIN_FIELD_EMAIL);
            $contact = new waContact($contact_info['id']);

            // Confirmation code has not be sent, so sent it
            $sent = $channel->hasSentSignUpConfirmationMessage($contact);
            if (!$sent) {
                $confirmation_url = $this->auth_config->getSignUpUrl(array(
                    'get' => array('confirm' => 'confirmation_hash')
                ), true);
                $confirmation_url = str_replace('confirmation_hash', '{$confirmation_hash}', $confirmation_url);
                $channel->sendSignUpConfirmationMessage($contact, array(
                    'site_url' => $this->auth_config->getSiteUrl(),
                    'site_name' => $this->auth_config->getSiteName(),
                    'confirmation_url' => $confirmation_url,
                ));
            }

            // Show proper error
            $errors['login']['confirm_email'] = $e->getMessage();

        } catch (waAuthConfirmPhoneException $e) {

            // PHONE IS NOT CONFIRMED

            $errors['confirmation_code']['confirm_phone'] = $e->getMessage();

            // check timeout first
            if (!$this->isSentCodeTimeoutPassed()) {
                $errors['confirmation_code'] = array(
                    'timeout' => array(
                        'message' => $this->auth_config->getConfirmationCodeTimeoutErrorMessage(),
                        'timeout' => $this->auth_config->getConfirmationCodeTimeout()
                    )
                );
            } else {

                // Ok timeout is passed - resent code

                $channel = $this->auth_config->getSMSVerificationChannelInstance();
                $sent = $channel->sendSignUpConfirmationMessage($data['login'], array(
                    'use_session' => true
                ));

                if (!$sent) {
                    $errors['auth'] = "Sorry, we cannot sent confirmation code. Please refer to your system administrator.";
                } else {

                    $msg = _ws('An SMS message has been sent to phone number <strong>%s</strong> for you to confirm signup.');
                    $msg = sprintf($msg, waContactPhoneField::cleanPhoneNumber($data['login']));

                    $this->assign(array(
                        'code_sent' => true,
                        'code_sent_message' => $msg,
                        'code_sent_timeout_message' => $this->auth_config->getConfirmationCodeTimeoutMessage(),
                        'code_sent_timeout' => $this->auth_config->getConfirmationCodeTimeout()
                    ));
                }

            }



        } catch (waAuthInvalidCredentialsException $e) {
            if ($this->auth_config->getAuthType() ===  waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD) {
                $errors['password'] = _ws('Incorrect or expired one-time password. Try again or request a new one-time password.');
            } else {
                $errors['auth'] = $e->getMessage();
            }
        } catch (waException $e) {
            $errors['auth'] = $e->getMessage();
        }

        if ($errors) {

            $login = $this->getData('login');

            // if user exists - need logging this action
            $user = $auth->getByLogin($login);
            if ($user && is_array($user) && ifset($user,'is_user', null) == 1) {
                $params = array(
                    'source' => $this->env,
                    'login'  => $login,
                    'ip'     => waRequest::getIp()
                );
                $this->logAction('login_failed', $params);
            }

        }

        $this->assign('errors', $errors);

        return !$errors;
    }

    protected function isSentCodeTimeoutPassed()
    {
        $key = 'wa/login/sent_code/last_time/';
        $last_time = wa()->getStorage()->get($key);
        if (!wa_is_int($last_time) || $last_time <= 0) {
            $last_time = 0;
        }
        $now_time = time();
        $timeout = $this->auth_config->getConfirmationCodeTimeout();
        $result = $now_time - $last_time > $timeout;
        wa()->getStorage()->set($key, $now_time);
        return $result;
    }

    protected function afterExecute()
    {
        parent::afterExecute();
        $auth = wa()->getAuth();
        $this->view->assign('options', $auth->getOptions());
    }

    protected function prepareData($data)
    {
        $data = is_array($data) ? $data : array();
        $data['login'] = isset($data['login']) && is_scalar($data['login']) ? (string)$data['login'] : '';
        $data['password'] = isset($data['password']) && is_scalar($data['password']) ? (string)$data['password'] : '';
        $data['remember'] = !empty($data['remember']);
        $data['captcha'] = isset($data['captcha']) && is_scalar($data['captcha']) ? (string)$data['captcha'] : '';
        return $data;
    }

    protected function trySendOnetimePassword()
    {
        list($ok, $details) = $this->sendOnetimePassword();


        $this->assign('send_onetime_password_ok', $ok);

        if ($ok) {
            $this->assign($details);
        } else {
            if ($details) {
                $errors = $details;
            } else {
                $errors = array('send_onetime_password' => _ws('One-time password was not sent.'));
            }
            $this->assign('errors', $errors);
        }

        return $ok;
    }

    /**
     * @return array
     *  * 0 - bool <status>
     *  * 1 - array <details>
     */
    protected function sendOnetimePassword()
    {
        $auth_config = $this->auth_config;
        if ($auth_config->getAuthType() !== waDomainAuthConfig::AUTH_TYPE_ONETIME_PASSWORD) {
            return array(false, array());
        }

        $data = $this->getData();

        $errors = $this->validate($data, array('login', 'captcha'));
        if ($errors) {
            return array(false, $errors);
        }

        $user_info = wa()->getAuth()->getByLogin($data['login']);
        if (!$user_info) {
            $signup_url = $auth_config->getSignUpUrl();
            $error = sprintf(_ws('Contact does not exist. <a href="%s">Sign up</a> first.'), $signup_url);
            return array(false, array('login' => $error));
        }

        $priority = $this->getChannelPriorityByLogin($data['login']);
        $channels = $auth_config->getVerificationChannelInstances($priority);

        $is_available = $this->isChannelAvailable($channels, $data['login']);
        if (!$is_available) {
            $msg = _ws('Couldn’t sign in via “%s”.');
            $msg = sprintf($msg, $data['login']);
            $errors = array('auth' => $msg);
            return array(false, $errors);
        }

        $recipient = new waContact($user_info['id']);
        if (!$recipient->exists()) {
            return array(false, array());
        }

        if (!$this->isTimeoutPassed()) {
            $errors = array(
                'timeout' => array(
                    'message' => $this->auth_config->getOnetimePasswordTimeoutErrorMessage(),
                    'timeout' => $this->auth_config->getOnetimePasswordTimeout()
                )
            );
            return array(false, $errors);
        }

        $asset_id = null;
        $channel_type = null;

        foreach ($channels as $channel) {

            $asset_id = $channel->sendOnetimePasswordMessage($recipient, array(
                'site_url' => $this->auth_config->getSiteUrl(),
                'site_name' => $this->auth_config->getSiteName(),
                'login_url' => $this->auth_config->getLoginUrl(array(), true),
            ));

            if ($asset_id > 0) {
                $channel_type = $channel->getType();
                break;
            }
        }

        if (!$asset_id) {
            return array(false, array());
        }

        $csm = new waContactSettingsModel();
        $csm->set($user_info['id'], 'webasyst', 'onetime_password_id', $asset_id);

        $sent_message = _ws('One-time password has been sent.');

        if ($channel_type == waVerificationChannelModel::TYPE_EMAIL) {
            $sent_message = _ws('One-time password has been sent to your email address.');
        } elseif ($channel_type == waVerificationChannelModel::TYPE_SMS) {
            $sent_message = _ws('One-time password has been sent to you as an SMS.');
        }



        $details = array(
            'onetime_password_sent_message' => $sent_message,
            'onetime_password_timeout_message' => $this->auth_config->getOnetimePasswordTimeoutMessage(),
            'onetime_password_timeout' => $this->auth_config->getOnetimePasswordTimeout()
        );

        return array(true, $details);
    }

    protected function isTimeoutPassed()
    {
        $key = 'wa/login/onetime_password/last_time/';
        $last_time = wa()->getStorage()->get($key);
        if (!wa_is_int($last_time) || $last_time <= 0) {
            $last_time = 0;
        }
        $now_time = time();
        $timeout = $this->auth_config->getOnetimePasswordTimeout();
        $result = $now_time - $last_time > $timeout;
        wa()->getStorage()->set($key, $now_time);
        return $result;
    }

    abstract protected function checkAuthConfig();

    /**
     * Save referrer, to redirect there after logging in (in afterAuth() method)
     * @see afterAuth
     * @return mixed
     */
    abstract protected function saveReferer();

    protected function checkXMLHttpRequest()
    {
        $is_json_mode = $this->isJsonMode();

        // json_mode means that we work with form
        // not just regular ajax request
        // So ignore checking
        if ($is_json_mode) {
            return;
        }


        // Voodoo magic: reload page when user performs an AJAX request after session died.
        if (waRequest::isXMLHttpRequest() && (waRequest::param('secure') || $this->env == 'backend')) {
            //
            // The idea behind this is quite complicated.
            //
            // When browser expects JSON and gets this response then the error handler is called.
            // Default error handler (see wa.core.js) looks for the wa-session-expired header
            // and reloads the page when it's found.
            //
            // On the other hand, when browser expects HTML, it's most likely to insert it to the DOM.
            // In this case <script> gets executed and browser reloads the whole layout to show login page.
            // (This is also the reason to use 200 HTTP response code here: no error handler required at all.)
            //
            header('wa-session-expired: 1');
            echo _ws('Session has expired. Please reload current page and log in again.').'<script>window.location.reload();</script>';
            exit;
        }
    }

    /**
     * Template method will call after successful auth
     * Also @see saveReferer
     * @return mixed
     */
    abstract protected function afterAuth();

    protected function getLoginUrl()
    {
        return $this->auth_config->getLoginUrl();
    }
}
