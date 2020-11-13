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

    /**
     * waBaseForgotPasswordAction constructor.
     * @param null $params
     */
    public function __construct($params = null)
    {
        parent::__construct($params);
        if (!$this->env) {
            $this->env = wa()->getEnv();
        }
    }

    /**
     * Entry point of action
     * @throws waException
     */
    public function execute()
    {
        wa()->getResponse()->setTitle(_ws('Password recovery'));

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

    /**
     * Not found error helper
     * @throws waException
     */
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
        // diagnostic already printed inside
        list($ok, $details) = $this->validateHash($hash);

        if (!$ok) {
            $this->notFound();
        }

        /**
         * @var waContact $contact
         */
        $contact = $details['contact'];

        $channel_type = $details['channel_type'];

        $channel = $this->auth_config->getVerificationChannelInstance($channel_type);

        // remove hash
        $this->invalidateHash($hash);

        // diagnostic already printed inside
        $result = $this->sendGeneratedPassword($contact, $channel);
        if (!$result) {
            $this->notFound();
        }

        // prepare message
        if ($channel->getType() === waVerificationChannelModel::TYPE_EMAIL) {
            $sent_message = _ws('Done! A message with a new password has been sent to email address <strong>%s</strong>.');
            $sent_message = sprintf($sent_message, $details['address']);
        } else {
            $sent_message = _ws('Done! An SMS message with a new password has been sent to phone number <strong>%s</strong>.');

            $phone_formatted = $details['address'];
            $phone_field = waContactFields::get('phone');
            if ($phone_field) {
                $phone_formatted = $phone_field->format($details['address'], 'value');
            }

            $sent_message = sprintf($sent_message, $phone_formatted);
        }

        // Assign result vars - always assign even if we do call redirect.
        // Redirect can be json driven, so for js api need assign vars anyway
        $this->assign(array(
            'generated_password_sent' => true,
            'used_address' => $details['address'],
            'generated_password_sent_message' => $sent_message
        ));

        // redirect
        if ($this->needRedirects()) {
            $this->redirect($this->getLoginUrl());
        }
    }

    /**
     * Set user password
     *
     * Needs hash, that grands rights for set password
     *
     * @param string $hash
     * @throws waAuthConfirmEmailException
     * @throws waAuthConfirmPhoneException
     * @throws waAuthException
     * @throws waAuthInvalidCredentialsException
     * @throws waException
     */
    protected function setPassword($hash)
    {
        $auth = wa()->getAuth();

        // diagnostic already printed inside
        list($ok, $details) = $this->validateHash($hash);

        if (!$ok) {
            $this->notFound();
        }

        /**
         * @var waContact $contact
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

                // If contact is not confirmed yet, restoring password is enough to mark as confirmed
                if ($this->auth_config->getSignUpConfirm()) {

                    if ($details['channel_type'] === waVerificationChannelModel::TYPE_EMAIL) {
                        $cem = new waContactEmailsModel();
                        $email_row = $cem->getByField(array(
                            'contact_id' => $contact->getId(),
                            'email' => $details['address']
                        ));
                        if ($email_row) {
                            // Email is now confirmed
                            $cem->updateById($email_row['id'], array('status' => waContactEmailsModel::STATUS_CONFIRMED));
                        }
                    } elseif ($details['channel_type'] === waVerificationChannelModel::TYPE_SMS) {
                        $cdm = new waContactDataModel();
                        $phone_row = $cdm->getByField(array(
                            'contact_id' => $contact->getId(),
                            'field' => 'phone',
                            'value' => $details['address']
                        ));
                        if ($phone_row) {
                            // Phone is now confirmed
                            $cdm->updateById($phone_row['id'], array('status' => waContactDataModel::STATUS_CONFIRMED));
                        }
                    }
                }

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

    /**
     * Validate input data for set password step (and user password mode)
     * @param $data
     * @return array
     */
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
        } elseif (strlen($password) > waAuth::PASSWORD_MAX_LENGTH) {
            $errors['password'] = _ws('Specified password is too long.');
        }

        if ($this->auth_config->needLoginCaptcha()) {
            $captcha_options = [];
            if ($this->auth_config instanceof waDomainAuthConfig) {
                $captcha_options['app_id'] = $this->auth_config->getApp();
            }
            if (!wa()->getCaptcha($captcha_options)->isValid()) {
                $errors['captcha'] = _ws('Invalid captcha');
            }
        }
        return $errors;
    }

    /**
     * Find contact helper, wrapper around auth provider getByLogin method, but returns waContact if success
     * @param string $login
     * @param waAuth $auth
     * @return waContact|bool
     * @throws waAuthException
     * @throws waException
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

    /**
     * Try find contact helper, see findContact
     * @param string $login
     * @param waAuth $auth
     *
     * @param string $login
     * @param waAuth $auth
     * @return array
     *   - 0: bool $status
     *   - 1: array $details
     *       - waContact $details['contact'] [optional] If $status == TRUE here we have waContact
     *       - string $details['error_code'] [optional] If $status == FALSE here we have reason why
     *       - string $details['error_msg'] [optional] If $status == FALSE here we have reason why
     *
     * @see findContact
     * @throws waAuthException
     * @throws waException
     */
    protected function tryFindContact($login, $auth)
    {
        $contact = $this->findContact($login, $auth);

        if (!$contact) {
            return array(false, array(
                'error_code' => 'login',
                'error_msg' => _ws('No user with this login name has been found.')
            ));
        }

        if ($contact->get('is_banned')) {
            $msg = _ws('Password recovery for “%s” has been banned.');
            $msg = sprintf($msg, $login);
            return array(false, array(
                'error_code' => 'ban',
                'error_msg' => $msg,
            ));
        }

        return array(true, array(
            'contact' => $contact
        ));
    }

    /**
     * Validate input data on forgot password step (not set password step)
     * @param $data
     * @return array
     */
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
                list($valid, $details) = $this->validateCode($data['confirmation_code'], $data['login']);
                if (!$valid) {
                    $errors['confirmation_code'][$details['error_code']] = $details['error_msg'];
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

    /**
     * Forgot password action step
     * @throws waAuthException
     * @throws waException
     */
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

                list($find_contact_status, $find_contact_details) = $this->tryFindContact($login, $auth);

                if ($find_contact_status) {
                    /**
                     * @var waContact $contact
                     */
                    $contact = $find_contact_details['contact'];

                    // diagnostic already printed inside
                    list($ok, $details) = $this->sendPasswordRecoveryMessage($contact, array('login' => $login));

                    if ($ok) {
                        $details['sent_ok'] = true;

                        // Need in Set-Password Form mode
                        $this->saveLastSendDetails($details);

                        $this->assign($details);
                    } else {
                        $errors = $details;
                    }

                } else {
                    $errors[$find_contact_details['error_code']] = $find_contact_details['error_msg'];
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
     * @throws waException
     */
    protected function sendPasswordRecoveryMessage(waContact $contact, $options = array())
    {
        $login = isset($options['login']) && is_scalar($options['login']) ? (string)$options['login'] : '';

        $priority = $this->getChannelPriorityByLogin($login);

        $channels = $this->auth_config->getVerificationChannelInstances($priority);

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

        $recipient = $contact;

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

                $get_params = [
                    'key' => '{$secret_hash}',
                ];

                if ($this->env === 'backend') {
                    // force login form in backend if webasyst ID auth is forced
                    $is_login_form_forced = wa()->getRequest()->get('force_login_form');
                    if ($is_login_form_forced) {
                        $get_params['force_login_form'] = 1;
                    }
                }

                $url = $this->auth_config->getRecoveryPasswordUrl(array(
                    'get' => $get_params
                ), true);

                $sent = $channel->sendRecoveryPasswordMessage($contact, array_merge($options, array(
                    'recovery_url' => $url
                )));

            } elseif ($channel_type === waVerificationChannelModel::TYPE_SMS) {

                $phone = (string)$login;
                $is_international = substr($phone, 0, 1) === '+';

                // Try send, if not sent try transformation and then send again
                $recipient = array(
                    'id' => $contact->getId(),
                    'address' => $phone,
                );

                $sent = $channel->sendRecoveryPasswordMessage($recipient, array_merge($options, array(
                    'use_session' => true
                )));

                // Not sent, maybe because of sms adapter not work correct with not international phones
                if (!$sent && !$is_international) {
                    // If not international phone number - transform 8 to code (country prefix)
                    $transform_result = $this->auth_config->transformPhone($phone);
                    if ($transform_result['status']) {
                        $recipient = array(
                            'id' => $contact->getId(),
                            'address' => $transform_result['phone']
                        );
                        $sent = $channel->sendRecoveryPasswordMessage($recipient, array_merge($options, array(
                            'use_session' => true
                        )));
                    }
                }

            } else {
                // unknown
                $sent = false;
            }

            if ($sent) {
                break;
            }

            // diagnostic log print
            if ($channel->isEmail()) {
                $diagnostic_message = "Could not send password recovery message by email. Check email settings.\n%s";
                $this->logError(
                    sprintf($diagnostic_message, $channel->getDiagnostic()),
                    array('line' => __LINE__, 'file' => __FILE__)
                );
            } elseif ($channel->isSMS()) {
                $diagnostic_message = "Could not send password recovery message by SMS. View sms.log file for details.\n%s";
                $this->logError(
                    sprintf($diagnostic_message, $channel->getDiagnostic()),
                    array('line' => __LINE__, 'file' => __FILE__)
                );
            } else {
                $diagnostic_message = "Could not send password recovery message.\n%s";
                $this->logError(
                    sprintf($diagnostic_message, $channel->getDiagnostic()),
                    array('line' => __LINE__, 'file' => __FILE__)
                );
            }

        }

        if (!$sent) {
            $sent_error = _ws('Sorry, we cannot recover a password for these contact data. Please refer to your system administrator.');

            // Looks like all channels failed
            $this->logError(
                sprintf("Couldn't send recovery password.\nLooks like there is no any working channel in system. Check auth settings for this env=%s and site=%s",
                    $this->env, $this->auth_config->getSiteUrl()),
                array('line' => __LINE__, 'file' => __FILE__)
            );

            return array(false, array(
                'sent' => $sent_error
            ));
        }

        $details = array(
            'channel_type' => $channel_type,
            'sent_message' => '',
            'timeout_message' => '',
            'timeout' => 0,
            'address' => '',
            'contact_id' => 0,
            'login' => $login
        );

        if (is_array($recipient)) {
            if (isset($recipient['address'])) {
                $details['address'] = $recipient['address'];
            }
            if (isset($recipient['id'])) {
                $details['contact_id'] = $recipient['id'];
            }
        }

        $login_type = $priority;    // type of login - email, phone, or NULL (wa_contact.login)

        if ($channel_type === waVerificationChannelModel::TYPE_EMAIL) {

            // Check login type in light of secure matter:
            // Do not reveal info about how email looks like if login type is not email
            if ($login_type === $channel_type) {

                if ($this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_GENERATE_PASSWORD) {
                    $sent_message = _ws('Please check new mail at <strong>%s</strong>, we have sent you a message with a password recovery link to confirm the password change. After confirmation, we will send you your password in the next message.');
                } else {
                    $sent_message = _ws('Please check new mail at <strong>%s</strong>, we have sent you a message with a password recovery link.');
                }
                $sent_message = sprintf($sent_message, $contact->get('email', 'default'));

            } else {
                if ($this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_GENERATE_PASSWORD) {
                    $sent_message = _ws('Please check new mail, we have sent you a message with a link to confirm the password change. After confirmation, we will send you your password in the next message.');
                } else {
                    $sent_message = _ws('Please check new mail, we have sent you a message with a password recovery link.');
                }
            }


            $details['sent_message'] = $sent_message;
            if ($recipient instanceof waContact) {
                $details['address'] = $contact->get('email', 'default');
                $details['contact_id'] = $contact->getId();
            }

        } elseif ($channel_type === waVerificationChannelModel::TYPE_SMS) {

            $details['sent_message'] = _ws('Confirm your phone number');
            $details['timeout_message'] = $this->auth_config->getRecoveryPasswordTimeoutMessage();
            $details['timeout'] = $this->auth_config->getRecoveryPasswordTimeout();

            if ($recipient instanceof waContact) {
                $details['address'] = $contact->get('phone', 'default');
                $details['contact_id'] = $contact->getId();
            }

        }

        return array($sent, $details);
    }

    /**
     * Generate password and sent it by channel
     * @param waContact $contact
     * @param waVerificationChannel $channel
     * @return bool
     * @throws waException
     */
    protected function sendGeneratedPassword(waContact $contact, waVerificationChannel $channel)
    {
        $password = waContact::generatePassword();

        $recipient = array(
            'id' => $contact->getId(),
            'email' => $contact->get('email', 'default'),
            'phone' => $contact->get('phone', 'default')
        );

        $recipient['phone'] = waContactPhoneField::cleanPhoneNumber($recipient['phone']);

        $options = array(
            'site_url' => $this->auth_config->getSiteUrl(),
            'site_name' => $this->auth_config->getSiteName(),
            'login_url' => $this->auth_config->getLoginUrl(array(), true),
        );

        $result = $channel->sendPassword($recipient, $password, $options);

        if (!$result && $channel->isSMS() && !empty($recipient['phone'])) {
            $transform_result = $this->auth_config->transformPhone($recipient['phone']);

            if ($transform_result['status']) {

                // actually transformed successfully
                $recipient['phone'] = $transform_result['phone'];

                // just in case, check found contact with phone with "+" is the same as that who try restore password
                $found_contact = $this->findContact("+" . $recipient['phone'], wa()->getAuth());

                if ($found_contact && $found_contact->getId() == $contact->getId()) {
                    // try again sent
                    $result = $channel->sendPassword($recipient, $password, $options);
                }
            }
        }

        if (!$result) {

            // diagnostic log print

            if ($channel->isEmail()) {
                $diagnostic_message = "Couldn't send email message with generated password. Check email settings.\n%s";
                $this->logError(
                    sprintf($diagnostic_message, $channel->getDiagnostic()),
                    array('line' => __LINE__, 'file' => __FILE__)
                );
            } elseif ($channel->isSMS()) {
                $diagnostic_message = "Couldn't send SMS with generated password. Explore sms.log for details.\n%s";
                $this->logError(
                    sprintf($diagnostic_message, $channel->getDiagnostic()),
                    array('line' => __LINE__, 'file' => __FILE__)
                );
            } else {
                $diagnostic_message = "Couldn't send message with generated password.\n%s";
                $this->logError(
                    sprintf($diagnostic_message, $channel->getDiagnostic()),
                    array('line' => __LINE__, 'file' => __FILE__)
                );
            }

            return false;
        }

        $contact->save(array('password' => $password));

        return true;
    }

    /**
     * @param string $to - email
     * @param string $url - url to reset password
     * @return bool
     * @throws SmartyException
     * @throws waException
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

    /**
     * @param $code
     * @param $phone
     * @return array
     * @throws waException
     */
    protected function validateCode($code, $phone)
    {
        $default_error = _ws('Incorrect confirmation code. Try again or request a new code.');

        $channel = $this->auth_config->getSMSVerificationChannel();
        if (!$channel) {
            return array(false, array(
                'error_code' => waVerificationChannelSMS::VERIFY_ERROR_INVALID,
                'error_msg' => $default_error,
            ));
        }
        $channel = waVerificationChannel::factory($channel);

        $auth = wa()->getAuth();
        list($find_contact_status, $find_contact_details) = $this->tryFindContact($phone, $auth);

        if (!$find_contact_status) {
            return array(false, array(
                'error_code' => $find_contact_details['error_code'],
                'error_msg' => $find_contact_details['error_msg']
            ));
        }

        /**
         * @var waContact $contact
         */
        $contact = $find_contact_details['contact'];

        // Check ID of recipient and ID of found by login (phone) contact
        $last_send_details = $this->getLastSendDetails();
        if ($contact->getId() != $last_send_details['contact_id']) {

            $diagnostic_message = "ID of recipient contact is it not the same as ID of contact found by login.\n%s";
            $this->logError(
                sprintf($diagnostic_message, $channel->getDiagnostic()),
                array('line' => __LINE__, 'file' => __FILE__)
            );

            $msg = _ws('Incorrect or expired confirmation code. Try again or request a new code.');
            return array(false, array(
                'error_code' => waVerificationChannelSMS::VERIFY_ERROR_INVALID,
                'error_msg' => $msg
            ));
        }

        $validation_result = $channel->validateRecoveryPasswordSecret($code, array(
            'check_tries' => array(
                'count' => $this->auth_config->getVerifyCodeTriesCount(),
                'clean' => true
            )
        ));

        // Validation is ok
        if ($validation_result['status']) {
            return array(true, null);
        }

        if ($validation_result['details']['error'] === waVerificationChannel::VERIFY_ERROR_OUT_OF_TRIES) {
            $msg = _ws('You have run out of available attempts. Please request a new code.');
        } else {
            $msg = _ws('Incorrect or expired confirmation code. Try again or request a new code.');
        }

        return array(false, array(
            'error_code' => $validation_result['details']['error'],
            'error_msg' => $msg,
        ));
    }

    /**
     * Validate hash and return proper contact and address (login)
     * Hash it is secret that grant access to page (temporary)
     * @param string $hash
     * @return array
     *   - 0 bool <status>
     *   - 1 array <details>
     * @throws waException
     */
    protected function validateHash($hash)
    {
        // Last details save in session - of course details may be empty.
        // If empty - make a conclusion that we deal with TYPE_EMAIL (cause hash from link and session is brand new)
        $send_details = $this->getLastSendDetails();

        // channel type can be empty - see previous comment
        // so if empty OR TYPE_EMAIL - it is TYPE_EMAIL
        // if TYPE_SMS - it is TYPE_SMS
        $send_details['channel_type'] = ifset($send_details['channel_type']);

        if (empty($send_details['channel_type']) || $send_details['channel_type'] === waVerificationChannelModel::TYPE_EMAIL) {
            $channel = $this->auth_config->getEmailVerificationChannelInstance();
        } elseif ($send_details['channel_type'] === waVerificationChannelModel::TYPE_SMS) {
            $channel = $this->auth_config->getSMSVerificationChannelInstance();
        } else {
            // I don't now what the hell is this -- print diagnostic
            $this->logError(
                sprintf("Validate hash failed. Get unknown verification channel of type %s", $send_details['channel_type']),
                array('line' => __LINE__, 'file' => __FILE__)
            );

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
            $options = array(
                'recipient' => array(
                    'id' => $send_details['contact_id'],
                    'address' => $send_details['address'],
                )
            );
        }

        $validation_result = $channel->validateRecoveryPasswordSecret($secret, $options);

        // Validation is failed
        if (!$validation_result['status']) {
            return array(false, array());
        }

        // Has login in session - try find contact by login
        if (!empty($send_details['login'])) {

            list($find_contact_status, $find_contact_details) = $this->tryFindContact($send_details['login'], wa()->getAuth());

            // Not found - maybe it was deleted during the recover password process?
            if (!$find_contact_status) {
                $this->logError(
                    "Validate hash failed. There is no contact associated with login that was input - contact was deleted maybe during password restoring",
                    array('line' => __LINE__, 'file' => __FILE__)
                );
                return array(false, array());
            }

            /**
             * @var waContact $contact
             */
            $contact = $find_contact_details['contact'];

            // With current validation process must be bind certain contact id
            if ($validation_result['details']['contact_id'] != $contact->getId()) {
                $this->logError(
                    "Validate hash failed. There is no contact associated with that hash - contact not exists or hash is invalid",
                    array('line' => __LINE__, 'file' => __FILE__)
                );
                return array(false, array());
            }

        } else {

            // We has not login in session, this case works only for recover by link
            if ($channel->getType() === waVerificationChannelModel::TYPE_SMS) {
                $this->logError(
                    "Missed login (phone) in session, password recovery stops",
                    array('line' => __LINE__, 'file' => __FILE__)
                );
                return array(false, array());
            }

            // With current validation process must be bind certain contact id
            $contact_id = $validation_result['details']['contact_id'];
            $contact = new waContact($contact_id);

            // Contact doesn't exist or not have been bind with validation process
            if (!$contact->exists()) {
                $this->logError(
                    "Validate hash failed. There is no contact associated with that hash - contact not exists or hash is invalid",
                    array('line' => __LINE__, 'file' => __FILE__)
                );
                return array(false, array());
            }

            // Ok we have address
            $validated_address = $validation_result['details']['address'];

            // Check existing email and its binding with contact
            $cem = new waContactEmailsModel();
            $email_row = $cem->getByField(array(
                'contact_id' => $contact->getId(),
                'email' => $validated_address
            ));

            // Email has been deleted from this contact
            if (!$email_row) {
                $this->logError(
                    sprintf("Validate hash failed. Email row doesn't exist in DB"),
                    array('line' => __LINE__, 'file' => __FILE__)
                );
                return array(false, array());
            }
        }

        // set contact locale
        if ($contact['locale']) {
            wa()->setLocale($contact['locale']);
            waLocale::loadByDomain('webasyst', wa()->getLocale());
        }

        // Ok we have address
        $validated_address = $validation_result['details']['address'];

        // return success status with some details of result
        $details = array(
            'contact' => $contact,
            'address' => $validated_address,
            'channel_type' => $channel->getType()
        );

        // phone must be as saved in contact, cause we will show it input
        // if in validated phone with 7 and in contact is with 8 we need with 8
        if ($channel->isSMS()) {
            $details['address'] = $contact->get('phone', 'default');
        }

        return array(true, $details);
    }

    /**
     * Invalidate (remove) secret hash, that grants access
     * @param $hash
     */
    protected function invalidateHash($hash)
    {
        // Last details save in session - of course details may be empty.
        // If empty - make a conclusion that we deal with TYPE_EMAIL (cause hash from link and session is brand new)
        $send_details = $this->getLastSendDetails();

        // channel type can be empty - see previous comment
        // so if empty OR TYPE_EMAIL - it is TYPE_EMAIL
        // if TYPE_SMS - it is TYPE_SMS
        $send_details['channel_type'] = ifset($send_details['channel_type']);

        if (empty($send_details['channel_type']) || $send_details['channel_type'] === waVerificationChannelModel::TYPE_EMAIL) {
            $channel = $this->auth_config->getEmailVerificationChannelInstance();
        } elseif ($send_details['channel_type'] === waVerificationChannelModel::TYPE_SMS) {
            $channel = $this->auth_config->getSMSVerificationChannelInstance();
        } else {
            // I don't now what the hell is this -- doesn't matter anyway ^)
            return;
        }

        // Define Secret
        if ($channel->getType() === waVerificationChannelModel::TYPE_SMS) {
            $secret = $this->extractCodeFromHash($hash);
        } else {
            $secret = $hash;
        }

        // invalidate secret
        $channel->invalidateRecoveryPasswordSecret($secret);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return _ws('Password recovery');
    }

    /**
     * Prepare input post data - typecast field values, filter off excess fields to prevent malicious, and etc
     *
     * IMPORTANT: This method MUST return ready and secure (cleaned) data
     *
     * @param $data
     * @return mixed
     */
    protected function prepareData($data)
    {
        $clean_data = array();
        if ($this->isSetPasswordMode()) {
            $clean_data['password'] = $this->getScalarValue('password', $data);
            $clean_data['password_confirm'] = $this->getScalarValue('password_confirm', $data);
        } else {
            $clean_data['login'] = trim($this->getScalarValue('login', $data));
            if (isset($data['confirmation_code'])) {
                $clean_data['confirmation_code'] = trim($this->getScalarValue('confirmation_code', $data));
            }
        }
        return $clean_data;
    }

    /**
     * Save details of last sending in session - will be get soon
     * Actually need only for TYPE_SMS channel
     * @param $details
     */
    protected function saveLastSendDetails($details)
    {
        $key = 'wa/forgotpassword/send_details/';
        $this->getStorage()->set($key, $details);
    }

    /**
     * Get details of last sending in session
     * Actually need only for TYPE_SMS channel
     * @return mixed
     */
    protected function getLastSendDetails()
    {
        $key = 'wa/forgotpassword/send_details/';
        return $this->getStorage()->get($key);
    }

    /**
     * Delete details of last sending in session
     */
    protected function delLastSendDetails()
    {
        $key = 'wa/forgotpassword/sent_details/';
        $this->getStorage()->del($key);
    }

    /***
     * @param $code
     * @return string
     */
    protected function generateHashByCode($code)
    {
        $salt = 'xxuuw:dswr4$h5t392n1jlkdfa/.`w';
        $unique_id = uniqid($code.time().$salt.mt_rand().mt_rand().mt_rand(), true);
        $hash = md5($unique_id);
        $hash = substr($hash, 0, 16) . $code . substr($hash, 16);
        return $hash;
    }

    /**
     * Extract confirmation code from hash. Need for TYPE_SMS channel
     * @param $hash
     * @return bool|string
     */
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

    /**
     * Some after execute deals
     */
    protected function afterExecute()
    {
        parent::afterExecute();
        $this->saveLastResponse();
    }

    /**
     * Some before redirect deals
     * @param array $params
     * @param null $code
     */
    protected function beforeRedirect($params = array(), $code = null)
    {
        $this->saveLastResponse();
    }

    /**
     * @see waLoginModuleController
     * @return string
     */
    protected function getLoginUrl()
    {
        return $this->auth_config->getLoginUrl();
    }
}
