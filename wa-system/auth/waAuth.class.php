<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage auth
 */
class waAuth implements waiAuth
{
    protected static $static_cache;

    const LOGIN_FIELD_EMAIL = 'email';
    const LOGIN_FIELD_PHONE = 'phone';
    const LOGIN_FIELD_LOGIN = 'login';

    const PASSWORD_MAX_LENGTH = 255;

    protected $options = array(
        'cookie_expire' => 2592000,
    );

    protected $available_login_field_ids = array(
        self::LOGIN_FIELD_LOGIN,
        self::LOGIN_FIELD_EMAIL,
        self::LOGIN_FIELD_PHONE
    );

    /**
     * ID of login field by which will be found auth-contact
     * @see getByLogin
     * @var string|null
     */
    protected $current_login_field_id = null;

    /**
     * @var waAuthConfig
     */
    protected $auth_config;

    protected $env;

    /**
     * @param array $options
     */
    public function __construct($options = array())
    {
        $this->env = wa()->getEnv();

        if (is_array($options)) {
            foreach ($options as $k => $v) {
                $this->options[$k] = $v;
            }
        }

        if (!isset($this->options['is_user'])) {
            // only contacts with is_user = 1 can auth
            $this->options['is_user'] = $this->env == 'backend';
        }

        if (isset($this->options['env'])) {
            $this->env = $this->options['env'];
        }

        $this->auth_config = waAuthConfig::factory([
            'env' => $this->env
        ]);

        $this->initLoginFieldIds();

    }

    /**
     * Try authorize contact in current session
     *
     * @param array|waContact $params
     * @return array|bool - If failed - return false, otherwise contact info as associative array
     * @throws waAuthException
     * @throws waAuthInvalidCredentialsException
     * @throws waAuthConfirmEmailException
     * @throws waAuthConfirmPhoneException
     * @throws waAuthRunOutOfTriesException
     * @throws waException
     */
    public function auth($params = array())
    {
        $result = $this->_auth($params);
        if ($result !== false) {
            waSystem::getInstance()->getStorage()->write('auth_user', $result);
            waSystem::getInstance()->getUser()->init();
        }
        return $result;
    }

    /**
     * Check if in current session authorized some contact and return info about it
     *
     * @return array|bool|null - if FALSE (or some kind of emptiness) then there is not authorized contact in session
     *  Otherwise returns info about contact (as associative array)
     *
     * @throws waAuthException
     * @throws waException
     */
    public function isAuth()
    {
        $info = waSystem::getInstance()->getStorage()->read('auth_user');
        if (!$info) {
            $info = $this->_authByCookie();
            if ($info) {
                waSystem::getInstance()->getStorage()->write('auth_user', $info);
            }
        }

        if ($info && $info['id'] && (!$this->getOption('is_user') || ifempty($info['is_user']) > 0)) {
            return $info;
        }
        return false;
    }

    /**
     * @param string $email
     * @return array|null
     */
    protected function getByEmail($email)
    {
        if (!$this->isValidEmail($email)) {
            return null;
        }

        $model = new waContactModel();

        $where = array();
        if ($this->options['is_user']) {
            $where[] = "c.is_user = 1";
        }
        $where[] = "c.password != ''";
        $where[] = "e.email LIKE s:email";
        $where[] = "e.sort = 0";

        $where = join(' AND ', $where);

        $sql = "SELECT c.* FROM wa_contact c
                JOIN wa_contact_emails e ON c.id = e.contact_id
                WHERE {$where}
                ORDER BY c.id LIMIT 1";
        return $model->query($sql, array('email' => $email))->fetchAssoc();
    }

