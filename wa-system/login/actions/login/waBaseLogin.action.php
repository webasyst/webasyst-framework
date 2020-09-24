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

    /**
     * waBaseLoginAction constructor.
     * @param null $params
     * @throws waException
     */
    public function __construct($params = null)
    {
        parent::__construct($params);
        if ($this->env === null) {
            $this->env = wa()->getEnv();
        }
    }

    /**
     * Entry point of action
     * @throws waAuthException
     * @throws waException
     */
    public function execute()
    {
        wa()->getResponse()->setTitle(_w('Login'));

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

    /**
     * Validate login. Login can be one of these: email, phone or wa_contact.login
     * @param $login
     * @return string|null
     * @throws waException
     */
    protected function validateLogin($login)
    {
        $login = is_scalar($login) ? (string)$login : '';
        if (strlen($login) <= 0) {
            return _ws('Login is required');
        } else {
            return null;
        }
    }

    /**
     * Validate password
     * @param string $password
     * @return string|null
     * @throws waException
     */
    protected function validatePassword($password)
    {
        $password = is_scalar($password) ? (string)$password : '';
        if (strlen($password) <= 0) {
            return _ws('Password is required');
        } elseif (strlen($password) > waAuth::PASSWORD_MAX_LENGTH) {
            return _ws('Specified password is too long.');
        } else {
            return null;
        }
    }

    /**
     * Validate captcha
     * @return string|null
     * @throws waException
     */
    protected function validateCaptcha()
    {
        if (!$this->auth_config->needLoginCaptcha()) {
            return null;
        }
        $captcha_options = [];
        if ($this->auth_config instanceof waDomainAuthConfig) {
            $captcha_options['app_id'] = $this->auth_config->getApp();
        }
        if (!wa()->getCaptcha($captcha_options)->isValid()) {
            return _ws('Invalid captcha');
        } else {
            return null;
        }
    }

    /**
     * Validate confirmation code
     * @param string $code
     * @param string|int $phone
     * @return array
     *  - 0 - bool status
     *  - 1 - array details
     *      If status is FALSE, details has keys
     *        - string|null 'error_code' some string ID of error, that will be send to client as a controller response
     *        - string      'error_msg'  message about error
     * @throws waException
     */
    protected function validateCode($code, $phone)
    {
        if (!$this->auth_config->getSignupConfirm()) {
            return array(false, array(
                'error_code' => waVerificationChannelSMS::VERIFY_ERROR_INVALID,
                'error_msg' => _ws('Incorrect or expired confirmation code. Try again or request a new code.'),
            ));
        }

        if (!$code) {
            return array(false, array(
                'error_code' => waVerificationChannelSMS::VERIFY_ERROR_INVALID,
                'error_msg' => _ws('Enter a confirmation code to complete signup'),
            ));
        }

        if (!$this->isValidPhoneNumber($phone)) {
            return array(false, array(
                'error_code' => waVerificationChannelSMS::VERIFY_ERROR_INVALID,
                'error_msg' => _ws('Incorrect phone number value'),
            ));
        }

        $channel = $this->auth_config->getSMSVerificationChannelInstance();

        //
        $is_international = substr($phone, 0, 1) === '+';

        // phone was transformed while sent sms
        $phone_transformed = $this->wasPhoneTransformedForSMS();

        // input phone is not international and was transformed while sent sms means in DB we has transformed phone, so validation must be on transformed phone
        if (!$is_international && $phone_transformed) {
            $transformation_result = $this->auth_config->transformPhone($phone);
            $phone = $transformation_result['phone'];
        }

        // validate code itself
        $result = $channel->validateSignUpConfirmation($code, array(
            'recipient' => $phone,
            'check_tries' => array(
                'count' => $this->auth_config->getVerifyCodeTriesCount(),
                'clean' => true
            )
        ));

        if ($result['status']) {
            return array(true, null);   // no error, successful verification
        }

        if ($result['details']['error'] === waVerificationChannel::VERIFY_ERROR_OUT_OF_TRIES) {
            $msg = _ws('You have run out of available attempts. Please request a new code.');
        } else {
            $msg = _ws('Incorrect or expired confirmation code. Try again or request a new code.');
        }

        return array(false, array(
            'error_code' => $result['details']['error'],
            'error_msg' => $msg,
        ));
    }

    /**
     * Validate input data
     * @param $data
     * @param null|array $fields What fields to validate. Null is all input fields
     * @return array
     * @throws waException
     */
    protected function validate($data, $fields = null)
    {
        $fields = is_array($fields) || is_scalar($fields) ? (array)$fields : array(
            'login', 'password', 'captcha', 'confirmation_code'
        );

        $errors = array();
        foreach ($fields as $field_id) {
            if ($field_id === 'login') {
                $error = $this->validateLogin($data['login']);
                if ($error) {
                    $errors[$field_id] = $error;
                }
            } elseif ($field_id === 'password') {
                $error = $this->validatePassword($data['password']);
                if ($error) {
                    $errors[$field_id] = $error;
                }
            } elseif ($field_id === 'captcha') {
                $error = $this->validateCaptcha();
                if ($error) {
                    $errors[$field_id] = $error;
                }
            } elseif ($field_id === 'confirmation_code' && isset($data['confirmation_code'])) {
                list($valid, $details) = $this->validateCode($data['confirmation_code'], $data['login']);
                if (!$valid) {
                    $errors[$field_id][$details['error_code']] = $details['error_msg'];
                }
            }
        }

        return $errors;
    }

    /**
     * Main auth business logic of auth
     * @return bool
     * @throws waAuthException
     * @throws waException
     */
    protected function tryAuth()
    {
        $data = $this->getData();
        $errors = $this->validate($data);

        // there are some validate errors
        if ($errors) {
            $this->assign('errors', $errors);
            return false;
        }

        // auth provider
        $auth = wa()->getAuth();

        // Confirmation code posted and in validation step already validated, so update phone status
        if (isset($data['confirmation_code'])) {
            $contact_info = $auth->getByLogin($data['login'], waAuth::LOGIN_FIELD_PHONE);

            if ($contact_info) {

                // non-international phone try to convert to international
                $phone = $data['login'];
                $is_international = substr($phone, 0, 1) === '+';
                if (!$is_international) {
                    $result = $this->auth_config->transformPhone($phone);
                    $phone = $result['phone'];
                }

                $dm = new waContactDataModel();
                $status_updated = $dm->updateContactPhoneStatus($contact_info['id'], $phone, waContactDataModel::STATUS_CONFIRMED);
                if (!$status_updated) {
                    // rollback to phone before transformation
                    $dm->updateContactPhoneStatus($contact_info['id'], $data['login'], waContactDataModel::STATUS_CONFIRMED);
                }

            }
        }

        // Run actual auth logic in system through auth provider
        // Auth provider throws different type of exception - need to process every of it
        // in $errors collect auth errors and than send they to client
        $errors = array();

        try {

            // just in case filter-off 'id' from input data, although prepareData MUST be always prepare secure data
            if (array_key_exists('id', $data)) {
                unset($data['id']);
            }

            // actual auth in system through auth provider
            if ($auth->auth($data)) {
                $this->logAction('login', $this->env);
                $this->afterAuth();
            } else {
                // almost never happens -- may be contact is not registered
                $errors['auth'] = _ws("Invalid login name or password.");
                // diagnostic print
                $this->logError(
                    "Almost never happens -- may be contact is not registered or exist",
                    array('line' => __LINE__, 'file' => __FILE__)
                );
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

                $phone = waContactPhoneField::cleanPhoneNumber($data['login']);
                $is_international = substr($phone, 0, 1) === '+';
                $phone_transformed = false;

                $channel = $this->auth_config->getSMSVerificationChannelInstance();

                $sent = $channel->sendSignUpConfirmationMessage($phone, array(
                    'use_session' => true
                ));

                // Not sent, maybe because of sms adapter not work correct with not international phones
                if (!$sent && !$is_international) {
                    // If not international phone number - transform 8 to code (country prefix)
                    $transform_result = $this->auth_config->transformPhone($phone);
                    if ($transform_result['status']) {
                        $phone_transformed = true;
                        $phone = $transform_result['phone'];
                        $sent = $channel->sendSignUpConfirmationMessage($phone, array(
                            'use_session' => true
                        ));
                    }
                }

                if (!$sent) {
                    $errors['auth'] = "Sorry, we cannot sent confirmation code. Please refer to your system administrator.";
                } else {

                    $this->markPhoneWasTransformedForSMS($phone_transformed);

                    $phone_formatted = $phone;
                    $phone_field = waContactFields::get('phone');
                    if ($phone_field) {
                        $phone_formatted = $phone_field->format($phone_formatted, 'value');
                    }

                    $msg = _ws('An SMS message has been sent to phone number <strong>%s</strong> for you to confirm signup.');
                    $msg = sprintf($msg, $phone_formatted);

                    $this->assign(array(
                        'code_sent' => true,
                        'code_sent_message' => $msg,
                        'code_sent_timeout_message' => $this->auth_config->getConfirmationCodeTimeoutMessage(),
                        'code_sent_timeout' => $this->auth_config->getConfirmationCodeTimeout()
                    ));
                }

            }

        } catch (waAuthInvalidCredentialsException $e) {
            if ($this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD) {
                $errors['password'] = _ws('Incorrect or expired one-time password. Try again or request a new one-time password.');
            } else {
                $errors['auth'] = $e->getMessage();
            }
        } catch (waAuthRunOutOfTriesException $e) {
            $errors['password'][waVerificationChannel::VERIFY_ERROR_OUT_OF_TRIES] = $e->getMessage();
        } catch (waException $e) {
            $errors['auth'] = $e->getMessage();
        }

        // errors occur - log fail try from backend user in system wa_log
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

        // send errors to client
        $this->assign('errors', $errors);

        return !$errors;
    }

    /**
     * Timeout checker for confirmation code
     * Need to prevent to ofter request of confirmation code
     * See usage of method for details
     * @return bool
     * @throws waException
     */
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

    /**
     * Mark phone was transformed due the sending of sms with confirmation_code
     * @param bool $transformed
     * @throws waException
     */
    protected function markPhoneWasTransformedForSMS($transformed)
    {
        $key = 'wa/login/sent_code/phone_was_transformed/';
        wa()->getStorage()->set($key, (bool)$transformed);
    }

    /**
     * Was phone transformed due the sending sms with confirmation_code
     * @return bool
     * @throws waException
     */
    protected function wasPhoneTransformedForSMS()
    {
        $key = 'wa/login/sent_code/phone_was_transformed/';
        return (bool)wa()->getStorage()->get($key);
    }

    /**
     * Backward compatibility -- send into template options of auth provider
     * @throws waException
     */
    protected function afterExecute()
    {
        parent::afterExecute();
        $auth = wa()->getAuth();
        if (!$this->isJsonMode()) {
            $this->view->assign('options', $auth->getOptions());
        }
    }

    /**
     * Prepare input post data - typecast field values, filter off excess fields to prevent malicious, and etc
     *
     * IMPORTANT: This method MUST return ready and secure (cleaned) data, because
     * this data will be pass straight to wa()->getAuth()->auth()
     *
     * @param $data
     * @return array
     */
    protected function prepareData($data)
    {
        $data = is_array($data) ? $data : array();
        $clean_data = array(
            'login'    => trim($this->getScalarValue('login', $data)),
            'password' => $this->getScalarValue('password', $data),
            'captcha'  => $this->getScalarValue('captcha', $data),
            'remember' => !empty($data['remember'])
        );

        // 'confirmation_code' will be here if confirmation of phone is required and phone is not confirmed yet
        if (isset($data['confirmation_code'])) {
            $clean_data['confirmation_code'] = $this->getScalarValue('confirmation_code', $data);
        }
        return $clean_data;
    }

    /**
     * Try send onetime password and assign details of result
     * @return bool
     * @throws waAuthException
     * @throws waException
     */
    protected function trySendOnetimePassword()
    {
        // diagnostic already printed inside
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
     * @throws waAuthException
     * @throws waException
     */
    protected function sendOnetimePassword()
    {
        // ensure that it is onetime password mode
        $auth_config = $this->auth_config;
        if ($auth_config->getAuthType() !== waDomainAuthConfig::AUTH_TYPE_ONETIME_PASSWORD) {
            return array(false, array());
        }

        $data = $this->getData();

        // validate input data
        $errors = $this->validate($data, array('login', 'captcha'));
        if ($errors) {
            return array(false, $errors);
        }

        // try find contact by login
        $contact = null;
        $user_info = wa()->getAuth()->getByLogin($data['login']);
        if ($user_info) {
            $contact = new waContact($user_info['id']);
        }

        // contact does not exists - error
        if (!$contact || !$contact->exists()) {
            $signup_url = $auth_config->getSignUpUrl();
            if ($this->env === 'backend') {
                $error = _ws('User does not exist.');
            } else {
                $error = sprintf(_ws('Contact does not exist. <a href="%s">Sign up</a> first.'), $signup_url);
            }
            return array(false, array('login' => $error));
        }

        // check timeout message
        if (!$this->isTimeoutPassed()) {
            $errors = array(
                'timeout' => array(
                    'message' => $this->auth_config->getOnetimePasswordTimeoutErrorMessage(),
                    'timeout' => $this->auth_config->getOnetimePasswordTimeout()
                )
            );
            return array(false, $errors);
        }

        // by login define what channel will be looked first
        $priority = $this->getChannelPriorityByLogin($data['login']);

        // get auth verification channels
        $channels = $auth_config->getVerificationChannelInstances($priority);

        // result of looking through the channels
        $asset_id = null;
        $channel_type = null;

        // phone was transformed during the sms sending
        $phone_transformed = false;

        $recipient = array(
            'id' => $contact->getId(),
            'phone' => $contact->get('phone', 'default'),
            'email' => $contact->get('email', 'default')
        );
        $recipient['phone'] = waContactPhoneField::cleanPhoneNumber($recipient['phone']);

        $send_options = array(
            'site_url' => $this->auth_config->getSiteUrl(),
            'site_name' => $this->auth_config->getSiteName(),
            'login_url' => $this->auth_config->getLoginUrl(array(), true),
        );

        // look through channels and try send onetime password, first success - break loop
        foreach ($channels as $channel) {

            $asset_id = $channel->sendOnetimePasswordMessage($recipient, $send_options);

            if ($asset_id > 0) {
                $channel_type = $channel->getType();
                break;
            }

            // fail on sending SMS, try convert from local phone prefix to international phone prefix (for Russia is 8 => 7)
            if ($channel->isSMS() && !empty($recipient['phone'])) {
                $transformation_result = $this->auth_config->transformPhone($recipient['phone']);
                if ($transformation_result['status']) {

                    // actually converted
                    $recipient['phone'] = $transformation_result['phone'];

                    // try send again
                    $asset_id = $channel->sendOnetimePasswordMessage($recipient, $send_options);

                    // successful sms sending
                    if ($asset_id > 0) {
                        $phone_transformed = true;
                        $channel_type = $channel->getType();
                        break;
                    }
                }
            }

            // diagnostic log prints
            if ($channel->isEmail()) {
                $diagnostic_message = "Couldn't send email message with onetime password. Check email settings\n%s";
                $this->logError(
                    sprintf($diagnostic_message, $channel->getDiagnostic()),
                    array('line' => __LINE__, 'file' => __FILE__)
                );
            } elseif ($channel->isSMS()) {
                $diagnostic_message = "Couldn't send SMS with onetime password. Explore sms.log for details\n%s";
                $this->logError(
                    sprintf($diagnostic_message, $channel->getDiagnostic()),
                    array('line' => __LINE__, 'file' => __FILE__)
                );
            } else {
                $diagnostic_message = "Couldn't send message with onetime password.\n%s";
                $this->logError(
                    sprintf($diagnostic_message, $channel->getDiagnostic()),
                    array('line' => __LINE__, 'file' => __FILE__)
                );
            }

        }

        // Looks like all channels failed - try log diagnostic information for admin
        if (!$asset_id) {

            // Looks like all channels failed
            $this->logError(
                sprintf("Couldn't send message with onetime password.\nLooks like there is no any working channel in system. Check auth settings for this env=%s and site=%s",
                    $this->env, $this->auth_config->getSiteUrl()),
                array('line' => __LINE__, 'file' => __FILE__)
            );

            return array(false, array());
        }

        // remember onetime password asset for this user (not in session cause we will validate onetime password in waAuth class)
        $csm = new waContactSettingsModel();
        $csm->set($user_info['id'], 'webasyst', 'onetime_password_id', $asset_id);
        $csm->set($user_info['id'], 'webasyst', 'onetime_password_phone_transformed', $phone_transformed);

        // prepare message for user
        if ($channel_type == waVerificationChannelModel::TYPE_EMAIL) {
            $sent_message = _ws('One-time password has been sent to your email address.');
        } elseif ($channel_type == waVerificationChannelModel::TYPE_SMS) {
            $sent_message = _ws('One-time password has been sent to you as an SMS.');
        } else {
            $sent_message = _ws('One-time password has been sent.');
        }

        // prepare details array and return it with success status
        $details = array(
            'used_channel_type' => $channel_type,
            'onetime_password_sent_message' => $sent_message,
            'onetime_password_timeout_message' => $this->auth_config->getOnetimePasswordTimeoutMessage(),
            'onetime_password_timeout' => $this->auth_config->getOnetimePasswordTimeout()
        );
        return array(true, $details);
    }

    /**
     * Timeout checker for onetime password
     * Need to prevent to ofter request of onetime password
     * See usage of method for details
     * @return bool
     * @throws waException
     */
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

    /**
     * Check auth config and if check is failed throw exception
     * Logging in will be stop by that exception
     * @return mixed
     * @throws waException
     */
    abstract protected function checkAuthConfig();

    /**
     * Save referrer, to redirect there after logging in (in afterAuth() method)
     * @see afterAuth
     * @return mixed
     */
    abstract protected function saveReferer();

    /**
     * Some Voodoo magic xml-http checker - see details inside
     */
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

    /**
     * @see waLoginModuleController
     * @return string
     */
    protected function getLoginUrl()
    {
        return $this->auth_config->getLoginUrl();
    }
}
