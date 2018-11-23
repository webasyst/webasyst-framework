<?php

/**
 * Class waBaseForgotPasswordAction
 *
 * Base action for restore password for backend & frontend
 * Here common algorithm
 *
 * Must be called waForgotPasswordAction
 * But for backward compatibility with old Shop (and other apps) MUST be called waBaseForgotPasswordAction
 * (waForgotPasswordAction is in use)
 *
 */
abstract class waBaseForgotPasswordAction extends waLoginModuleController
{
    /**
     * @var waAuthConfig
     */
    protected $auth_config;
    protected $env;

    public function __construct($params = null)
    {
        parent::__construct($params);
        if (!$this->env) {
            $this->env = wa()->getEnv();
        }
    }

    public function execute()
    {
        // In one time password mode page is unavailable
        if ($this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD) {
            $this->notFound();
        }

        if ($this->isSetPasswordMode()) {
            if ($this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_GENERATE_PASSWORD) {
                $this->setGeneratedPassword($this->getHash());
            } else {
                $this->setPassword($this->getHash());
            }
        } else {
            $this->forgotPassword();
        }
    }

    /**
     * Ger hash that gives rights for set new password
     * @return string
     */
    protected function getHash()
    {
        $request = $this->getRequest()->request();

        // For backward compatibility leave name of param 'key'
        // But for clarity new name of param is 'hash'
        if (array_key_exists('key', $request)) {
            return is_scalar($request['key']) ? (string)$request['key'] : '';
        } elseif (array_key_exists('hash', $request)) {
            return is_scalar($request['hash']) ? (string)$request['hash'] : '';
        } else {
            return '';
        }
    }

    /**
     * If Hash presented in url than we in Set-Password Form
     * @return mixed
     */
    protected function isSetPasswordMode()
    {
        return !!$this->getHash();
    }

    protected function notFound()
    {
        throw new waException(_w('Page not found'), 404);
    }

    /**
     * Generates and sets password
     * Method is actual only for 'generated_password' auth type
     *
     * Needs hash, that grands rights for setting new (or generated) password
     *
     * NOTICE:
     * There is a little bit overhead for 'confirmation_code' case
     * But to keep code more simple and not duplicated we will use this method
     * @see forgotPassword around 'confirmation_code' case
     *
     * @param string $hash
     * @throws waException
     */
    protected function setGeneratedPassword($hash)
    {
        list($ok, $details) = $this->validateHash($hash);

        if (!$ok) {
            $this->notFound();
        }

        /**
         * @var waContact $contact
         */
        $contact = $details['contact'];

        $channel_type = $details['channel_type'];
        $channel = $this->auth_config->getVerificationChannel($channel_type);

        if (!$channel) {
            $this->notFound();
        }

        $channel = waVerificationChannel::factory($channel);

        // remove hash
        $this->invalidateHash($hash);

        $result = $this->sendGeneratedPassword($contact, $channel);
        if (!$result) {
            $this->notFound();
        }

        if ($channel->getType() === waVerificationChannelModel::TYPE_EMAIL) {
            $sent_message = _ws('Done! A message with a new password has been sent to email address <strong>%s</strong>.');
            $sent_message = sprintf($sent_message, $details['address']);
        } else {
            $sent_message = _ws('Done! An SMS message with a new password has been sent to phone number <strong>%s</strong>.');
            $sent_message = sprintf($sent_message, $details['address']);
        }

        $this->assign(array(
            'generated_password_sent' => true,
            'used_address' => $details['address'],
            'generated_password_sent_message' => $sent_message
        ));

        if ($this->needRedirects()) {
            $this->redirect($this->getLoginUrl());
        }
    }