    /**
     * Get first found singed up contact by this authentication ID (login)
     *
     * For this method login can be
     *   - actual contact login (wa_contact.login) - self::LOGIN_FIELD_LOGIN
     *   - email of contact - self::LOGIN_FIELD_EMAIL
     *   - phone of contact - self::LOGIN_FIELD_PHONE
     *
     *
     * @param string $login
     * @param null|string $login_type
     *      - If waAuth::LOGIN_FIELD_* const - By which login type we will look up.
     *      - Otherwise (NULL, skipped, etc) will look up
     *          - by suitable for $login login type or
     *          - by any login type (ordered by priority as in waAuthConfig)
     *
     * @return array|null
     * @throws waAuthException
     * @throws waException
     */
    public function getByLogin($login, $login_type = null)
    {
        $login = is_scalar($login) ? (string)$login : '';
        if (strlen($login) <= 0) {
            return null;
        }

        // Login values for look up
        $login_values = array();
        foreach ($this->getLoginFieldIds() as $field_id) {
            $login_values[$field_id] = $login;
        }

        // typecast input parameter
        if ($login_type !== self::LOGIN_FIELD_LOGIN || $login_type !== self::LOGIN_FIELD_EMAIL || $login_type !== self::LOGIN_FIELD_PHONE) {
            $login_type = null;
        }

        // By which login type we will look up
        if ($login_type === null) {
            if ($this->isValidEmail($login)) {
                $login_type = self::LOGIN_FIELD_EMAIL;
            } elseif ($this->isValidPhoneNumber($login)) {
                $login_type = self::LOGIN_FIELD_PHONE;
            } else {
                $login_type = null;
            }
        }

        $result = $this->lookupByLoginFields($login_values, $login_type, 'first');
        if (!$result) {
            return null;
        }

        $this->current_login_field_id = key($result);
        $contact = $result[$this->current_login_field_id];

        // being paranoid
        $this->checkBan($contact);

        return $contact;
    }

    /**
     * @param $login
     * @return array|null
     */
    protected function getByContactLogin($login)
    {
        $login = is_scalar($login) ? (string)$login : '';
        if (strlen($login) <= 0) {
            return null;
        }

        $where = array();
        if ($this->options['is_user']) {
            $where[] = "is_user = 1";
        }

        $where[] = "password != ''";
        $where[] = 'login = :login';

        $where = join(' AND ', $where);

        $sql = "SELECT * FROM wa_contact
                WHERE {$where}
                ORDER BY id
                LIMIT 1";

        $model = new waContactModel();
        return $model->query($sql, array('login' => $login))->fetchAssoc();
    }

    /**
     * Get registered contact by phone with taking into account transformation settings of auth config
     * @param string $phone
     * @return array|null
     */
    protected function getByPhone($phone)
    {
        if (!$this->isValidPhoneNumber($phone)) {
            return null;
        }

        // do always first try by phone as it
        $contact = $this->findByPhone($phone);
        if ($contact) {
            return $contact;
        }

        $phone = (string)$phone;
        $is_international = substr($phone, 0, 1) === '+';

        // If international phone number - "reverse" transform to phone with 8
        // If not international phone number - transform 8 to code (country prefix)
        $result = $this->auth_config->transformPhone($phone, $is_international);

        // phone is changed (transformation has been applied), so try find by this new phone
        if ($result['status']) {
            return $this->findByPhone($result['phone']);
        }

        return null;
    }

    /**
     * Find registered contact by phone without taking into account transformation settings of auth config
     * @param $phone
     * @return array|null
     */
    protected function findByPhone($phone)
    {
        if (!$this->isValidPhoneNumber($phone)) {
            return null;
        }

        $phone = waContactPhoneField::cleanPhoneNumber($phone);
        $model = new waContactModel();

        $where = array();
        if ($this->options['is_user']) {
            $where[] = "c.is_user = 1";
        }

        $where[] = "c.password != ''";
        $where[] = 'd.value = :phone';
        $where[] = 'd.sort = 0';

        $where = join(' AND ', $where);

        $sql = "SELECT c.* FROM wa_contact c
                JOIN wa_contact_data d ON c.id = d.contact_id AND d.field = 'phone'
                WHERE {$where}
                ORDER BY c.id LIMIT 1";
        return $model->query($sql, array('phone' => $phone))->fetchAssoc();
    }

