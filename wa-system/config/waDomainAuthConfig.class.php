<?php

/**
 * Class waDomainAuthConfig
 *
 * Config that controls signing up process
 * That config always related with concrete DOMAIN
 */
class waDomainAuthConfig extends waAuthConfig
{
    protected static $static_cache;

    /**
     * @var string Current domain
     */
    protected $domain;

    /**
     * @var string
     *   If $this->domain is Alias (mirror)
     *   Than $this->original_domain is domain for what current domain is alias
     *   Otherwise $this->original_domain is the same as $this->domain
     */
    protected $original_domain;

    protected $domain_info;
    protected $config;
    protected $available_fields;
    protected $enable_fields;
    protected $must_have_fields = array();
    protected $must_not_have_fields = array();
    protected $default_fields = array(
        'firstname',
        'lastname',
        ''
    );

    protected $auth_adapters;

    /**
     * waDomainAuthConfig constructor.
     * @param null|string $domain If null, use current domain
     */
    protected function __construct($domain)
    {
        $this->domain = $domain;

        $alias = wa()->getRouting()->isAlias($domain);
        if ($alias) {
            $this->original_domain = $alias;
        } else {
            $this->original_domain = $this->domain;
        }

        $this->config = wa()->getAuthConfig($this->original_domain);

        if ($this->getAuthType() === waAuthConfig::AUTH_TYPE_USER_PASSWORD) {
            $this->must_have_fields[] = 'password';
            $this->default_fields[] = 'password';
        } else {
            $this->must_not_have_fields[] = 'password';
        }
    }