    protected function setPassword($hash)
    {
        $auth = wa()->getAuth();

        list($ok, $details) = $this->validateHash($hash);

        if (!$ok) {
            $this->notFound();
        }

        /**
         * @var waContact $details
         */
        $contact = $details['contact'];

        $errors = array();
        if (waRequest::method() == 'post') {

            $data = $this->getData();
            $errors = $this->setPasswordValidate($data);

            if (!$errors) {

                // save new password
                $contact['password'] = $data['password'];
                $contact->save();

                // remove hash
                $this->invalidateHash($hash);

                // auth
                $auth->auth($contact);
                $this->assign('contact', $contact);

                // redirect
                if ($this->needRedirects()) {
                    $this->redirect(wa()->getAppUrl());
                }
            }
        }

        $this->assign('login', $details['address']);
        $this->assign('address', $details['address']);
        $this->assign('channel_type', $details['channel_type']);
        $this->assign('errors', $errors);
        $this->assign('set_password', true);

    }

    protected function setPasswordValidate($data)
    {
        $errors = array();
        $password = $data['password'];
        $password_confirm = $data['password_confirm'];
        if (strlen($password) <= 0) {
            $errors['password'] = _ws('Password can not be empty.');
        }
        if ($password !== $password_confirm) {
            $errors['password_confirm'] = _ws('Passwords do not match');
        }
        if ($this->auth_config->needLoginCaptcha() && !wa()->getCaptcha()->isValid()) {
            $errors['captcha'] = _ws('Invalid captcha');
        }
        return $errors;
    }

    /**
     * @param string $login
     * @param waAuth $auth
     * @return waContact|bool
     */
    protected function findContact($login, $auth)
    {
        $is_user = $auth->getOption('is_user');

        $login = is_scalar($login) ? (string)$login : '';
        if (strlen($login) <= 0) {
            return false;
        }

        $priority = null;
        if ($this->isValidEmail($login)) {
            $priority = 'email';
        } elseif ($this->isValidPhoneNumber($login)) {
            $priority = 'phone';
        }

        $contact_info = $auth->getByLogin($login, $priority);

        // Make sure it's a user if asked for a user
        if (!empty($contact_info) && (!$is_user || $contact_info['is_user'])) {
            return new waContact($contact_info);
        }

        return false;
    }

    protected function forgotPasswordValidate($data)
    {
        $errors = array();
        $login = $data['login'];
        if (strlen($login) <= 0) {
            $errors['login'] = _ws('Required');
        }

        if ($this->auth_config->needLoginCaptcha() && !wa()->getCaptcha()->isValid()) {
            $errors['captcha'] = _ws('Invalid captcha');
        }

        if ($errors) {
            return $errors;
        }

        // IMPORTANT: Protocol detail
        // If 'confirmation_code' presented
        //   than we are in "Confirmation step" forgot-password form
        // If 'confirmation_code' NOT presented
        //   than client request new 'confirmation_code'

        $confirmation_code_presented = isset($data['confirmation_code']);


        // Validate code
        if ($confirmation_code_presented) {
            if (empty($data['confirmation_code'])) {
                $errors['confirmation_code'] = _ws('Enter a confirmation code to complete the operation.');
            } else {
                if (!$this->validateCode($data['confirmation_code'], $data['login'])) {
                    $errors['confirmation_code'] = _ws('Incorrect confirmation code. Try again or request a new code.');
                }
            }
        }

        return $errors;
    }

    /**
     * IMPORTANT:
     * Recovery password request MUST BE limited by timeout
     *
     * @return bool
     */
    protected function isTimeoutPassed()
    {
        $key = 'wa/forgotpassword/last_time/';
        $last_time = wa()->getStorage()->get($key);
        if (!wa_is_int($last_time) || $last_time <= 0) {
            $last_time = 0;
        }
        $now_time = time();
        $timeout = $this->auth_config->getRecoveryPasswordTimeout();
        $result = $now_time - $last_time > $timeout;
        wa()->getStorage()->set($key, $now_time);
        return $result;
    }