    /**
     *
     * @param array $login_values <field_id> => <value> map expected.
     *   <field_id> it is waAuth::LOGIN_FIELD_* constant
     *   For each field try to find contact by proper value
     *
     * @param string[]|string $priority order of looking up - array (or single scalar) of <field_id> waAuth::LOGIN_FIELD_*
     *
     * @param string $lookup_type 'all'|'first'
     *   'all' - return all result of looking up process (Default value)
     *   'first' - looking up stop after first success found. Return only one item array in result
     *
     * @return array
     *   - Array of format <field_id> => array|null <contact>
     *       <field_id> it is waAuth::LOGIN_FIELD_* constant
     *       <contact> if NOT found - null, if found - array of contact info
     *
     *   - Array may be empty if no contacts found
     *
     * @throws waException
     */
    public function lookupByLoginFields($login_values, $priority = null, $lookup_type = 'all')
    {
        $login_values = is_array($login_values) ? $login_values : array();
        $available = array_fill_keys($this->available_login_field_ids, true);
        foreach ($login_values as $field_id => $value) {
            if (!isset($available[$field_id])) {
                unset($login_values[$field_id]);
            }
        }

        $login_values = waUtils::orderKeys($login_values, $priority);

        $result = array();

        foreach ($login_values as $field_id => $value) {
            $contact = null;
            if ($field_id === self::LOGIN_FIELD_LOGIN) {
                $contact = $this->getByContactLogin($value);
            } elseif ($field_id === self::LOGIN_FIELD_EMAIL) {
                $contact = $this->getByEmail($value);
            } elseif ($field_id === self::LOGIN_FIELD_PHONE) {
                $contact = $this->getByPhone($value);
            }
            if (!$contact || $contact['is_user'] <= -1) {
                continue;
            }
            $result[$field_id] = $contact;

            if ($lookup_type === 'first' && $contact) {
                break;
            }
        }

        return $result;
    }

    /**
     * @param array $data - contact/user info
     * @throws waAuthException
     */
    protected function checkBan($data)
    {
        if ($data['is_user'] == -1) {
            throw new waAuthException(_ws('Access denied.'));
        }
    }

    protected function _prepareAuthParams($params)
    {
        if ($params instanceof waContact) {
            return $params;
        }

        $params = is_array($params) && !empty($params) ? $params : array();

        if (isset($params['login'])) {
            $params['login'] = is_scalar($params['login']) ? (string)$params['login'] : '';
        } else {
            $params['login'] = null;
        }

        if (isset($params['password'])) {
            $params['password'] = is_scalar($params['password']) ? (string)$params['password'] : '';
        } else {
            $params['password'] = null;
        }


        if (isset($params['remember'])) {
            $params['remember'] = (bool)$params['remember'];
        } else {
            $params['remember'] = false;
        }


        if (isset($params['id'])) {
            $params['id'] =  is_scalar($params['id']) ? (int)$params['id'] : null;
        } else {
            $params['id'] =  null;
        }

        return $params;
    }

    protected function _afterAuth($user_info, $params)
    {
        $this->_remember($user_info, $params['remember']);
        return $this->getAuthData($user_info);
    }