    /**
     * @param null|string $domain
     * @return waDomainAuthConfig
     */
    public static function factory($domain = null)
    {
        if (!$domain) {
            $domain = wa()->getRouting()->getDomain(null, true, false);
        }

        if (!isset(self::$static_cache['instances'])) {
            self::$static_cache['instances'] = array();
        }
        if (!isset(self::$static_cache['instances'][$domain])) {
            $config = new self($domain);
            // orders matter to prevent waAuth <-> self infinitive recursion
            self::$static_cache['instances'][$domain] = $config;
            $config->ensureConsistency();
        }
        return self::$static_cache['instances'][$domain];
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function getOriginalDomain()
    {
        return $this->original_domain;
    }

    public function getSiteUrl()
    {
        return $this->getDomain();
    }

    /**
     * @param string|null $name Name of field of info-array. Default is NULL
     *
     * @return mixed
     *
     *   - array
     *       If $name is null - return all info record FROM DB
     *       If returned NOT empty array than it has fields:
     *         + int 'id'
     *         + string 'name'
     *         + string 'title'
     *         + string 'style'
     *       @see siteDomainModel and underline table
     *
     *   - NOT array
     *      If $name is NOT null - return concrete field value.
     *        If field value is not exists - always NULL
     *        Otherwise any scalar value from DB - int|string|null
     */
    public function getDomainInfo($name = null)
    {
        if ($this->domain_info === null) {
            $this->domain_info = array();
            if (wa()->appExists('site') && class_exists('siteDomainModel')) {
                $model = new siteDomainModel();
                $info = $model->getByName($this->getDomain());
                if ($info) {
                    $this->domain_info = $info;
                }
            }
        }
        if ($name !== null && is_scalar($name)) {
            return isset($this->domain_info[$name]) ? $this->domain_info[$name] : null;
        } else {
            return $this->domain_info;
        }
    }

    public function getSiteName()
    {
        $site_name = $this->getDomainInfo('title');
        $site_name = $site_name ? $site_name : wa()->accountName();
        return $site_name;
    }

    /**
     * @return null|string
     */
    public function getApp()
    {
        $auth_apps = $this->getAuthApps();
        if (isset($this->config['app']) && isset($auth_apps[$this->config['app']])) {
            return $this->config['app'];
        } else {
            $app = end($auth_apps);
            return $app ? $app['id'] : null;
        }
    }

    public function setApp($app)
    {
        $auth_apps = $this->getAuthApps();
        $auth_app_ids = array_keys($auth_apps);
        $this->setVariant('app', $app, $auth_app_ids);
    }

    /**
     * @return bool
     */
    public function getSignUpCaptcha()
    {
        return $this->getBoolValue('signup_captcha');
    }

    public function setSignUpCaptcha($enable = true)
    {
        $this->setBoolValue('signup_captcha', $enable);
    }

    /**
     * @return bool
     */
    public function getRememberMe()
    {
        return $this->getBoolValue('rememberme');
    }

    public function setRememberMe($enable = true)
    {
        $this->setBoolValue('rememberme', (bool)$enable);
    }

    /**
     * @return array
     */
    public function getAdapters()
    {
        return $this->getArrayValue('adapters');
    }

    public function setAdapters($adapters)
    {
        $this->setArrayValue('adapters', $adapters);
    }

    public function getAvailableAuthAdapters()
    {
        $this->loadAuthAdapters();
        return $this->auth_adapters;
    }

    /**
     * @return string
     */
    public function getLoginCaption()
    {
        $caption = $this->getScalarValue('login_caption');
        $caption = strlen($caption) > 0 ? $caption : _ws('Email');
        return $caption;
    }

    public function setLoginCaption($caption)
    {
        $this->setScalarValue('login_caption', $caption);
    }

    /**
     * @return string
     */
    public function getLoginPlaceholder()
    {
        return $this->getScalarValue('login_placeholder', _ws('Email'));
    }

    public function setLoginPlaceholder($placeholder)
    {
        $this->setScalarValue('login_placeholder', $placeholder);
    }

    /**
     * @return bool
     */
    public function getSignUpConfirm()
    {
        return $this->getBoolValue('signup_confirm', true);
    }

    public function setSignUpConfirm($enable = true)
    {
        $this->setBoolValue('signup_confirm', (bool)$enable, true);
    }

    /**
     * Parameter ['params']['confirm_email']
     * saved in config for backward compatibility with Old Shop (version <= 7)
     * It must be sync with 'signup_confirm' parameter now
     *
     * @param $signup_confirm_value
     */
    protected function syncOldConfirmEmailParam($signup_confirm_value)
    {
        $params = $this->getParams();
        $params['confirm_email'] = $signup_confirm_value;
        $this->setParams($params);
    }

    /**
     * Need notify about successful singing up or not
     * @return bool
     */
    public function getSignUpNotify()
    {
        return $this->getBoolValue('signup_notify', true);
    }

    public function setSignUpNotify($enable = true)
    {
        $this->setBoolValue('signup_notify', (bool)$enable, true);
    }

    /**
     * @return string
     */
    public function getPriorityAuthMethod()
    {
        return $this->getScalarValue('priority_auth_method');
    }

    public function setPriorityAuthMethod($priority_auth_method)
    {
        $this->setScalarValue('priority_auth_method', $priority_auth_method);
    }

    public function getAuthTypes()
    {
        return array(
            waAuthConfig::AUTH_TYPE_USER_PASSWORD => array(
                'default' => true,
                'name' => _ws('A user enters a password during signup'),
            ),
            waAuthConfig::AUTH_TYPE_GENERATE_PASSWORD => array(
                'name' => _ws('A password is generated during signup and sent in a notification'),
            ),
            waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD => array(
                'name' => _ws('A one-time password (4-digit code) is sent to a user for each login'),
            )
        );
    }

    public function getParams()
    {
        return $this->getArrayValue('params');
    }

    public function setParams($params)
    {
        $this->setArrayValue('params', $params);
    }

    public function getServiceAgreement()
    {
        $params = $this->getParams();
        return isset($params['service_agreement']) ? $params['service_agreement'] : '';
    }

    public function setServiceAgreement($value)
    {
        $params = $this->getParams();
        $params['service_agreement'] = $value;
        $this->setParams($params);
    }

    /**
     * @param null|string[]|string $fields
     *
     * That peace of information that need presented for each app in returned array
     * By default 'id', 'icon', 'name' ('id' is always presented)
     *
     * Also can be $fields === 'all'
     * In that case will be return all available information
     *
     * @return array Array of app array
     */
    public function getAuthApps($fields = null)
    {
        $all_apps = $this->getAllApps();
        $domain_apps = $this->getDomainApps($this->domain);
        $domain_apps_map = array_fill_keys($domain_apps, true);

        if ($fields === null) {
            $fields = array('id', 'icon', 'name');
        } elseif ($fields === 'all') {
            $fields = array('id', 'icon', 'name', 'login_url');
        } elseif (is_array($fields) || is_scalar($fields)) {
            $fields = (array)$fields;
        } else {
            $fields = array('id');
        }

        $fields = array_fill_keys($fields, true);

        $auth_apps = array();
        foreach ($all_apps as $app_id => $app) {
            if (isset($app['frontend']) && !empty($app['auth']) && isset($domain_apps_map[$app_id])) {
                $app_info = array(
                    'id' => $app_id,
                    'icon' => '',
                    'name' => '',
                    'login_url' => ''
                );
                if (!empty($fields['icon'])) {
                    $app_info['icon'] = $app['icon'];
                }
                if (!empty($fields['name'])) {
                    $app_info['name'] = $app['name'];
                }
                if (!empty($fields['login_url'])) {
                    $app_info['login_url'] = wa()->getRouteUrl($app_id.'/login', array('domain' => $this->domain), true);
                }
                $auth_apps[$app_id] = $app_info;
            }
        }
        return $auth_apps;
    }

    /**
     * @param $type 'set' | 'get'
     * @param null|string $key
     * @param string $ns 'all','login','signup'
     * @return mixed
     */
    protected function getMethodByKey($type, $key = null, $ns = 'all')
    {
        static $methods;

        if ($methods === null) {
            $keys = array(
                'auth'                      => array('login', 'signup'),
                'app'                       => array('login', 'signup'),
                'rememberme'                => array('login', 'signup'),
                'adapters'                  => array('login', 'signup'),
                'auth_type'                 => array('login', 'signup'),
                'timeout'                   => array('login', 'signup'),
                'recovery_password_timeout' => array('login', 'signup'),
                'onetime_password_timeout'  => array('login', 'signup'),
                'confirmation_code_timeout' => array('login', 'signup'),
                'login_captcha'             => array('login'),
                'login_placeholder'         => array('login'),
                'login_caption'             => array('login'),
                'signup_confirm'            => array('signup'),
                'signup_notify'             => array('signup'),
                'signup_captcha'            => array('signup'),
                'combine_email_and_phone'   => array('login', 'signup'),
                'verification_channel_ids'  => array('login', 'signup'),
                'fields'                    => array('signup'),
                'params'                    => array('signup'),
                'used_auth_methods'         => array('login', 'signup'),
                'priority_auth_method'      => array('login', 'signup'),
                'can_login_by_contact_login' => array('login')
            );
            $methods = array();
            foreach ($keys as $k => $nss) {
                $get_method = array('get');
                $set_method = array('set');
                $k_parts = explode('_', $k);
                foreach ($k_parts as $k_part) {
                    $k_part = ucfirst($k_part);
                    if ($k_part === 'Signup') {
                        $k_part = 'SignUp';
                    }
                    if ($k_part === 'Rememberme') {
                        $k_part = 'RememberMe';
                    }
                    $get_method[] = $k_part;
                    $set_method[] = $k_part;
                }
                $get_method = join('', $get_method);
                if (method_exists($this, $get_method)) {
                    $methods[$k]['get'] = array($get_method, $nss);
                }
                $set_method = join('', $set_method);
                if (method_exists($this, $set_method)) {
                    $methods[$k]['set'] = array($set_method, $nss);
                }
            }
        }
        $type = $type === 'get' ? 'get' : 'set';
        if (!in_array($ns, array('all', 'login', 'signup'))) {
            $ns = 'all';
        }
        if ($key === null) {
            $result = array();
            foreach (waUtils::getFieldValues($methods, $type, true) as $index => $pack) {
                list($method, $nss) = $pack;
                if ($ns === 'all' || in_array($ns, $nss)) {
                    $result[$index] = $method;
                }
            }
            return $result;
        } else {
            $pack = isset($methods[$key][$type]) ? $methods[$key][$type] : null;
            if ($pack) {
                list($method, $nss) = $pack;
                if ($ns === 'all' || in_array($ns, $nss)) {
                    return $method;
                }
            }
            return null;
        }
    }

    public function commit()
    {
        $this->ensureConsistency();
        // It must be here
        $this->syncOldConfirmEmailParam($this->getSignUpConfirm());
        return $this->commitIntoFile();

    }

    protected function commitIntoFile()
    {
        $config = wa()->getConfig()->getAuth();
        $config[$this->original_domain] = $this->config;
        return wa()->getConfig()->setAuth($config);
    }

    /**
     * @param array $params
     * @param bool $absolute
     * @return null|string
     */
    public function getSignUpUrl($params = array(), $absolute = false)
    {
        return $this->getAuthControllerUrl('signup', $params, $absolute);
    }

    /**
     * @param array $params
     * @param bool $absolute
     * @return null|string
     */
    public function getForgotPasswordUrl($params = array(), $absolute = false)
    {
        return $this->getAuthControllerUrl('forgotpassword', $params, $absolute);
    }

    public function getSendOneTimePasswordUrl($params = array(), $absolute = false)
    {
        $get = 'send_onetime_password=1';
        if (isset($params['get'])) {
            $params['get'] = $this->mergeGetParams($params['get'], $get);
        } else {
            $params['get'] = $get;
        }
        return $this->getLoginUrl($params);
    }

    /**
     * @param array $params
     * @param bool $absolute
     * @return null|string
     */
    public function getLoginUrl($params = array(), $absolute = false)
    {
        return $this->getAuthControllerUrl('login', $params, $absolute);
    }

    public function getRecoveryPasswordUrl($params = array(), $absolute = false)
    {
        return $this->getAuthControllerUrl('forgotpassword', $params, $absolute);
    }

    protected function getAuthControllerUrl($url, $params = array(), $absolute = false)
    {
        $auth_app = $this->getApp();

        $domain = null;
        if ($absolute) {
            $domain = $this->domain;
        }

        $path = $auth_app . '/' . ltrim(trim($url), '/');
        $url = wa()->getRouteUrl($path, $params, $absolute, $domain);
        return $this->buildUrl($url, is_array($params) ? ifset($params['get']) : null);
    }

    /**
     * TODO: move part of this method to parent class
     */
    protected function ensureConsistency()
    {
        $this->ensureAuthAppConsistency();
        $this->ensureVerificationChannelIdsConsistency();
        $this->ensureFieldsConsistency();
        $this->ensureSignupNotifyConsistency();
    }

    /**
     * TODO: move to parent class & make channel_ids depends on used_auth_method
     */
    protected function ensureVerificationChannelIdsConsistency()
    {
        $this->ensureChannelExists();

        $channel_ids = $this->getVerificationChannelIds();

        $vcm = new waVerificationChannelModel();
        $channels = $vcm->getChannels($channel_ids);

        $types = array();   // <type> => count
        foreach ($channels as $channel_id => $channel) {
            $type = $channel['type'];
            $types[$type] = (int)ifset($types[$type]);
            $types[$type]++;
            if ($types[$type] > 1) {
                unset($channels[$channel_id]);
            }
        }

        usort($channels, wa_lambda('$a, $b', 'return strcmp($a["type"], $b["type"]);'));

        $channel_ids = waUtils::getFieldValues($channels, 'id');
        $this->setArrayValue('verification_channel_ids', $channel_ids);
    }

    protected function ensureAuthAppConsistency()
    {
        if (!$this->getApp() || !$this->getAuth()) {
            $this->unsetKey('auth');
            $this->unsetKey('app');
        }
    }

    protected function ensureFieldsConsistency()
    {
        $used_auth_methods = $this->getUsedAuthMethods();

        $fields = $this->getFields();
        $this->loadAvailableFields();

        // Throw away not available fields
        $changed = false;
        foreach ($fields as $field_id => $field) {
            if (!isset($this->available_fields[$field_id])) {
                unset($fields[$field_id]);
                $changed = true;
            }
        }
        // Save updated fields (with now available fields)
        if ($changed) {
            $this->setArrayValue('fields', $fields);
        }


        $sms_used = in_array(self::AUTH_METHOD_SMS, $used_auth_methods, true);
        $email_used = in_array(self::AUTH_METHOD_EMAIL, $used_auth_methods, true);

        if ($email_used) {

            // Email field MUST BE presented
            if (!isset($fields['email'])) {
                $fld = $this->available_fields['email'];
                $fields['email'] = array(
                    'caption' => $fld->getName(),
                );
            }

            // Email field MUST BE required
            if (!$sms_used) {
                $fields['email']['required'] = true;
            }

        }

        if ($sms_used) {

            // Phone field MUST BE presented
            if (!isset($fields['phone'])) {
                $fld = $this->available_fields['phone'];
                $fields['phone'] = array(
                    'caption' => $fld->getName(),
                );
            }

            // Phone field MUST BE required
            if (!$email_used) {
                $fields['phone']['required'] = true;
            }
        }

        // SMS method used => Phone field MUST BE presented
        if ($sms_used && !isset($fields['phone'])) {
            $fld = $this->available_fields['phone'];
            $fields['phone'] = array(
                'caption' => $fld->getName(),
            );
        }

        foreach ($this->must_have_fields as $field_id) {
            if (!isset($fields[$field_id])) {
                $fields[$field_id] = array();
            }
            $fields[$field_id]['required'] = true;
        }

        foreach ($this->must_not_have_fields as $field_id) {
            if (isset($fields[$field_id])) {
                unset($fields[$field_id]);
            }
        }

        $this->setArrayValue('fields', $fields);

    }

    /**
     *
     */
    protected function ensureSignUpNotifyConsistency()
    {
        if ($this->getAuthType() === waAuthConfig::AUTH_TYPE_GENERATE_PASSWORD) {
            $this->setBoolValue('signup_notify', true);
        }
    }

    /**
     * Get fields that selected for current SIGN-UP form
     *
     * @return array
     *   Array of <field> indexed by <field_id>
     *   <field> is array of params. Each param is optional
     *     - bool 'required'
     *     - string 'caption'
     *     - string 'placeholder'
     */
    public function getFields()
    {
        // load default fields if needed
        $this->loadDefaultFields();
        $config_fields = $this->getArrayValue('fields');

        $fields = array();
        foreach ($config_fields as $field_id => $params) {
            if (!is_scalar($field_id)) {
                continue;
            }
            if (wa_is_int($field_id)) { // separator
                $fields[] = '';
                continue;
            }
            $fields[$field_id] = $params;
        }

        return $fields;
    }

    /**
     * If field presents in sign-up form will be return array (even if empty)
     * @param $field_id
     * @return mixed|null
     */
    public function getField($field_id)
    {
        $fields = $this->getFields();
        return isset($fields[$field_id]) ? $fields[$field_id] : null;
    }

    /**
     * Get fields that selected & required for current SIGN-UP form
     * @see getFields
     * @return array
     */
    public function getRequiredFields()
    {
        $fields = $this->getFields();
        $required = array();
        foreach ($fields as $field_id => $field) {
            if (!empty($field['required'])) {
                $required[$field_id] = $field;
            }
        }
        return $required;
    }

    /**
     * Load default fields if needed into config 'fields' var
     */
    protected function loadDefaultFields()
    {
        $config_fields = $this->getArrayValue('fields');
        if ($config_fields) {
            return;
        }
        $this->loadAvailableFields();
        $config_fields = array();
        foreach ($this->default_fields as $field_id) {
            if (isset($this->available_fields[$field_id])) {
                $fld = $this->available_fields[$field_id];
                $config_fields[$field_id] = array(
                    'caption' => $fld->getName(),
                );
            }
        }
        $this->setArrayValue('fields', $config_fields);
    }

    /**
     * Set fields that will be selected for current SIGN-UP form
     * @see getFields()
     * @param array $fields
     * @throws waException
     */
    public function setFields($fields)
    {
        $fields = is_array($fields) ? $fields : array();

        $config_fields = array();
        foreach ($fields as $field_id => $field) {
            $config_fields[$field_id] = $field;
        }

        $this->loadAvailableFields();

        $this->setArrayValue('fields', $config_fields);
    }

    /**
     * Get all available fields
     * They will be rendered in left sidebar in form constructor in UI
     * @return array
     */
    public function getAvailableFields()
    {
        // yes, it is correct - loadEnableFields. Cause of side-effect
        $this->loadEnableFields();
        return $this->available_fields;
    }

    /**
     * Get enable fields
     * They will be rendered in right sidebar in form constructor in UI
     * @return array
     */
    public function getEnableFields()
    {
        $this->loadEnableFields();
        return $this->enable_fields;
    }

    /**
     * @param $field_id
     * @see getEnableFields()
     * @return mixed|null
     */
    public function getEnableField($field_id)
    {
        $fields = $this->getEnableFields();
        return isset($fields[$field_id]) ? $fields[$field_id] : null;
    }

    /**
     * Return channels info arrays (not objects)
     * Take into account own config priority ( @see getPriorityAuthMethod )
     *
     * @see waAuthConfig::getVerificationChannels()
     * @see waAuthConfig::getVerificationChannelIds()
     *
     * @param null $priority_type
     * @return array
     */
    public function getVerificationChannels($priority_type = null)
    {
        if ($priority_type === null) {
            $result = $this->getPriorityAuthMethod();
            if ($result === self::AUTH_METHOD_SMS) {
                $priority_type = waVerificationChannelModel::TYPE_SMS;
            } else {
                $priority_type = waVerificationChannelModel::TYPE_EMAIL;
            }
        }
        return parent::getVerificationChannels($priority_type);
    }

    /**
     * Load all available fields
     * @throws waException
     */
    protected function loadAvailableFields()
    {
        if ($this->available_fields !== null) {
            return;
        }
        // load available fields
        $this->available_fields = waContactFields::getAll('person', true);
        $this->available_fields['password'] = new waContactPasswordField('password', 'Password');
    }

    /**
     * IMPORTANT: with side effect
     */
    protected function loadEnableFields()
    {
        if ($this->enable_fields !== null) {
            return;
        }

        $this->loadAvailableFields();
        $available_fields = $this->available_fields;

        // load enable fields
        // NOTICE: side-effect - available fields will be touched

        $enable_fields = array();
        $unset_fields = array(
            'name'
        );

        $config_fields = $this->getFields();

        $separators = 0;
        foreach ($config_fields as $field_id => $field) {
            if (!is_array($field)) {
                $field_id = $field;
                if (!$field_id) {   // '' means separator
                    $field_id = $separators++;
                }
            }
            $enable_fields[$field_id] = $field;
        }

        foreach ($available_fields as $field_name => $field) {
            $name = $field->getName();
            if ($name && !in_array($field_name, $unset_fields)) {
                $checked = array_key_exists($field_name, $enable_fields);
                $available_fields[$field_name] = array(
                    'id'           => $field_name,
                    'name'         => $name,
                    'checked'      => $checked,
                    'disabled'     => false,
                    'is_composite' => $field instanceof waContactCompositeField
                );
            } else {
                unset($available_fields[$field_name]);
            }
        }
        $enable_fields = array_merge_recursive($enable_fields, $available_fields);
        $this->enable_fields = $enable_fields;
        $this->available_fields = $available_fields;
    }

    protected function loadAuthAdapters()
    {
        if ($this->auth_adapters !== null) {
            return;
        }

        $path = wa()->getConfig()->getPath('system').'/auth/adapters/';
        $dh = opendir($path);
        $this->auth_adapters = array();
        while (($f = readdir($dh)) !== false) {
            if ($f === '.' || $f === '..' || is_dir($path.$f)) {
                continue;
            } elseif (substr($f, -14) == 'Auth.class.php') {
                require_once($path.$f);
                $id = substr($f, 0, -14);
                $class_name = $id."Auth";
                $this->auth_adapters[$id] = new $class_name(array('app_id' => '', 'app_secret' => ''));
            }
        }
        closedir($dh);
    }

    protected function getDomainApps($domain)
    {
        $all_apps = $this->getAllApps();
        $domain_apps = array();
        $routes = wa()->getRouting()->getRoutes($domain);
        foreach ($routes as $route) {
            if (isset($route['app']) && isset($all_apps[$route['app']])) {
                $domain_apps[] = $route['app'];
            }
        }
        return array_unique($domain_apps);
    }

    protected function getAllApps()
    {
        return wa()->getApps();
    }

    /**
     * Can log in (auth) by wa_contact.login field getter
     * @return bool
     */
    public function getCanLoginByContactLogin()
    {
        return $this->getBoolValue('can_login_by_contact_login', true);
    }

    /**
     * Can log in (auth) by wa_contact.login field setter
     * @param bool $enabled
     */
    public function setCanLoginByContactLogin($enabled)
    {
        $this->setBoolValue('can_login_by_contact_login', $enabled, true);
    }

    /**
     * Array of fields by which we can log in
     * Consume by waAuth
     * @see waAuth
     * @return string[] Array of waAuth::LOGIN_FIELD_* constact
     */
    public function getLoginFieldIds()
    {
        $field_ids = array();
        $used_method = $this->getUsedAuthMethods();
        $used_method_map = array_fill_keys($used_method, true);
        if (!empty($used_method_map[self::AUTH_METHOD_EMAIL])) {
            $field_ids[] = waAuth::LOGIN_FIELD_EMAIL;
        }
        if (!empty($used_method_map[self::AUTH_METHOD_SMS])) {
            $field_ids[] = waAuth::LOGIN_FIELD_PHONE;
        }
        if ($this->getCanLoginByContactLogin()) {
            $field_ids[] = waAuth::LOGIN_FIELD_LOGIN;
        }
        return $field_ids;
    }
}