    protected function forgotPassword()
    {
        $errors = array();
        $auth = wa()->getAuth();

        if (waRequest::method() == 'post' && !waRequest::post('ignore')) {

            $data = $this->getData();
            $errors = $this->forgotPasswordValidate($data);
            $login = $data['login'];

            if (!$errors) {

                // IMPORTANT: Protocol detail
                // If 'confirmation_code' presented than we are in "Confirmation step" forgot-password form
                if (isset($data['confirmation_code'])) {

                    /**
                     * Confirmation code is already checked
                     * @see forgotPasswordValidate
                     * @see validateCode
                     */

                    // Secret HASH, that grant temporary rights to set new password
                    $hash = $this->generateHashByCode($data['confirmation_code']);


                    // 'generated_password' auth type case
                    if ($this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_GENERATE_PASSWORD) {

                        // To keep code simple call this method - a little be overhead for this case
                        $this->setGeneratedPassword($hash);
                        return;

                    }

                    // 'user_password' case

                    if ($this->needRedirects()) {

                        // Redirect to Set-Password Form
                        $set_password_url = $this->auth_config->getForgotPasswordUrl(array(
                            'get' => array('key' => $hash)
                        ));
                        $this->redirect($set_password_url);

                    } else {

                        // Just signal client side about what happens
                        $this->assign('code_confirmed', true);
                        $this->assign('hash', $hash);

                    }

                    return;
                }

                if ($contact = $this->findContact($login, $auth)) {
                    if ($contact->get('is_banned')) {
                        $msg = _ws('Password recovery for “%s” has been banned.');
                        $msg = sprintf($msg, $login);
                        $errors['ban'] = $msg;
                    } else {

                        list($ok, $details) = $this->sendPasswordRecoveryMessage($contact, array('login' => $login));

                        if ($ok) {
                            $details['sent_ok'] = true;

                            // Need in Set-Password Form mode
                            $this->saveLastSendDetails($details);

                            $this->assign($details);
                        } else {
                            $errors = $details;
                        }

                    }
                } else {
                    $errors['login'] = _ws('No user with this login name has been found.');
                }
            }
        }

        $this->assign('options', $auth->getOptions());
        $this->assign('errors', $errors);

        if ($this->layout) {
            $this->layout->assign('errors', $errors);
            // Backward compatibility
            $this->layout->assign('error', reset($errors));
        }
    }

    /**
     * @param waContact $contact
     * @param array $options
     * @return array
     *   + 0 - bool status
     *   + 1 - array details
     */
    protected function sendPasswordRecoveryMessage(waContact $contact, $options = array())
    {
        $login = isset($options['login']) && is_scalar($options['login']) ? (string)$options['login'] : '';

        $priority = $this->getChannelPriorityByLogin($login);

        $channels = $this->auth_config->getVerificationChannelInstances($priority);

        $is_available = $this->isChannelAvailable($channels, $login);
        if (!$is_available) {
            $msg = _ws('Couldn’t recover password via “%s”.');
            $msg = sprintf($msg, $login);
            $errors = array('fail' => $msg);
            return array(false, $errors);
        }

        if (!$contact->exists()) {
            return array(false, array(
                'fail' => _ws("Contact doesn't exist.")
            ));
        }

        // Code not presented - check timeout
        if (!$this->isTimeoutPassed()) {
            $errors['timeout'] = array(
                'message' => $this->auth_config->getRecoveryPasswordTimeoutErrorMessage(),
                'timeout' => $this->auth_config->getRecoveryPasswordTimeout()
            );
            return array(false, $errors);
        }

        $sent = false;
        $channel_type = null;
        foreach ($channels as $channel) {

            $channel_type = $channel->getType();

            $options = array(
                'site_url' => $this->auth_config->getSiteUrl(),
                'site_name' => $this->auth_config->getSiteName(),
                'login_url' => $this->auth_config->getLoginUrl(array(), true),
            );

            if ($channel_type == waVerificationChannelModel::TYPE_EMAIL) {
                $url = $this->auth_config->getRecoveryPasswordUrl(array(
                    'get' => 'key={$secret_hash}'
                ), true);
                $options['recovery_url'] = $url;
            } else {
                $options['use_session'] = true;
            }

            $sent = $channel->sendRecoveryPasswordMessage($contact, $options);

            if ($sent) {
                break;
            }
        }

        if (!$sent) {
            $sent_error = _ws('Sorry, we cannot recover password for this login name or email. Please refer to your system administrator.');
            $this->logErrors(array(
                $sent_error,
                "Email settings are not correct or email transport does not work"
            ));
            return array(false, array(
                'sent' => $sent_error
            ));
        }

        $details = array(
            'channel_type' => $channel_type,
            'sent_message' => '',
            'timeout_message' => '',
            'timeout' => 0,
            'address' => ''
        );

        if ($channel_type === waVerificationChannelModel::TYPE_EMAIL) {

            if ($this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_GENERATE_PASSWORD) {
                $sent_message = _ws('Please check new mail at <strong>%s</strong>, we have sent you a message with a password recovery link to confirm the password change. After confirmation, we will send you your password in the next message.');
            } else {
                $sent_message = _ws('Please check new mail at <strong>%s</strong>, we have sent you a message with a password recovery link.');
            }

            $sent_message = sprintf($sent_message, $contact->get('email', 'default'));
            $details['sent_message'] = $sent_message;
            $details['address'] = $contact->get('email', 'default');

        } elseif ($channel_type === waVerificationChannelModel::TYPE_SMS) {

            $details['sent_message'] = _ws('Confirm your phone number');
            $details['timeout_message'] = $this->auth_config->getRecoveryPasswordTimeoutMessage();
            $details['timeout'] = $this->auth_config->getRecoveryPasswordTimeout();
            $details['address'] = $contact->get('phone', 'default');

        }

        return array($sent, $details);
    }