    /**
     * @param array|waContact $params
     * @return array|bool
     * @throws waAuthException
     * @throws waAuthInvalidCredentialsException
     * @throws waAuthConfirmEmailException
     * @throws waAuthConfirmPhoneException
     * @throws waAuthRunOutOfTriesException
     * @throws waException
     */
    protected function _auth($params)
    {
        $params = $this->_prepareAuthParams($params);

        if ($params['id'] > 0) {
            $contact_model = new waContactModel();
            $user_info = $contact_model->getById($params['id']);
            if ($user_info && ($user_info['is_user'] > 0 || !$this->options['is_user'])) {

                $response = waSystem::getInstance()->getResponse();

                $cookie_domain = ifset($this->options['cookie_domain'], '');
                $remember_enabled = $this->auth_config->getRememberMe();

                if (empty($params['remember']) || !$remember_enabled) {
                    $response->setCookie('auth_token', null, -1, null, $cookie_domain);
                }

                return $this->_afterAuth($user_info, $params);
            }
            return false;
        }

        if ($params['login'] === null) {
            return $this->_authByCookie();
        }

        $login = $params['login'];
        $password = $params['password'];
        if (strlen($login) <= 0) {
            throw new waAuthException(_ws('Login is required'));
        }

        $user_info = $this->getByLogin($login);
        if (!$user_info || ($this->options['is_user'] && $user_info['is_user'] <= 0)) {
            throw new waAuthException(_ws("Invalid login name or password."));
        }

        if ($this->isOnetimePasswordMode()) {
            // this method could throw different kind of exceptions by himself
            $result = $this->_authByOnetimePassword(new waContact($user_info['id']), $login, $password);
            if ($result) {
                return $this->_afterAuth($user_info, $params);
            }
        }

        $result = $this->_authByPassword($user_info, $password);
        if (!$result) {
            throw new waAuthInvalidCredentialsException();
        }

        // In case auth channel confirmation is required,
        // check that this contact has phone or email confirmed.
        // Otherwise do not allow to log in via frontend.
        $this->mustNeedConfirmSignup($user_info);

        return $this->_afterAuth($user_info, $params);

    }


    /**
     * @param array $contact
     * @throws waAuthException
     * @throws waAuthConfirmEmailException
     * @throws waAuthConfirmPhoneException
     * @throws waException
     */
    protected function mustNeedConfirmSignup($contact)
    {
        if ($this->env !== 'frontend' || !$this->auth_config->getSignupConfirm()) {
            return;
        }

        $contact = new waContact($contact['id']);
        $is_user = $contact['is_user'];

        if ($is_user) {
            return; // User can log in
        }

        $cem = new waContactEmailsModel();
        $email_row = $cem->getEmail($contact['id']);

        $cdm = new waContactDataModel();
        $phone_row = $cdm->getPhone($contact['id']);

        // error that stop logging in
        $error = new waAuthException(_ws("Contact can't be authorized"));

        // What login field used to auth
        $login_field_id = $this->current_login_field_id;

        // Verification channels - will need to check availability of concrete type of channel
        $channels = $this->auth_config->getVerificationChannelInstances();

        // If try logging in by EMAIL
        if ($login_field_id === self::LOGIN_FIELD_EMAIL) {

            // If we log in by email email must exist
            if (!$email_row) {
                throw $error;
            }

            // Email unconfirmed case
            if ($email_row['status'] != waContactEmailsModel::STATUS_CONFIRMED) {
                if (!$phone_row) {
                    throw $this->getAuthConfirmEmailException();
                }
                if ($phone_row['status'] != waContactDataModel::STATUS_CONFIRMED) {
                    throw $this->getAuthConfirmEmailException();
                }
                if (!$this->isChannelAvailable($channels, self::LOGIN_FIELD_PHONE)) {
                    throw $this->getAuthConfirmEmailException();
                }
            }

            // If here that it means that email or phone are confirmed

            return; // Contact can log in
        }

        // If try logging in by PHONE
        if ($login_field_id === self::LOGIN_FIELD_PHONE) {

            // If we log in by phone phone must exist
            if (!$phone_row) {
                throw $error;
            }

            // Phone unconfirmed case
            if ($phone_row['status'] != waContactDataModel::STATUS_CONFIRMED) {
                if (!$email_row) {
                    throw $this->getAuthConfirmPhoneException();
                }
                if ($email_row['status'] != waContactEmailsModel::STATUS_CONFIRMED) {
                    throw $this->getAuthConfirmPhoneException();
                }
                if (!$this->isChannelAvailable($channels, self::LOGIN_FIELD_EMAIL)) {
                    throw $this->getAuthConfirmPhoneException();
                }
            }

            // If here that it means that email or phone are confirmed

            return; // Contact can log in
        }

        // If try logging in by LOGIN
        if ($login_field_id === self::LOGIN_FIELD_LOGIN) {

            // At least one of "log-in" field MUST exist
            if (!$email_row && !$phone_row) {
                throw $error;
            }

            // At least one of "log-in" field MUST NOT BE unconfirmed
            if ($email_row['status'] == waContactEmailsModel::STATUS_UNCONFIRMED && $phone_row['status'] == waContactDataModel::STATUS_UNCONFIRMED) {
                throw $error;
            }

            return; // Contact can log in
        }

        throw $error;
    }

