<?php

class waSignupAction extends waViewAction
{
    const SIGNED_UP_STATUS_OK = 'ok';
    const SIGNED_UP_STATUS_FAILED = 'failed';
    const SIGNED_UP_STATUS_IN_PROCESS = 'in_process';

    protected $namespace = 'data';
    /**
     * @var waAuthConfig
     */
    protected $auth_config;
    protected $generated_password;
    protected $response = array();

    /**
     * @var bool
     */
    private $is_json_mode;

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->auth_config = waDomainAuthConfig::factory();
    }

    public function execute()
    {
        if ($this->isEndPointMessageAction()) {
            return $this->executeEndPointMessageAction();
        }

        if ($this->isSendConfirmationAction()) {
            return $this->executeSendConfirmationAction();
        }

        $confirm_hash = (string)$this->getGetParam('confirm');

        // if authorized => send to app_url
        if (wa()->getAuth()->isAuth() && !$confirm_hash) {
            if ($this->needRedirects()) {
                $this->redirectToAppPage();
            }
            return;
        }


        // check auth enabled
        if (!$this->auth_config->getAuth()) {
            $this->notFound();
        }

        // need save referrer to redirect back here after all
        $this->saveReferer();

        // check signup url
        $signup_url = $this->auth_config->getSignUpUrl();
        if (urldecode(wa()->getConfig()->getRequestUrl(false, true)) != $signup_url) {
            $this->redirectToSignupPage();
            return;
        }

        if ($this->isPost()) {
            return $this->executeSignupAction($this->getData());
        }

        if ($confirm_hash) {
            $this->executeConfirmEmailAction($confirm_hash);
        }
    }

    protected function getGetParams()
    {
        $get = wa()->getRequest()->get();
        return !empty($get) && is_array($get) ? $get : array();
    }

    protected function getGetParam($name)
    {
        $params = $this->getGetParams();
        return ifset($params[$name]);
    }

    protected function isPost()
    {
        return wa()->getRequest()->method() === 'post';
    }

    /**
     * Flag for check need we execute proper action
     * @see executeEndPointMessageAction
     * @return bool
     */
    protected function isEndPointMessageAction()
    {
        return $this->getGetParam('signed_up') || $this->getGetParam('confirmation_link_sent') ||
            $this->getGetParam('email_confirmed');
    }

    /**
     * This action is end-point action when we just send info vars
     * Need mostly for backward compatibility when old themes
     * They need consume proper named vars to act correctly
     */
    protected function executeEndPointMessageAction()
    {
        $get = wa()->getRequest()->get();
        $get = !empty($get) && is_array($get) ? $get : array();

        $signed_up = ifset($get['signed_up']);
        $confirmation_link_sent = ifset($get['confirmation_link_sent']);
        $email_confirmed = ifset($get['email_confirmed']);

        $this->assign(array(

            // name is saved for backward compatibility with old themes
            //'contact' => $signed_up && wa()->getAuth()->isAuth() ? wa()->getUser() : null,

            // name is saved for backward compatibility with old themes
            //'email_confirmation_hash' => $confirmation_link_sent,

            // name is saved for backward compatibility with old themes
            //'confirmed_email' => $email_confirmed,

            //
            'email_confirmed' => $email_confirmed
        ));

    }

    /**
     * Flag for check need we execute proper action
     * @see executeSendConfirmationAction
     * @return bool
     */
    protected function isSendConfirmationAction()
    {
        return $this->getGetParam('send_confirmation');
    }

    /**
     * This action IS JUST for case when client in login page and can't logging in because email hasn't be confirmed
     * @throws waException
     */
    protected function executeSendConfirmationAction()
    {
        $auth = wa()->getAuth();

        $user_info = null;
        if ($this->getRequest()->request('login')) {
            $login = $this->getRequest()->request('login');
            $login = is_scalar($login) ? (string)$login : '';
            if (strlen($login) > 0) {
                $user_info = $auth->getByLogin($login);
            }
        }

        if ($user_info) {
            if ($this->sendLink(new waContact($user_info['id']))) {
                echo _ws('Confirmation link has been resent.');
            } else {
                echo _ws('Error');
            }
        } else {
            echo _ws('Invalid login');
        }

        exit;
    }

    /**
     * Action for email confirmation
     * @param $confirm_hash
     * @throws waException
     */
    protected function executeConfirmEmailAction($confirm_hash)
    {
        // already auth contact
        if ($this->getUser()->isAuth()) {
            $this->redirectToAppPage();
            return;
        }

        $validation_result = $this->validateConfirmationHash($confirm_hash);

        // Validation is failed
        if (!$validation_result['status']) {
            $this->redirectToLoginPage();
            return;
        }

        // With current validation process must be bind certain contact
        $contact_id = $validation_result['details']['contact_id'];
        $contact = new waContact($contact_id);

        // Contact doesn't exist or not have been bind with validation process
        if (!$contact->exists()) {
            $this->redirectToLoginPage();
            return;
        }

        // Ok, we have email - mark it as confirmed
        $validated_email = $validation_result['details']['address'];

        $cem = new waContactEmailsModel();
        $email_row = $cem->getByField(array(
            'contact_id' => $contact->getId(),
            'email' => $validated_email
        ));

        // Email has been deleted from this contact
        if (!$email_row) {
            $this->redirectToLoginPage();
            return;
        }

        // Email is now confirmed
        $cem->updateById($email_row['id'], array('status' => waContactEmailsModel::STATUS_CONFIRMED));

        // For some reasons can't signup contact
        if (!$this->trySignupContact($contact)) {
            $this->redirectToLoginPage();
            return;
        }

        // send sign up notification
        if ($this->auth_config->getSignUpNotify()) {
            $addresses = array(
                'email' => $validated_email,
                'phone' => $contact->get('phone', 'default')
            );
            $this->sendSignupNotify($addresses);
        }

        $this->redirectToEmailConfirmedPage();

    }

    protected function assign($name, $value = null)
    {
        if (is_scalar($name)) {
            $this->response[$name] = $value;
        } elseif (is_array($name)) {
            $this->response = array_merge($this->response, $name);
        }
    }

    protected function afterExecute()
    {
        if (!$this->isJsonMode()) {
            wa()->getResponse()->setTitle(_ws('Sign up'));
            if (!isset($this->response['errors'])) {
                $this->response['errors'] = array();
            }
            $this->view->assign($this->response);
        }

        $this->setSignupLastResponse();
    }

    /**
     * Way to tell waSignupForm (and waLoginForm) about what had happen in here
     */
    protected function setSignupLastResponse()
    {
        // Way to tell waSignupForm (and waLoginForm) about what had happen in here
        wa()->getStorage()->set('wa/signup/last_response', $this->response);
    }

    public function display($clear_assign = true)
    {
        if (!$this->isJsonMode()) {
            return parent::display($clear_assign);
        }

        $this->preExecute();
        $this->execute();
        $this->afterExecute();

        $errors = array();
        if (isset($this->response['errors']) && is_array($this->response['errors'])) {
            $errors = $this->response['errors'];
            unset($this->response['errors']);
        }


        if ($errors) {
            $this->sendJson($errors, false);
        } else {

            // We can't send waContact object by json, just array, so extract info from waContact
            if (isset($this->response['contact']) && $this->response['contact'] instanceof waContact) {
                /**
                 * @var waContact $contact
                 */
                $contact = $this->response['contact'];
                $this->response['contact'] = array(
                    'id' => $contact->getId(),
                    'name' => waContactNameField::formatName($contact),
                    'firstname' => $contact['firstname'],
                    'lastname' => $contact['lastname'],
                    'middlename' => $contact['middlename'],
                    'userpic_20' => $contact->getPhoto(20)
                );
            }

            $this->sendJson($this->response);
        }
    }

    protected function isJsonMode()
    {
        if ($this->is_json_mode !== null) {
            return !!$this->is_json_mode;
        }
        $is_json_mode = $this->getRequest()->request('wa_json_mode');
        $is_ajax = waRequest::isXMLHttpRequest();
        $this->is_json_mode = $is_ajax && $is_json_mode;
        return $this->is_json_mode;
    }

    public function redirect($params = array(), $code = null)
    {
        if (!$this->isJsonMode()) {
            return parent::redirect($params, $code);
        } else {
            $this->setSignupLastResponse();
            $url = $this->unpackRedirectParams($params);
            $response = $this->response;
            $response['redirect_url'] = $url;
            $response['redirect_code'] = $code;
            return $this->sendJson($response);
        }
    }

    /**
     * Save referrer, to redirect there after signing in
     * @return mixed
     */
    protected function saveReferer()
    {
        if (!waRequest::param('secure')) {
            $referer = waRequest::server('HTTP_REFERER');
            $referer = is_string($referer) ? $referer : '';

            $root_url = wa()->getRootUrl(true);
            if ($root_url != substr($referer, 0, strlen($root_url))) {
                $this->getStorage()->del('auth_referer');
                return;
            }

            $referer = substr($referer, strlen($this->getConfig()->getHostUrl()));

            $ignore_urls = array(
                $this->auth_config->getSignUpUrl(),
                $this->auth_config->getForgotPasswordUrl(),
                $this->auth_config->getLoginUrl(),
            );

            foreach ($ignore_urls as $ignore_url) {

                $ignore_url = is_string($ignore_url) ? $ignore_url : '';

                // if referer "looks like" ignorable url
                if (!is_null($referer) && (strpos($referer, $ignore_url) !== false || strpos($ignore_url, $referer) !== false)) {
                    // Suck url not consider as referer
                    $referer = null;
                }
            }

            if ($referer) {
                $this->getStorage()->set('auth_referer', $referer);
            }
        }
    }

    /**
     * @return string
     */
    protected function getGeneratePassword()
    {
        if (!$this->generated_password) {
            $this->generated_password = waContact::generatePassword();
        }
        return $this->generated_password;
    }

    /**
     * Send notification about successful signing up to first working address in list
     *
     * IMPORTANT: Send also generated password in proper mode ( waAuthConfig::AUTH_TYPE_GENERATE_PASSWORD )
     *
     * @param $addresses array of addresses where we can sent notification indexed by field id ('email', 'phone')
     *
     *
     * @param null|string $priority waVerificationChannelModel::TYPE_* const
     * @return array(0 => <status>, 1 => <details>)
     * @throws waException
     */
    protected function sendSignupNotify($addresses, $priority = null)
    {
        if (!$this->auth_config->getSignUpNotify()) {
            return array(false, array());
        }

        $generated_password_auth_type = $this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_GENERATE_PASSWORD;

        $channels = $this->auth_config->getVerificationChannelInstances($priority);

        $sent = false;
        $used_channel_type = null;
        $used_address = null;

        foreach ($channels as $channel) {

            $address = null;

            // options for send method
            $options = array(
                'site_url' => $this->auth_config->getSiteUrl(),
                'site_name' => $this->auth_config->getSiteName(),
                'login_url' => $this->auth_config->getLoginUrl(array(), true)
            );
            if ($this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_GENERATE_PASSWORD) {
                $options['password'] = $this->getGeneratePassword();
            }

            if ($channel->isEmail() && !empty($addresses['email'])) {
                $address = $addresses['email'];
                $sent = $channel->sendSignUpSuccessNotification($address, $options);
            } elseif ($channel->isSMS() && !empty($addresses['phone'])) {

                $phone = $addresses['phone'];
                $is_international = substr($phone, 0, 1) === '+';

                $sent = $channel->sendSignUpSuccessNotification($phone, $options);

                // Not sent, maybe because of sms adapter not work correct with not international phones
                if (!$sent && !$is_international) {
                    // If not international phone number - transform 8 to code (country prefix)
                    $transform_result = $this->auth_config->transformPhone($phone);
                    if ($transform_result['status']) {
                        $phone = $transform_result['phone'];
                        $sent = $channel->sendSignUpSuccessNotification($phone, $options);
                    }
                }

                $address = $phone;
            }

            if (!$address) {
                continue;
            }

            // successful send
            if ($sent) {
                $used_address = $address;
                $used_channel_type = $channel->getType();
                break;
            }

            // print diagnostic only if generated password not sent
            if ($generated_password_auth_type) {
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
            }
        }

        if (!$sent) {
            // print diagnostic only if generated password not sent
            if ($generated_password_auth_type) {
                $this->logError(
                    sprintf("Couldn't send message with generated password.\nLooks like there is no any working channel in system. Check auth settings for this domain %s",
                        $this->auth_config->getDomain()),
                    array('line' => __LINE__, 'file' => __FILE__)
                );
            }
            return array(false, array());
        }

        $details = array(
            'used_address' => $used_address,
            'used_channel_type' => $used_channel_type,
        );

        if ($this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_GENERATE_PASSWORD) {
            if ($used_channel_type === waVerificationChannelModel::TYPE_EMAIL) {
                $msg = _ws('You have successfully signed up. Please check new mail at <strong>%s</strong>, we have sent you a message with your password.');
                $msg = sprintf($msg, $used_address);
            } else {
                $msg = _ws('You have successfully signed up. An SMS message with your password has been sent to phone number <strong>%s</strong>.');
                $msg = sprintf($msg, $used_address);
            }

            return array(true, array_merge($details, array(
                'generated_password_sent' => true,
                'generated_password_sent_message' => $msg
            )));

        }

        return array($sent, $details);
    }

    /**
     * @param $data
     * @param $details
     */
    protected function processSignupFailedStatus($data, $details)
    {
        $errors = $details ? $details : array('signup' => _ws('Signup failed.'));
        $this->assign('errors', $errors);
    }

    /**
     * @param $data
     * @param $details
     */
    protected function processSignupInProcessStatus($data, $details)
    {
        if (!empty($details['confirmation_link_sent'])) {
            $msg = _ws('Please check new mail at <strong>%s</strong>, we have sent a message for you to confirm signup.');
            $msg = sprintf($msg, $data['email']);
            $this->assign(array(
                'confirmation_link_sent' => true,
                'confirmation_link_sent_message' => $msg,
            ));

            return;
        }

        if (!empty($details['onetime_password_sent'])) {
            $details = array_merge($details, array(
                'onetime_password_sent' => true,
                'onetime_password_timeout_message' => $this->auth_config->getOnetimePasswordTimeoutMessage(),
                'onetime_password_timeout' => $this->auth_config->getOnetimePasswordTimeout()
            ));

            $this->assign($details);
            return;
        }

        if (!empty($details['confirmation_code_sent'])) {

            if (!empty($details['phone_transformed'])) {
                $this->markPhoneWasTransformedForSMS($details['phone_transformed']);
            }

            $msg = _ws('An SMS message has been sent to phone number <strong>%s</strong> for you to confirm signup.');

            $phone_formatted = waContactPhoneField::cleanPhoneNumber($data['phone']);
            $phone_field = waContactFields::get('phone');
            if ($phone_field) {
                $phone_formatted = $phone_field->format($phone_formatted, 'value');
            }

            $msg = sprintf($msg, $phone_formatted);

            $this->assign(array(
                'code_sent' => true,
                'code_sent_message' => $msg,
                'code_sent_timeout_message' => $this->auth_config->getConfirmationCodeTimeoutMessage(),
                'code_sent_timeout' => $this->auth_config->getConfirmationCodeTimeout()
            ));
            return;
        }
    }

    /**
     * @return bool
     */
    protected function needRedirects()
    {
        $request = $this->getRequest()->request();
        // NOTICE: TRUE is default
        $need_redirects = true;
        if (array_key_exists('need_redirects', $request)) {
            $need_redirects = (bool)$request['need_redirects'];
        }
        return $need_redirects;
    }


    /**
     * @param array $data
     * @param array $details
     */
    protected function processSignupOkStatus($data, $details)
    {
        // No need notify about successful singing up
        if (!$this->auth_config->getSignUpNotify()) {
            if ($this->needRedirects()) {
                $this->redirectToLastPage();
            }
            return;
        }

        // Ok, try send notification
        // Take into account priority - if there is 'confirmation_code' then priority is SMS
        $priority = null;
        if (isset($data['confirmation_code']) && !empty($data['phone'])) {
            $priority = waVerificationChannelModel::TYPE_SMS;
        }

        // diagnostic already printed inside
        list($notify_sent, $notify_details) = $this->sendSignupNotify($data, $priority);

        // IMPORTANT detail
        // Through 'assign' we inform ALSO signup & login forms
        // Login form for example need know about notification detail to show client feedback message
        $this->assign('notify_sent', $notify_sent);
        if ($notify_sent) {
            $this->assign($notify_details);
        }

        // IMPORTANT detail
        // Generated password MUST BE sent withing notification about successful signing up
        $generated_password_auth_type = $this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_GENERATE_PASSWORD;

        // If notify hasn't sent - it is bad, but not fatal
        // But if client can't get password - need show errors to client
        if (!$notify_sent && $generated_password_auth_type) {
            $this->assign('errors', array('signup' => _ws('Password has not been sent. Please contact your administrator.')));

            $this->logError(
                sprintf("Contact is singed up but send notification about signup failed and password not sent. See above detailed messages"),
                array('line' => __LINE__, 'file' => __FILE__)
            );

            return;
        }

        // Generated password has been sent (see above)
        // So we redirect client straight to login page
        if ($generated_password_auth_type) {
            if ($this->needRedirects()) {
                $this->redirectToLoginPage();
            }
            return;
        }

        // In rest other cases we just redirect to last page client visited
        if ($this->needRedirects()) {
            $this->redirectToLastPage();
        }
    }

    /**
     * Central action - workup signing up process itself
     * @param array $data
     * @throws waException
     */
    protected function executeSignupAction($data)
    {
        // First call sign-up core algorithm
        list($status, $details) = $this->signup($data);

        // Way to tell outer word about what status is now
        $this->assign('signup_status', $status);

        // Than dispatch by response status
        switch ($status) {
            case self::SIGNED_UP_STATUS_FAILED:
                $this->processSignupFailedStatus($data, $details);
                break;
            case self::SIGNED_UP_STATUS_IN_PROCESS:
                $this->processSignupInProcessStatus($data, $details);
                break;
            case self::SIGNED_UP_STATUS_OK:
                if (isset($details['contact'])) {
                    $this->assign('contact', $details['contact']);
                }
                $this->processSignupOkStatus($data, $details);
                break;
            default:
                // Unknown status
                $this->assign('errors', array('signup' => _ws('Signup failed.')));
                break;
        }
    }

    /**
     * Redirect to last page (aka referer)
     */
    protected function redirectToLastPage()
    {
        $url = $this->getStorage()->get('auth_referer');
        $this->getStorage()->del('auth_referer');
        if (!$url) {
            $url = waRequest::param('secure') ? $this->getConfig()->getCurrentUrl() : wa()->getAppUrl();
        }
        $this->redirect($url);
    }

    /**
     * Redirect to login page
     */
    protected function redirectToLoginPage()
    {
        $this->redirect($this->auth_config->getLoginUrl());
    }

    /**
     * Redirect to app page
     */
    protected function redirectToAppPage()
    {
        $this->redirect(wa()->getAppUrl());
    }

    /**
     * Redirect to sign up page
     */
    protected function redirectToSignupPage()
    {
        $signup_url = $this->auth_config->getSignUpUrl();
        $this->redirect($signup_url);
    }

    /**
     * Redirect to page where is message about that email has been confirmed
     */
    protected function redirectToEmailConfirmedPage()
    {
        $this->redirect($this->auth_config->getSignUpUrl(array(
            'get' => array(
                'email_confirmed' => '1'
            )
        )));
    }

    /**
     * Get posted data
     *
     * @example
     *
     *   getData('email') => string|null. Return only value for 'email' field
     *   getData(array('email', 'phone')) => array. Return values for 'email' and 'phone'
     *   getData() => Return all possible field values
     *
     * @param null|array|string $fields - take only that field(s)
     * @return array|string|null
     *      IF $fields is string
     *      THAN return string|null
     *      OTHERWISE return array
     */
    protected function getData($fields = null)
    {
        $data = $this->getRequest()->post($this->namespace, array(), waRequest::TYPE_ARRAY_TRIM);
        $data = is_array($data) ? $data : array();

        // filter off some fields
        if ($fields !== null) {

            $fields_to_extract = waUtils::toStrArray($fields);
            $result = array_fill_keys($fields_to_extract, null);
            foreach ($data as $field_id => $value) {
                $result[$field_id] = $value;
            }
            $data = $result;
        }

        if (is_scalar($fields)) {
            return isset($data[$fields]) ? $data[$fields] : null;
        }

        return $data;
    }

    protected function sendJson($response, $ok = true)
    {
        $this->getResponse()->addHeader('Content-Type', 'application/json');
        if ($ok) {
            $response = array('status' => 'ok', 'data' => $response);
        } else {
            $response = array('status' => 'fail', 'errors' => $response);
        }
        $this->getResponse()->sendHeaders();
        $this->out(json_encode($response), true);
    }

    protected function out($out, $exit = false)
    {
        echo $out;
        if ($exit) {
            exit;
        }
    }

    /**
     *
     * Main validation method - validate all data
     * Input data might be changed
     *
     * @param array &$data input data, passed by link. Might be changed
     * @return array Errors. For each invalid field array of errors
     *   Format:
     *     <field_id> => array <errors>
     *
     * @throws waException
     */
    protected function validate(&$data)
    {
        $data = is_array($data) ? $data : array();

        $errors = array();

        $required_fields = $this->auth_config->getRequiredFields();

        // FIRST OF ALL process email and phone requirement logic
        // Must be at least one of these presented

        $email_field_presented = $this->auth_config->getField('email');
        $phone_field_presented = $this->auth_config->getField('phone');

        if ($email_field_presented && $phone_field_presented && empty($data['email']) && empty($data['phone'])) {
            $errors['email,phone']['required'] = _ws("At least one of these fields is required");
        }

        if ($email_field_presented && !$phone_field_presented && empty($data['email'])) {
            $required_fields['email'] = 'email';
        }
        if ($phone_field_presented && !$email_field_presented && empty($data['phone'])) {
            $required_fields['email'] = 'phone';
        }

        // Check other required fields
        foreach ($required_fields as $field_id => $_) {
            if ($this->isFieldValueEmpty($field_id, ifset($data[$field_id]))) {
                $errors[$field_id] = (array)ifset($errors[$field_id]);
                $errors[$field_id]['required'] = sprintf(_ws("%s is required"), $this->getFieldCaption($field_id));
            }
        }

        // Check formal validity of email
        if ($email_field_presented && !empty($data['email'])) {
            if (!$this->isEmailValid($data['email'])) {
                $errors['email'] = (array)ifset($errors['email']);
                $errors['email']['invalid'] = _ws('Invalid Email');
            }
        }

        // Check formal validity of phone
        if ($phone_field_presented && !empty($data['phone'])) {
            if (!$this->isPhoneNumberValid($data['phone'])) {
                $errors['phone'] = (array)ifset($errors['phone']);
                $errors['phone']['invalid'] = _ws('Incorrect phone number value');
            } else {
                $data['phone'] = waContactPhoneField::cleanPhoneNumber($data['phone']);
            }
        }

        $onetime_password_need = $this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD;

        // If password presents than check password and password_confirm fields
        // IMPORTANT: in AUTH_TYPE_ONETIME_PASSWORD validation of 'password' must be skip
        // See below onetime_password validation
        if ($this->auth_config->getField('password') && !$onetime_password_need) {

            if (!isset($errors['password'])) {
                if (!$data['password']) {
                    // check required
                    $errors['password'] = array();
                    $errors['password_confirm']['required'] = _ws('A password cannot be empty.');
                } elseif ($data['password'] !== $data['password_confirm']) {
                    // check passwords match
                    $errors['password'] = (array)ifset($errors['password']);
                    $errors['password_confirm'] = (array)ifset($errors['password_confirm']);
                    $errors['password_confirm']['not_match'] = _ws('Passwords do not match');
                } elseif (strlen($data['password']) > waAuth::PASSWORD_MAX_LENGTH) {
                    // check passwords length
                    $errors['password'] = (array)ifset($errors['password']);
                    $errors['password_confirm'] = (array)ifset($errors['password_confirm']);
                    $errors['password_confirm']['too_long'] = _ws('Specified password is too long.');
                }
            }

        }

        // Check agreement with terms of service
        if ($this->auth_config->getServiceAgreement() == 'checkbox') {
            if (empty($data['terms_accepted'])) {
                $errors['terms_accepted'] = _ws('Please confirm your agreement');
            }
        }
        unset($data['terms_accepted']);

        // check captcha
        if ($this->auth_config->getSignUpCaptcha()) {
            if (!wa()->getCaptcha(['app_id' => $this->auth_config->getApp()])->isValid()) {
                $errors['captcha'] = _ws('Invalid captcha');
            }
        }

        // Enough errors in that point
        if ($errors) {
            return $errors;
        }

        // Ok, no errors => check uniqueness
        $uniqueness_errors = $this->validateUniqueness($data);
        foreach ($uniqueness_errors as $field_id => $error_msg) {
            $errors[$field_id] = (array)ifset($errors[$field_id]);
            $errors[$field_id]['exists'] = $error_msg;
        }

        // User already exists - stop work => return errors
        if ($errors) {
            return $errors;
        }

        // Ok, user NOT exist - continue validation

        // IMPORTANT: Implementation detail
        // Validation related with channel MUST BE at the end, cause successful validation remove asset (secret) from DB
        // So if there are ERRORS already - NEED BE returned


        // Need to validate onetime password OR not
        $onetime_password_validation_need = $onetime_password_need && isset($data['onetime_password']);

        // Need to validation confirmation code sent over the sms OR not
        $confirmation_code_validation_need = $this->auth_config->getSignUpConfirm() && isset($data['confirmation_code']) &&
                                                !empty($data['phone']);

        if ($onetime_password_validation_need) {

            // Field is required
            if (empty($data['onetime_password'])) {
                $errors['onetime_password']['required'] = _ws('Enter a confirmation code to complete signup');
            } else {

                // Validate onetime password
                $addresses = array();
                if (isset($data['email'])) {
                    $addresses['email'] = $data['email'];
                }

                if (isset($data['phone'])) {
                    $phone = $data['phone'];

                    //
                    $is_international = substr($phone, 0, 1) === '+';

                    // phone was transformed while sent sms with onetime password
                    $phone_transformed = $this->wasPhoneTransformedForSMS('onetime_password');

                    // input phone is not international and was transformed while sent sms means in DB we has transformed phone, so validation must be on transformed phone
                    if (!$is_international && $phone_transformed) {
                        $transformation_result = $this->auth_config->transformPhone($phone);
                        $phone = $transformation_result['phone'];
                    }

                    $addresses['phone'] = $phone;
                }

                list($valid, $details) = $this->validateOnetimePassword($data['onetime_password'], $addresses);
                if (!$valid) {
                    $errors['onetime_password'][$details['error_code']] = $details['error_msg'];
                }
            }

        } elseif ($confirmation_code_validation_need) {

            // Field is required
            if (empty($data['confirmation_code'])) {
                $errors['confirmation_code']['required'] = _ws('Enter a confirmation code to complete signup');
            } else {

                // phone in input (in post data)
                $phone = $data['phone'];

                //
                $is_international = substr($phone, 0, 1) === '+';

                // phone was transformed while sent sms with confirmation code
                $phone_transformed = $this->wasPhoneTransformedForSMS();

                // input phone is not international and was transformed while sent sms means in DB we has transformed phone, so validation must be on transformed phone
                if (!$is_international && $phone_transformed) {
                    $transformation_result = $this->auth_config->transformPhone($phone);
                    $phone = $transformation_result['phone'];
                }

                // Validate verification code
                list($valid, $details) = $this->validateConfirmationCode($data['confirmation_code'], $phone);
                if (!$valid) {
                    $errors['confirmation_code'][$details['error_code']] = $details['error_msg'];
                }
            }
        }

        return $errors;
    }

    /**
     * Validate for uniqueness
     * This method check each "login" field for uniqueness
     *
     * @param array $data input data
     * @return array Errors array of errors
     *   Format:
     *     <field_id> => array
     * @throws waException
     */
    protected function validateUniqueness($data)
    {
        $errors = array();
        $auth = wa()->getAuth();

        // $auth is NOT always instanceof waAuth, but lookupByLoginFields method only exists for waAuth
        if ($auth instanceof waAuth) {
            $contacts = $auth->lookupByLoginFields($data);
            foreach ($contacts as $field_id => $contact) {
                $errors[$field_id] = sprintf(_ws('User with the same “%s” field value is already registered.'), $this->getFieldCaption($field_id));
            }
        }

        return $errors;
    }

    protected function isEmailValid($email)
    {
        if (is_scalar($email)) {
            $validator = new waEmailValidator(array('required'=>true));
            return $validator->isValid((string)$email);
        }
        return $email !== null;
    }

    protected function isPhoneNumberValid($phone)
    {
        if (is_scalar($phone)) {
            $validator = new waPhoneNumberValidator();
            return $validator->isValid((string)$phone);
        }
        return $phone !== null;
    }

    protected function isFieldValueEmpty($field_id, $value)
    {
        $field = waDomainAuthConfig::factory()->getEnableField($field_id);
        if (!$field) {
            return true;
        }
        if ($field['is_composite']) {
            $value = is_array($value) ? $value : array();
            return $this->isArrayEmpty($value);
        }

        if ($field_id === 'birthday') {
            $validator = new waDateValidator();
            return $validator->isEmpty($value);
        }

        $value = is_scalar($value) ? (string)$value : '';
        return strlen($value) <= 0;
    }

    protected function isArrayEmpty($array)
    {
        foreach ($array as $key => $value) {
            if (!empty($value)) {
                return false;
            }
        }
        return true;
    }

    protected function isSentCodeTimeoutPassed()
    {
        $key = 'wa/signup/sent_code/last_time/';
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

    protected function isOnetimePasswordTimeoutPassed()
    {
        $key = 'wa/signup/sent_onetime_password/last_time/';
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
     * Mark phone was transformed due the sending of sms with confirmation_code or onetime_password
     * @param bool $transformed
     * @param string $type 'confirmation_code', 'onetime_password'
     * @throws waException
     */
    protected function markPhoneWasTransformedForSMS($transformed, $type = 'confirmation_code')
    {
        $key = 'wa/signup/sent_sms/phone_was_transformed/' . $type;
        wa()->getStorage()->set($key, (bool)$transformed);
    }

    /**
     * Was phone transformed due the sending sms with confirmation_code or onetime_password
     * @param string $type 'confirmation_code', 'onetime_password'
     * @return bool
     * @throws waException
     */
    protected function wasPhoneTransformedForSMS($type = 'confirmation_code')
    {
        $key = 'wa/signup/sent_sms/phone_was_transformed/' . $type;
        return (bool)wa()->getStorage()->get($key);
    }

    /**
     * Core of signing up action
     *
     * Consist of 2 parts:
     *   - validation
     *   - try signup logic
     *
     * @param array $data
     * @return array(0 => <status>, 1 => <details>)
     *  - 0 - string <status> SIGNED_UP_STATUS_* const
     *  - 1 - array <details> details what happened. In Failed status, for example, here is errors
     * @throws waException
     */
    public function signup($data)
    {
        $errors = $this->validate($data);
        if ($errors) {
            return array(self::SIGNED_UP_STATUS_FAILED, $errors);
        }
        return $this->trySignup($data);
    }

    /**
     * Core of signing up action
     * Try signup logic after data passed validation
     *
     * @param array $data
     * @return array(0 => <status>, 1 => <details>)
     *  - 0 - string <status> SIGNED_UP_STATUS_* const
     *  - 1 - array <details> details what happened. In Failed status, for example, here is errors
     * @throws waException
     */
    protected function trySignup($data)
    {
        //
        $onetime_password_need = $this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD;

        // IMPORTANT:
        // PROTOCOL detail (!)
        // If client post data WITHOUT 'onetime_password' than it means that client request new 'onetime_password'
        $onetime_password_present = isset($data['onetime_password']);


        // If need sent onetime password
        if ($onetime_password_need && !$onetime_password_present) {

            $addresses = array();
            if (isset($data['phone'])) {
                $addresses['phone'] = $data['phone'];
            }
            if (isset($data['email'])) {
                $addresses['email'] = $data['email'];
            }

            // diagnostic already printed inside
            list($sent, $details) = $this->sendOnetimePassword($addresses);

            if (!$sent) {
                $errors = $details ? $details : array('onetime_password_send' => _ws('Sending error'));
                $details['onetime_password_sent'] = $sent;
                return array(self::SIGNED_UP_STATUS_FAILED, $errors);
            } else {
                $details['onetime_password_sent'] = $sent;
                return array(self::SIGNED_UP_STATUS_IN_PROCESS, $details);
            }

        }

        $need_confirm = $this->auth_config->getSignUpConfirm();
        $confirm_by_sms = isset($data['confirmation_code']) && !empty($data['phone']);

        // If NO need to confirm or already successfully confirm by sms (already pass validation - see validate method)
        // So try save & sign up client right away
        if (!$need_confirm || $confirm_by_sms) {

            $save_options = array();
            if ($confirm_by_sms) {
                // to mark that phone is confirmed
                $save_options['confirmed'] = waVerificationChannelModel::TYPE_SMS;
            }
            $contact = $this->trySaveContact($data, $errors, $save_options);

            if ($errors) {
                return array(self::SIGNED_UP_STATUS_FAILED, $errors);
            }

            // IMPORTANT: User interaction behavior detail
            // In password generation mode (auth type) we not authorize right away signed up client
            // In UI we will show LOGIN FORM for that user
            $auth_right_away = $this->auth_config->getAuthType() !== waAuthConfig::AUTH_TYPE_GENERATE_PASSWORD;

            if (!$this->trySignupContact($contact, $auth_right_away)) {
                // error already logged - so no need log one time again
                return array(self::SIGNED_UP_STATUS_FAILED, array());
            }

            return array(self::SIGNED_UP_STATUS_OK, array(
                'contact' => $contact
            ));
        }

        // Confirm need - try send confirmation message
        $confirmation_sent = null;

        // phone was transformed?
        $phone_transformed = false;

        $channels = $this->auth_config->getVerificationChannelInstances();
        $contact = null;

        foreach ($channels as $channel) {

            // try email first
            if ($channel->isEmail() && !empty($data['email'])) {

                // We might need rollback contact creation when sending link has failed
                // If contact not need by deleted on failure redefined trySaveContact MUST not save this hash
                $creation_hash = md5(uniqid('trySaveContact'));

                // For this case with must create contact first
                // Cause with need validate email related for this contact ONLY
                $contact = $this->trySaveContact($data, $errors, array(
                    'creation_hash' => $creation_hash
                ));
                if ($errors) {
                    break;
                }

                if ($this->sendLink($contact)) {
                    // clean rollback creation hash
                    $contact->save(array('creation_hash' => null));
                    $confirmation_sent = waVerificationChannelModel::TYPE_EMAIL;
                    break;
                } else {

                    // sending is failed - rollback creation
                    if ($contact['creation_hash'] === $creation_hash) {
                        $contact->delete();
                        $contact = null;
                    }

                    // diagnostic log print
                    $this->logError(
                        sprintf("Couldn't send email message with link. Check email settings.\n%s",
                            $channel->getDiagnostic()),
                        array('line' => __LINE__, 'file' => __FILE__)
                    );

                }
            }

            // then try sms
            if ($channel->isSMS() && !empty($data['phone'])) {

                list($ok, $details) = $this->sendCode($data['phone']);
                if ($ok) {
                    $confirmation_sent = waVerificationChannelModel::TYPE_SMS;
                    $phone_transformed = !empty($details['phone_transformed']);
                    break;
                } elseif (isset($details['timeout'])) {
                    // Tell user about timeout error right aways - so return
                    return array(self::SIGNED_UP_STATUS_FAILED, $details);
                } else {
                    // diagnostic log print
                    $this->logError(
                        sprintf("Couldn't send SMS with code. Explore sms.log for details.\n%s",
                            $channel->getDiagnostic()),
                        array('line' => __LINE__, 'file' => __FILE__)
                    );
                }
            }
        }


        // Confirmation sent by email
        if ($confirmation_sent === waVerificationChannelModel::TYPE_EMAIL) {
            return array(self::SIGNED_UP_STATUS_IN_PROCESS, array(
                'confirmation_link_sent' => true,
                'contact' => $contact
            ));
        }

        // Confirmation sent by sms
        if ($confirmation_sent === waVerificationChannelModel::TYPE_SMS) {
            return array(self::SIGNED_UP_STATUS_IN_PROCESS, array(
                'confirmation_code_sent' => true,
                'phone_transformed' => $phone_transformed
            ));
        }

        // Looks like all channels failed
        $this->logError(
            sprintf("Couldn't send message with confirmation secret (link or code).\nLooks like there is no any working channel in system. Check auth settings for this domain %s",
                $this->auth_config->getDomain()),
            array('line' => __LINE__, 'file' => __FILE__)
        );

        return array(self::SIGNED_UP_STATUS_FAILED, array());
    }

    /**
     * @param waContact $contact
     * @return bool
     */
    protected function tryAuthContact(waContact $contact)
    {
        $result = false;
        try {
            $result = (bool)wa()->getAuth()->auth($contact);
        } catch (waException $e) {
            $this->logError($e);
        }
        if ($result) {
            $this->assign('auth_status', 'ok');
            $this->assign('contact', $contact);
        }
        return $result;
    }

    /**
     *
     * Try signing up contact
     *
     * NOTICE: When auth type is generated password then don't authorize in right away
     *
     * @param waContact $contact
     * @param bool $need_auth Need auth right away
     * @return bool
     * @throws waException
     */
    protected function trySignupContact(waContact $contact, $need_auth = true)
    {
        // Always generate password if contact hasn't it
        // contact.password must not be empty
        if (!$contact->get('password')) {
            $password = $this->getGeneratePassword();
            $contact->save(array('password' => $password));
        }

        /**
         * @event signup
         * @param waContact $contact
         */
        wa()->event('signup', $contact);

        // after sign up callback
        $this->afterSignup($contact);

        $this->logAction('signup', wa()->getEnv(), null, $contact->getId());

        // try auth new contact if needed
        if ($need_auth) {
            $auth_result = $this->tryAuthContact($contact);
            if ($auth_result) {
                $this->afterAuth();
            }
        }

        return true;
    }

    /**
     * @param array $data Input array of data
     * @param array $errors Output Array of errors
     * @param array $options
     *      Extra options to manage save process.
     *       - mixed 'confirmed'
     *          If 'string' waContactVerificationModel::TYPE_* const - mark contact confirmed by this channel right away
     *          Otherwise not mark
     * @return null|waContact
     * @throws waException
     */
    protected function trySaveContact($data, &$errors = array(), $options = array())
    {
        if (isset($data['birthday']['value']) && is_array($data['birthday']['value'])) {
            foreach ($data['birthday']['value'] as $bd_id => $bd_val) {
                if(strlen($bd_val) === 0) {
                    $data['birthday']['value'][$bd_id] = null;
                }
            }
        }

        $data_to_save = array();
        foreach ($this->auth_config->getFields() as $field_id => $_) {
            if (array_key_exists($field_id, $data)) {
                $data_to_save[$field_id] = $data[$field_id];
            }
        }

        // set advanced data
        $data_to_save['create_method'] = 'signup';
        $data_to_save['create_ip'] = waRequest::getIp();
        $data_to_save['create_user_agent'] = waRequest::getUserAgent();
        $data_to_save['is_company'] = waRequest::request('contact_type') === 'company' ? 1 : 0;

        if (empty($data_to_save['password'])) {
            $data_to_save['password'] = $this->getGeneratePassword();
        }

        if (wa()->getEnv() === 'frontend') {
            $data['create_domain'] = wa()->getRouting()->getDomain();
        }

        $need_signup_confirm = $this->auth_config->getSignUpConfirm();
        $options['confirmed'] = isset($options['confirmed']) ? $options['confirmed'] : null;

        // Prepare email field with proper status
        if (!empty($data_to_save['email'])) {

            $status = waContactEmailsModel::STATUS_UNKNOWN;
            if ($need_signup_confirm) {
                if ($options['confirmed'] === waVerificationChannelModel::TYPE_EMAIL) {
                    $status = waContactEmailsModel::STATUS_CONFIRMED;
                } else {
                    $status = waContactEmailsModel::STATUS_UNCONFIRMED;
                }
            }

            $data_to_save['email'] = array('value' => $data['email'], 'status' => $status);
        }

        // Prepare phone field with proper status
        if (!empty($data_to_save['phone'])) {

            $status = waContactDataModel::STATUS_UNKNOWN;
            if ($need_signup_confirm) {
                if ($options['confirmed'] === waVerificationChannelModel::TYPE_SMS) {
                    $status = waContactDataModel::STATUS_CONFIRMED;
                } else {
                    $status = waContactDataModel::STATUS_UNCONFIRMED;
                }
            }

            $phone = $data['phone'];

            // non-international phone try to convert to international
            $is_international = substr($phone, 0, 1) === '+';
            if (!$is_international) {
                $result = $this->auth_config->transformPhone($phone);
                $phone = $result['phone'];
            }

            $data_to_save['phone'] = array('value' => $phone, 'status' => $status);
        }

        // Rollback creation hash
        if (isset($options['creation_hash'])) {
            $data_to_save['creation_hash'] = $options['creation_hash'];
        }

        $contact = new waContact();
        $errors = $contact->save($data_to_save, true);
        if ($errors) {
            if (isset($errors['name'])) {
                $errors['firstname'] = array();
                $errors['middlename'] = array();
                $errors['lastname'] = $errors['name'];
            }
            return null;
        }
        return $contact;
    }

    protected function getFieldCaption($field_id)
    {
        $field = waDomainAuthConfig::factory()->getField($field_id);
        if ($field && isset($field['caption'])) {
            return $field['caption'];
        }
        $field = waContactFields::get($field_id);
        if ($field) {
            return $field->getName();
        }
        return ucfirst($field_id);
    }

    protected function getFrom()
    {
        return null;
    }

    /**
     * @deprecated
     *
     * Need for backward compatibility with Old Shop (version <= 7)
     *
     * @param waContact $contact
     * @return bool
     */
    public function send(waContact $contact)
    {
        return $this->sendLink($contact);
    }

    /**
     * Send confirmation link
     * @param waContact
     * @return bool
     */
    public function sendLink($recipient)
    {
        $email = '';
        if ($recipient instanceof waContact && $recipient->exists()) {
            $email = $recipient->get('email', 'default');
        }
        if (!$email) {
            return false;
        }

        if (!$this->auth_config->getSignUpConfirm()) {
            return false;
        }

        $confirmation_url = $this->auth_config->getSignUpUrl(array(
            'get' => array('confirm' => 'confirmation_hash')
        ), true);
        $confirmation_url = str_replace('confirmation_hash', '{$confirmation_hash}', $confirmation_url);


        $channel = $this->auth_config->getEmailVerificationChannelInstance();

        /**
         * @var waContact $recipient
         */
        $result = $channel->sendSignUpConfirmationMessage($recipient, array(
            'site_url' => $this->auth_config->getSiteUrl(),
            'site_name' => $this->auth_config->getSiteName(),
            'confirmation_url' => $confirmation_url,
        ));

        if (!$result) {
            return false;
        }

        return $result;
    }

    /**
     * Send code
     * @param string $phone
     * @return array $result
     *   - bool  $result[0] status
     *   - array $result[1] details
     *        bool $result[1]['phone_transformed'] was or not phone transformed for sms sending
     */
    protected function sendCode($phone)
    {
        if (!$this->auth_config->getSignUpConfirm()) {
            return array(false, array());
        }

        if (!$this->isSentCodeTimeoutPassed()) {
            return array(false, array(
                'timeout' => array(
                    'message' => $this->auth_config->getConfirmationCodeTimeoutErrorMessage(),
                    'timeout' => $this->auth_config->getConfirmationCodeTimeout()
                )
            ));
        }

        $phone_transformed = false;

        $is_international = substr($phone, 0, 1) === '+';

        $channel = $this->auth_config->getSMSVerificationChannelInstance();

        $result = $channel->sendSignUpConfirmationMessage($phone, array(
            'use_session' => true
        ));

        // Not sent, maybe because of sms adapter not work correct with not international phones
        if (!$result && !$is_international) {
            // If not international phone number - transform 8 to code (country prefix)
            $transform_result = $this->auth_config->transformPhone($phone);
            if ($transform_result['status']) {
                $phone_transformed = true;
                $phone = $transform_result['phone'];
                $result = $channel->sendSignUpConfirmationMessage($phone, array(
                    'use_session' => true
                ));
            }
        }

        $result = (bool)$result;

        return array($result, array(
            'phone_transformed' => $result && $phone_transformed
        ));
    }

    /**
     * @param $addresses
     * @param null $priority
     * @return array
     *   0 - bool <status>
     *   1 - array <details>
     * @throws waException
     */
    protected function sendOnetimePassword($addresses, $priority = null)
    {
        if ($this->auth_config->getAuthType() !== waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD) {
            return array(false, array());
        }

        if (!$this->isOnetimePasswordTimeoutPassed()) {
            return array(false, array(
                'timeout' => array(
                    'message' => $this->auth_config->getOnetimePasswordTimeoutErrorMessage(),
                    'timeout' => $this->auth_config->getOnetimePasswordTimeout()
                )
            ));
        }

        $channels = $this->auth_config->getVerificationChannelInstances($priority);

        $sent = false;
        $used_address = null;
        $used_channel_type = null;

        foreach ($channels as $channel) {

            $address = null;

            // choose address
            if ($channel->isEmail() && !empty($addresses['email'])) {
                $address = $addresses['email'];

                $sent = $channel->sendOnetimePasswordMessage($address, array(
                    'site_url' => $this->auth_config->getSiteUrl(),
                    'site_name' => $this->auth_config->getSiteName(),
                    'login_url' => $this->auth_config->getLoginUrl(array(), true),
                    'use_session' => true
                ));

            } elseif ($channel->isSMS() && !empty($addresses['phone'])) {
                $phone = $addresses['phone'];
                $is_international = substr($phone, 0, 1) === '+';
                $phone_transformed = false;

                $sent = (bool)$channel->sendOnetimePasswordMessage($phone, array(
                    'use_session' => true
                ));

                // Not sent, maybe because of sms adapter not work correct with not international phones
                if (!$sent && !$is_international) {
                    // If not international phone number - transform 8 to code (country prefix)
                    $transform_result = $this->auth_config->transformPhone($phone);
                    if ($transform_result['status']) {
                        $phone_transformed = true;
                        $phone = $transform_result['phone'];
                        $sent = (bool)$channel->sendOnetimePasswordMessage($phone, array(
                            'use_session' => true
                        ));
                    }
                }

                $address = $phone;

                if ($sent) {
                    $this->markPhoneWasTransformedForSMS($phone_transformed, 'onetime_password');
                }

            }

            if (!$address) {
                continue;
            }

            // successful send
            if ($sent) {
                $used_channel_type = $channel->getType();
                $used_address = $address;
                break;
            }

            // fail send

            // diagnostic log print

            if ($channel->isEmail()) {
                $diagnostic_message = "Couldn't send email message with onetime password. Check email settings.\n%s";
                $this->logError(
                    sprintf($diagnostic_message, $channel->getDiagnostic()),
                    array('line' => __LINE__, 'file' => __FILE__)
                );
            } elseif ($channel->isSMS()) {
                $diagnostic_message = "Couldn't send SMS with onetime password. Explore sms.log for details.\n%s";
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

        if (!$sent) {

            // Looks like all channels failed
            $this->logError(
                sprintf("Couldn't send message with onetime password.\nLooks like there is no any working channel in system. Check auth settings for this domain %s",
                    $this->auth_config->getDomain()),
                array('line' => __LINE__, 'file' => __FILE__)
            );

            return array(false, array());
        }

        $details = array(
            'used_address' => $used_address,
            'used_channel_type' => $used_channel_type
        );

        // Save details so we can validate 'onetime_password' in other http-request
        // See validate and validateOnetimePassword methods
        wa()->getStorage()->set('wa/signup/send_onetime_password', $details);

        return array(true, $details);

    }

    /**
     * @param waContact $contact
     */
    protected function afterSignup(waContact $contact)
    {

    }

    /**
     * After successful auth
     */
    protected function afterAuth()
    {

    }

    /**
     * @param $code
     * @param $phone
     * @return array
     *  - 0 - bool status
     *  - 1 - array details
     *      If status is FALSE, details has keys
     *        - string|null 'error_code' some string ID of error, that will be send to client as a controller response
     *        - string      'error_msg'  message about error
     * @throws waException
     */
    protected function validateConfirmationCode($code, $phone)
    {
        $default_error = _ws('Incorrect or expired confirmation code. Try again or request a new code.');

        if (!$this->auth_config->getSignUpConfirm()) {
            return array(false, array(
                'error_code' => waVerificationChannelSMS::VERIFY_ERROR_INVALID,
                'error_msg' => $default_error,
            ));
        }

        $channel = $this->auth_config->getSMSVerificationChannelInstance();

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

        $error_code = $result['details']['error'];
        if ($error_code === waVerificationChannel::VERIFY_ERROR_OUT_OF_TRIES) {
            $error_msg = _ws('You have run out of available attempts. Please request a new code.');
        } else {
            $error_msg = $default_error;
        }

        return array(false, array(
            'error_code' => $error_code,
            'error_msg'  => $error_msg
        ));
    }

    /**
     * @param string $password
     * @param array $addresses
     * @return array
     *  - 0 - bool status
     *  - 1 - array details
     *      If status is FALSE, details has keys
     *        - string|null 'error_code' some string ID of error, that will be send to client as a controller response
     *        - string      'error_msg'  message about error
     * @throws waException
     */
    protected function validateOnetimePassword($password, $addresses)
    {
        $default_error = _ws('Incorrect or expired one-time password. Try again or request a new one-time password.');

        if ($this->auth_config->getAuthType() !== waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD) {
            return array(false, array(
                'error_code' => waVerificationChannelSMS::VERIFY_ERROR_INVALID,
                'error_msg' => $default_error
            ));
        }

        // Save used_channel_type so we can validate on that type of channel
        // See validate and validateOnetimePassword methods
        $sent_info = wa()->getStorage()->get('wa/signup/send_onetime_password');
        $sent_info = is_array($sent_info) ? $sent_info : array();

        $used_address = null;
        foreach ($addresses as $address) {
            if ($address === ifset($sent_info['used_address'])) {
                $used_address = $address;
            }
        }

        if (!$used_address) {
            return array(false, array(
                'error_code' => waVerificationChannelSMS::VERIFY_ERROR_INVALID,
                'error_msg' => $default_error
            ));
        }

        $channel = $this->auth_config->getVerificationChannelInstance(ifset($sent_info['used_channel_type']));
        $result = $channel->validateOnetimePassword($password, array(
            'recipient' => $used_address,
            'check_tries' => array(
                'count' => $this->auth_config->getVerifyCodeTriesCount(),
                'clean' => true
            )
        ));

        if ($result['status']) {
            return array(true, null);   // no error, successful verification
        }

        $error_code = $result['details']['error'];
        if ($error_code === waVerificationChannel::VERIFY_ERROR_OUT_OF_TRIES) {
            $error_msg = _ws('You have run out of available attempts. Please request a new one-time password.');
        } else {
            $error_msg = $default_error;
        }

        return array(false, array(
            'error_code' => $error_code,
            'error_msg'  => $error_msg
        ));
    }

    /**
     * @param $confirmation_hash
     *
     * @return array
     *   Format the same as validateSignUpConfirmation returns
     * @see waVerificationChannel::validateSignUpConfirmation
     *
     * @throws waException
     */
    protected function validateConfirmationHash($confirmation_hash)
    {
        if (!$this->auth_config->getSignUpConfirm()) {
            return array(
                'status' => false,
                'details' => array(
                    'error' => waVerificationChannel::VERIFY_ERROR_INVALID
                )
            );
        }
        $channel = $this->auth_config->getEmailVerificationChannelInstance();
        return $channel->validateSignUpConfirmation($confirmation_hash);
    }

    /**
     * @throws waException
     */
    protected function notFound()
    {
        throw new waException(_ws('Page not found'), 404);
    }

    /**
     * @param $error
     * @param array $context
     *   Context where error occurred. May be any string like 'line' or 'file'
     */
    protected function logError($error, $context = array())
    {
        // IMPORTANT:
        // @var_export - @ just in case if var_export trigger warning or notice
        // For example "var_export does not handle circular references"

        if ($error instanceof Exception) {
            $trace = $error->getTraceAsString();
            $message = get_class($error) . " - " . $error->getCode() . " - " . $error->getMessage() . PHP_EOL . $trace . PHP_EOL;
        } elseif (!is_scalar($error)) {
            $message = @var_export($error, true);
        } else {
            $message = $error;
        }

        if ($context) {
            $log_error = sprintf("Error=%s\nContext=%s\nAction=%s\nIP=%s",
                $message,
                @var_export($context, true),
                get_class($this),
                waRequest::getIp()
            );
        } else {
            $log_error = sprintf("Error=%s\nAction=%s\nIP=%s", $message, get_class($this), waRequest::getIp());
        }

        $date = date('Y-m-d');
        waLog::log($log_error, "signup/action/error-{$date}.log");
    }
}