    protected function sendGeneratedPassword(waContact $contact, waVerificationChannel $channel)
    {
        $password = waContact::generatePassword();

        $result = $channel->sendPassword($contact, $password, array(
            'site_url' => $this->auth_config->getSiteUrl(),
            'site_name' => $this->auth_config->getSiteName(),
            'login_url' => $this->auth_config->getLoginUrl(array(), true),
        ));
        if (!$result) {
            return false;
        }
        $contact->save(array('password' => $password));
        return true;
    }

    /**
     * @param string $to - email
     * @param string $url - url to reset password
     * @return bool
     */
    protected function send($to, $url)
    {
        $this->assign('url', $url);
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

    protected function validateCode($code, $phone)
    {
        $channel = $this->auth_config->getSMSVerificationChannel();
        if (!$channel) {
            return false;
        }
        $channel = waVerificationChannel::factory($channel);
        return $channel->validateRecoveryPasswordSecret($code, array(
            'recipient' => $phone
        ));
    }

    /**
     * Validate hash and return proper contact and address (login)
     * @param string $hash
     * @return array
     *   - 0 bool <status>
     *   - 1 array <details>
     * @throws waException
     */
    protected function validateHash($hash)
    {
        $send_details = $this->getLastSendDetails();

        $send_details['channel_type'] = ifset($send_details['channel_type']);

        if ($send_details['channel_type'] === waVerificationChannelModel::TYPE_SMS) {
            $channel = $this->auth_config->getSMSVerificationChannel();
        } else {
            $channel = $this->auth_config->getEmailVerificationChannel();
        }

        if (!$channel) {
            return array(false, array());
        }

        $channel = waVerificationChannel::factory($channel);
        if (!$channel) {
            return array(false, array());
        }

        // Define Secret
        if ($channel->getType() === waVerificationChannelModel::TYPE_SMS) {
            $secret = $this->extractCodeFromHash($hash);
        } else {
            $secret = $hash;
        }

        // ADDRESS where message has sent to
        // IF it is known than pass to validator to STRENGTHEN validation
        $options = array();
        if (isset($send_details['address'])) {
            $options['recipient'] = $send_details['address'];
        }

        $validation_result = $channel->validateRecoveryPasswordSecret($secret, $options);

        // Validation is failed
        if (!$validation_result['status']) {
            return array(false, array());
        }

        // Ok we have address
        $validated_address = $validation_result['details']['address'];

        // Define contact by address (or contact_id)
        if ($channel->getType() === waVerificationChannelModel::TYPE_SMS) {

            $cdm = new waContactDataModel();

            $contact_id = $cdm->getContactWithPasswordByPhone($validated_address);
            $contact = new waContact($contact_id);
            if (!$contact->exists()) {
                return array(false, array());
            }

        } else {

            // With current validation process must be bind certain contact
            $contact_id = $validation_result['details']['contact_id'];
            $contact = new waContact($contact_id);

            // Contact doesn't exist or not have been bind with validation process
            if (!$contact->exists()) {
                return array(false, array());
            }

            // Check existing email and its binding with contact
            $cem = new waContactEmailsModel();
            $email_row = $cem->getByField(array(
                'contact_id' => $contact->getId(),
                'email' => $validated_address
            ));

            // Email has been deleted from this contact
            if (!$email_row) {
                return array(false, array());
            }

        }

        // set contact locale
        if ($contact['locale']) {
            wa()->setLocale($contact['locale']);
            waLocale::loadByDomain('webasyst', wa()->getLocale());
        }

        return array(true, array(
            'contact' => $contact,
            'address' => $validated_address,
            'channel_type' => $channel->getType()
        ));
    }

    protected function invalidateHash($hash)
    {

        $send_details = $this->getLastSendDetails();

        $send_details['channel_type'] = ifset($send_details['channel_type']);

        if ($send_details['channel_type'] === waVerificationChannelModel::TYPE_SMS) {
            $channel = $this->auth_config->getSMSVerificationChannel();
        } else {
            $channel = $this->auth_config->getEmailVerificationChannel();
        }

        if (!$channel) {
            return;
        }

        $channel = waVerificationChannel::factory($channel);

        // Define Secret
        if ($channel->getType() === waVerificationChannelModel::TYPE_SMS) {
            $secret = $this->extractCodeFromHash($hash);
        } else {
            $secret = $hash;
        }

        $channel->invalidateRecoveryPasswordSecret($secret);
    }

    public function getTitle()
    {
        return _ws('Password recovery');
    }

    protected function prepareData($data)
    {
        if ($this->isSetPasswordMode()) {
            $data['password'] = $this->getScalarValue('password', $data);
            $data['password_confirm'] = $this->getScalarValue('password_confirm', $data);
        } else {
            $data['login'] = $this->getScalarValue('login', $data);
        }
        return $data;
    }

    protected function saveLastSendDetails($details)
    {
        $key = 'wa/forgotpassword/send_details/';
        $this->getStorage()->set($key, $details);
    }

    protected function getLastSendDetails()
    {
        $key = 'wa/forgotpassword/send_details/';
        return $this->getStorage()->get($key);
    }

    protected function delLastSendDetails()
    {
        $key = 'wa/forgotpassword/sent_details/';
        $this->getStorage()->del($key);
    }

    protected function saveGeneratedHash($hash)
    {
        $key = 'wa/forgotpassword/generated_hash';
        $this->getStorage()->set($hash, $key);
    }

    protected function getGeneratedHash()
    {
        $key = 'wa/forgotpassword/generated_hash';
        return $this->getStorage()->get($key);
    }

    protected function delGeneratedHash()
    {
        $key = 'wa/forgotpassword/generated_hash';
        $this->getStorage()->del($key);
    }

    protected function generateHashByCode($code)
    {
        $hash = md5(uniqid($code));
        $hash = substr($hash, 0, 16) . $code . substr($hash, 16);
        $this->saveGeneratedHash($hash);
        return $hash;
    }

    protected function extractCodeFromHash($hash)
    {
        return substr($hash, 16, -16);
    }

    /**
     * Way to tell waLoginForm about what had happen in here
     */
    protected function saveLastResponse()
    {
        wa()->getStorage()->set('wa/forgotpassword/last_response', $this->response);
    }

    protected function afterExecute()
    {
        parent::afterExecute();
        $this->saveLastResponse();
    }

    protected function beforeRedirect($params = array(), $code = null)
    {
        $this->saveLastResponse();
    }

    protected function getLoginUrl()
    {
        return $this->auth_config->getLoginUrl();
    }
}