    /**
     * @param array|waVerificationChannel[] $channels Array of channels
     * @param string $login_field_id self::LOGIN_FIELD_* const
     * @return bool
     * @throws waException
     */
    protected function isChannelAvailable($channels, $login_field_id)
    {
        $is_available = false;

        if ($login_field_id === self::LOGIN_FIELD_LOGIN) {
            return true;
        }

        foreach ($channels as $channel) {
            $channel = waVerificationChannel::factory($channel);
            if ($login_field_id === self::LOGIN_FIELD_EMAIL && $channel->getType() === waVerificationChannelModel::TYPE_EMAIL) {
                $is_available = true;
                break;
            }
            if ($login_field_id === self::LOGIN_FIELD_PHONE && $channel->getType() === waVerificationChannelModel::TYPE_SMS) {
                $is_available = true;
                break;
            }
        }

        return $is_available;
    }

    /**
     * @return waAuthConfirmEmailException
     */
    protected function getAuthConfirmEmailException()
    {
        $login_url = $this->auth_config->getSignupUrl(array(
            'get' => 'send_confirmation=1'
        ));
        $msg = _ws('A confirmation link has been sent to your email address provided during the signup. Please click this link to confirm your email and to sign in. <a class="send-email-confirmation" href="%s">Resend the link</a>');
        $msg = sprintf($msg, $login_url);
        return new waAuthConfirmEmailException($msg);
    }

    /**
     * @return waAuthConfirmPhoneException
     */
    protected function getAuthConfirmPhoneException()
    {
        $msg = _ws('Please confirm your phone number to sign in.');
        return new waAuthConfirmPhoneException($msg);
    }

    protected function _remember($user_info, $remember)
    {
        $response = waSystem::getInstance()->getResponse();

        // if remember
        $remember_enabled = $this->auth_config->getRememberMe();

        if ($remember && $remember_enabled) {
            $cookie_domain = ifset($this->options['cookie_domain'], '');
            $response->setCookie('auth_token', $this->getToken($user_info), time() + 2592000, null, $cookie_domain, false, true);
            $response->setCookie('remember', 1);
        } else {
            $response->setCookie('remember', 0);
        }
    }

    protected function isOnetimePasswordMode()
    {
        return $this->auth_config->getAuthType() ===  waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD;
    }

    protected function _authByPassword($contact, $password)
    {
        $contact_password = isset($contact['password']) && is_scalar($contact['password']) ? $contact['password'] : '';
        return strlen($contact_password) > 0 && waContact::getPasswordHash($password) === $contact_password;
    }

    /**
     * @param waContact $contact
     * @param $login
     * @param $password
     * @return bool
     * @throws waAuthInvalidCredentialsException
     * @throws waAuthRunOutOfTriesException
     * @throws waException
     */
    protected function _authByOnetimePassword(waContact $contact, $login, $password)
    {
        if (!$this->isOnetimePasswordMode()) {
            throw new waAuthInvalidCredentialsException();
        }
        if (!$contact->exists()) {
            throw new waAuthInvalidCredentialsException();
        }

        $csm = new waContactSettingsModel();
        $asset_id = $csm->getOne($contact->getId(), 'webasyst', 'onetime_password_id');
        if (!$asset_id) {
            throw new waAuthInvalidCredentialsException();
        }

        // was phone was transformed during the sms sending
        $phone_transformed = $csm->getOne($contact->getId(), 'webasyst', 'onetime_password_phone_transformed');

        if ($this->isValidEmail($login)) {
            $priority = waVerificationChannelModel::TYPE_EMAIL;
        } elseif ($this->isValidPhoneNumber($login)) {
            $priority = waVerificationChannelModel::TYPE_SMS;
        } else {
            $priority = null;
        }

        $channels = $this->auth_config->getVerificationChannels($priority);
        if (!$channels) {
            throw new waAuthInvalidCredentialsException();
        }

        $recipient = array(
            'id' => $contact->getId(),
            'phone' => $contact->get('phone', 'default'),
            'email' => $contact->get('email', 'default')
        );
        $recipient['phone'] = waContactPhoneField::cleanPhoneNumber($recipient['phone']);

        if ($phone_transformed) {
            $transformation_result = $this->auth_config->transformPhone($recipient['phone']);
            if ($transformation_result['status']) {
                // actually transformed successfully
                $recipient['phone'] = $transformation_result['phone'];
            }
        }

        $verified = false;
        $results = array();
        foreach ($channels as $channel_id => $channel) {
            $channel = waVerificationChannel::factory($channel);

            $res = $channel->validateOnetimePassword($password, array(
                'recipient' => $recipient,
                'asset_id' => $asset_id,
                'check_tries' => array(
                    'count' => $this->auth_config->getVerifyCodeTriesCount(),
                    'clean' => true
                )
            ));

            $results[$channel->getType()] = $res;
            if ($res['status']) {
                $verified = true;
                break;
            }
        }

        if ($verified) {
            return true;
        }

        foreach ($results as $result) {
            if ($result['details']['error'] === waVerificationChannel::VERIFY_ERROR_OUT_OF_TRIES) {
                throw new waAuthRunOutOfTriesException(_ws('You have run out of available attempts. Please request a new one-time password.'));
            }
        }

        throw new waAuthInvalidCredentialsException();

    }


    /**
     * @param $string
     * @return bool
     */
    protected function isValidEmail($string)
    {
        if (!is_scalar($string)) {
            return false;
        }
        $validator = new waEmailValidator(array('required'=>true));
        return $validator->isValid((string)$string);
    }

    protected function isValidPhoneNumber($string)
    {
        if (!is_scalar($string)) {
            return false;
        }
        $validator = new waPhoneNumberValidator();
        return $validator->isValid((string)$string);
    }


    /**
     * @return array|bool
     * @throws waAuthException
     * @throws waException
     */
    protected function _authByCookie()
    {
        $remember_enabled = $this->auth_config->getRememberMe();
        if ($remember_enabled && $token = waRequest::cookie('auth_token')) {
            $model = new waContactModel();
            $response = waSystem::getInstance()->getResponse();
            $id = substr($token, 15, -15);
            $user_info = $model->getById($id);
            $this->checkBan($user_info);
            $cookie_domain = ifset($this->options['cookie_domain'], '');
            if ($user_info && ($user_info['is_user'] > 0 || !$this->options['is_user']) &&
                $token === $this->getToken($user_info)) {
                $response->setCookie('auth_token', $token, time() + 2592000, null, $cookie_domain, false, true);
                return $this->getAuthData($user_info);
            } else {
                $response->setCookie('auth_token', null, -1, null, $cookie_domain);
            }
        }
        return false;
    }


    /**
     * @param $user_info
     * @return array
     */
    protected function getAuthData($user_info)
    {
        return array(
            'id' => $user_info['id'],
            'login' => $user_info['login'],
            'is_user' => $user_info['is_user'],
            'token' => $this->getToken($user_info),
            'storage_set' => time(), // used in waAuthUser->init()
        );
    }

    /**
     * @param ArrayAccess|array $user_info
     * @return string
     */
    public function getToken($user_info)
    {
        $hash = md5($user_info['create_datetime'] . $user_info['login'] . $user_info['password']);
        return substr($hash, 0, 15).$user_info['id'].substr($hash, -15);
    }

    /**
     * Clear all auth tokens in storage and cookies
     * @return void
     * @throws waException
     */
    public function clearAuth()
    {
        // collect of session keys, that no need to be destroyed
        $persistent = [
            waReCaptcha::SESSION_KEY => null
        ];

        foreach ($persistent as $key => $_) {
            $persistent[$key] = waSystem::getInstance()->getStorage()->get($key);
        }

        waSystem::getInstance()->getStorage()->destroy();

        // restore persistent session keys
        foreach ($persistent as $key => $value) {
            if ($value !== null) {
                waSystem::getInstance()->getStorage()->set($key, $value);
            }
        }

        if (waRequest::cookie('auth_token')) {
            $cookie_domain = ifset($this->options['cookie_domain'], '');
            waSystem::getInstance()->getResponse()->setCookie('auth_token', null, -1, null, $cookie_domain);
            if ($cookie_domain) {
                waSystem::getInstance()->getResponse()->setCookie('auth_token', null, -1);
            }
        }
    }

    /**
     * Check if current authorization information in session is actual (correct, consistent) for this contact (represented by $data)
     *
     * NOTICE: if auth-info is not correct (broken, inconsistent) - clear auth-info from session
     *
     * @param ArrayAccess|array $data
     * @return bool
     * @throws waAuthException
     * @throws waException
     */
    public function checkAuth($data = null)
    {
        if ($auth_info = $this->isAuth()) {
            if (!isset($auth_info['token']) || $auth_info['token'] != $this->getToken($data)) {
                $this->clearAuth();
                return false;
            }
        }
        return true;
    }

    /**
     * Update current authorization information in session by contact info (represented as associative array $data)
     * @param array $data
     * @return mixed|void
     */
    public function updateAuth($data)
    {
        wa()->getStorage()->set('auth_user', $this->getAuthData($data));
        if (waRequest::cookie('auth_token')) {
            $cookie_domain = ifset($this->options['cookie_domain'], '');
            wa()->getResponse()->setCookie('auth_token', $this->getToken($data), time() + 2592000, null, $cookie_domain, false, true);
        }
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($name)
    {
        return $this->options[$name];
    }

    /**
     * @return array
     */
    public function getLoginFieldIds()
    {
        return $this->getOption('login_field_ids');
    }

    protected function initLoginFieldIds()
    {
        $this->options['login_field_ids'] = array();

        if ($this->auth_config != null) {
            $this->options['login_field_ids'] = $this->auth_config->getLoginFieldIds();
        }

        /*
        // backend case
        if ($this->env === 'backend') {
            $this->options['login_field_ids'] = array('login');
            return;
        }

        $available = array_fill_keys($this->available_login_field_ids, true);

        // option injection case
        if ( isset($this->options['login_field_ids']) &&
                (is_array($this->options['login_field_ids']) ||
                    is_scalar($this->options['login_field_ids'])) ) {
            $login_field_ids = (array)$this->options['login_field_ids'];
            foreach ($login_field_ids as $index => $field_id) {
                if (!isset($available[$field_id])) {
                    unset($available[$index]);
                }
            }
            if (!$login_field_ids) {
                $login_field_ids = array('login');
            }
            $this->options['login_field_ids'] = $login_field_ids;
            return;
        }

        // config injection case
        $login_field_ids = array();

        foreach (waDomainAuthConfig::factory()->getFields() as $field_id => $field) {
            if (isset($available[$field_id]) && !empty($field['required'])) {
                $login_field_ids[] = $field_id;
            }
        }
        if (!$login_field_ids) {
            $login_field_ids = array('login');
        }
        $this->options['login_field_ids'] = $login_field_ids;
        return;*/
    }
}

