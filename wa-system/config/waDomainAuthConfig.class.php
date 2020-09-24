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

    /**
     * Map of that ensure<something>Consistency methods that had been called already
     * Call every "heavy" ensure<something>Consistency method on demand and only 1 time
     * @var $array
     */
    protected $ensure_consistency;

    protected $auth_adapters;

    /**
     * waDomainAuthConfig constructor.
     * @param null|string $domain If null, use current domain
     */
    protected function __construct($domain)
    {
        $this->domain = $domain;

        $alias = $this->getRouting()->isAlias($domain);
        if ($alias) {
            $this->original_domain = $alias;
        } else {
            $this->original_domain = $this->domain;
        }

        if ($this->config === null) {
            $this->config = wa()->getAuthConfig($this->original_domain);
        }

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
        if (waConfig::get('is_template')) {
            return null;
        }
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

            // TODO: optimize this call
            $config->ensureVerificationChannelIdsConsistency();

            $config->ensureSignupNotifyConsistency();

        }
        return self::$static_cache['instances'][$domain];
    }

    /**
     * Get current domain with or not 'Internationalized Domain Names' converting (Punycode)
     *
     * Domain is technical entity.
     * By domain saved routes in routes.php config
     * So by default getDomain get raw domain (without IDN converting)
     *
     * @param bool $idn_convert. Default is FALSE
     * @return string|null
     */
    public function getDomain($idn_convert = false)
    {
        return $idn_convert ? waIdna::dec($this->domain) : $this->domain;
    }

    /**
     * Get original domain (cause getDomain may be mirror)
     * with or not 'Internationalized Domain Names' converting (Punycode)
     *
     * Domain is technical entity.
     * By domain saved routes in routes.php config
     * So by default getDomain get raw domain (without IDN converting)
     *
     * @param bool $idn_convert. Default is FALSE
     * @return string|null
     */
    public function getOriginalDomain($idn_convert = false)
    {
        return $idn_convert ? waIdna::dec($this->original_domain) : $this->original_domain;
    }

    /**
     * Get site url - how looks like main entry point of site
     * It is domain itself but IDN converting is TRUE, cause siteUrl is application entity
     * it use in email/sms messages and UI views
     * @return string|null
     */
    public function getSiteUrl()
    {
        return $this->getDomain(true);
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
        $route_url = $this->getRouteUrl();
        $routes = $this->getAuthRoutes();
        if (isset($routes[$route_url])) {
            return $routes[$route_url]['app'];
        }
        return '';
    }

    public function getRouteUrl()
    {
        $routes = $this->getAuthRoutes();

        if (!isset($this->config['route_url']) || !isset($routes[$this->config['route_url']]) || empty($routes[$this->config['route_url']])) {

            // try define by app saved in config
            $this->config['route_url'] = null;
            $auth_apps = $this->getAuthApps();
            if (isset($this->config['app']) && !empty($auth_apps[$this->config['app']])) {
                $this->config['route_url'] = $this->getRouteUrlByApp($this->config['app']);
            }

            // so get last route - cause last is more common rule
            if (!$this->config['route_url'] || empty($routes[$this->config['route_url']])) {
                $route = end($routes);
                $this->config['route_url'] = $route ? $route['url'] : '';
            }
        }

        return $this->config['route_url'];
    }

    public function setRouteUrl($route_url)
    {
        $routes = $this->getAuthRoutes();
        if (isset($routes[$route_url])) {
            $this->config['route_url'] = $route_url;
            $this->config['app'] = $routes[$route_url]['app'];
        }
    }

    protected function getRouteUrlByApp($app_id)
    {
        if ($app_id === null) {
            return '';
        }
        $app_login_url = wa()->getRouteUrl($app_id.'/login', array('domain' => $this->domain), true);
        $endpoints = $this->getAuthEndpoints();
        foreach ($endpoints as $route_url => $endpoint) {
            if ($endpoint['app']['id'] === $app_id && $endpoint['login_url'] === $app_login_url) {
                return $route_url;
            }
        }
        return '';
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
     * Placeholder for input 'login' for Login form
     * @return string
     */
    public function getLoginPlaceholder()
    {
        return $this->getScalarValue('login_placeholder', _ws('Email'));
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
        $this->setBoolValue('signup_confirm', (bool)$enable);
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
        $this->setBoolValue('signup_notify', (bool)$enable);
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
     * Get that apps that responsible for authorization and settled in current domain
     * @return array Array of app array
     */
    protected function getAuthApps()
    {
        if (isset(self::$static_cache['auth_apps'][$this->domain])) {
            return self::$static_cache['auth_apps'][$this->domain];
        }

        $all_apps = $this->getAllApps();
        $domain_apps = $this->getDomainApps($this->domain);
        $domain_apps_map = array_fill_keys($domain_apps, true);

        $auth_apps = array();
        foreach ($all_apps as $app_id => $app) {
            if (isset($app['frontend']) && !empty($app['auth']) && isset($domain_apps_map[$app_id])) {
                $app_info = array(
                    'id' => $app_id,
                    'icon' => $app['icon'],
                    'name' => $app['name']
                );
                $auth_apps[$app_id] = $app_info;
            }
        }
        self::$static_cache['auth_apps'][$this->domain] = $auth_apps;
        return $auth_apps;
    }

    /**
     * Get authorizing endpoints indexed by <route_url>
     *
     * Endpoint is info array hold app info and urls of login and signup
     *
     * @return array
     *   - array 'app' app info array
     *   - string 'login_url' url of login page
     *   - string 'signup_url' url of signup page
     */
    public function getAuthEndpoints()
    {
        if (isset(self::$static_cache['auth_endpoints'][$this->domain])) {
            return self::$static_cache['auth_endpoints'][$this->domain];
        }

        $endpoints = array();
        $auth_apps = $this->getAuthApps();
        $routing = $this->getRouting();
        $routes = $this->getAuthRoutes();

        $old_route = $routing->getRoute();
        $old_domain = $routing->getDomain();

        foreach ($routes as $route) {

            // app info
            $app_id = $route['app'];
            if (!isset($auth_apps[$app_id])) {
                continue;
            }

            $routing->setRoute($route, $this->domain);

            $login_url = $routing->getUrl($app_id.'/login', array('domain' => $this->domain), true);
            $signup_url = $routing->getUrl($app_id.'/signup', array('domain' => $this->domain), true);

            $endpoints[$route['url']] = array(
                'app'        => $auth_apps[$app_id],
                'login_url'  => $login_url,
                'signup_url' => $signup_url
            );
        }

        // restore current route rule
        $routing->setRoute($old_route, $old_domain);

        self::$static_cache['auth_endpoints'][$this->domain] = $endpoints;

        return $endpoints;
    }

    /**
     * @return waRouting
     */
    protected function getRouting()
    {
        return wa()->getRouting();
    }

    protected function getAuthRoutes()
    {
        if (isset(self::$static_cache['auth_routes'][$this->domain])) {
            return self::$static_cache['auth_routes'][$this->domain];
        }

        $auth_apps = $this->getAuthApps();
        $auth_routes = array();
        $routes = $this->getRouting()->getRoutes($this->domain);
        foreach ($routes as $route) {
            if (isset($route['app']) && isset($auth_apps[$route['app']])) {
                $auth_routes[$route['url']] = $route;
            }
        }

        self::$static_cache['auth_routes'][$this->domain] = $auth_routes;

        return $auth_routes;
    }

    /**
     * @param $type 'set' | 'get'
     * @param null|string $key
     * @param string $ns 'all','login','signup'
     * @return mixed
     */
    protected function getMethodByKey($type, $key = null, $ns = 'all')
    {
        if (!isset(self::$static_cache['methods'])) {
            $keys = array(
                'auth'                      => array('login', 'signup'),
                'route_url'                 => array('login', 'signup'),
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
                'password_placeholder'      => array('signup'),
                'signup_confirm'            => array('signup'),
                'signup_notify'             => array('signup'),
                'signup_captcha'            => array('signup'),
                'combine_email_and_phone'   => array('login', 'signup'),
                'verification_channel_ids'  => array('login', 'signup'),
                'fields'                    => array('signup'),
                'params'                    => array('signup'),
                'used_auth_methods'         => array('login', 'signup'),
                'priority_auth_method'      => array('login', 'signup'),
                'can_login_by_contact_login' => array('login'),
                'phone_transform_prefix'     => array('signup', 'login'),
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
        // TODO: optimize this call
        $this->ensureVerificationChannelIdsConsistency();

        $this->ensureFieldsConsistency(true);

        $this->ensureSignupNotifyConsistency();

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
        return $this->getLoginUrl($params, $absolute);
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

        $route_url = $this->getRouteUrl();
        $routes = $this->getAuthRoutes();

        $routing = $this->getRouting();

        $old_route = null;
        $new_route = isset($routes[$route_url]) ? $routes[$route_url] : null;
        $old_domain = null;

        if ($new_route !== null) {
            $old_route = $routing->getRoute();
            $old_domain = $routing->getDomain();
            $routing->setRoute($routes[$route_url], $this->domain);
        }

        $path = $auth_app . '/' . ltrim(trim($url), '/');

        $params['domain'] = $this->domain;
        $url = $routing->getUrl($path, $params, $absolute);

        $url = $this->buildUrl($url, is_array($params) ? ifset($params['get']) : null);

        // Convert international domain names
        if ($absolute) {
            $url = waIdna::dec($url);
        }

        if ($new_route !== null) {
            $routing->setRoute($old_route, $old_domain);
        }

        return $url;
    }

    /**
     * TODO: move to parent class & make channel_ids depends on used_auth_method
     */
    protected function ensureVerificationChannelIdsConsistency()
    {
        $this->ensureChannelExists();

        $channel_ids = $this->getVerificationChannelIds();

        $vcm = $this->getVerificationChannelModel();
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

    /**
     * Ensure that array of fields in config is consistent:
     *  - there are not unavailable fields
     *  - array of fields is not empty
     *  - email/phone existing and required if proper channel is activated
     *
     * Call only one time for instance unless $force passed
     *
     * @param bool $force
     */
    protected function ensureFieldsConsistency($force = false)
    {
        if (!$force && !empty($this->ensure_consistency['fields'])) {
            return;
        }

        $this->ensure_consistency['fields'] = true;

        $used_auth_methods = $this->getUsedAuthMethods();

        // load available fields
        $this->loadAvailableFields();

        // load default fields
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
        $this->ensureFieldsConsistency();
        return $this->getArrayValue('fields');
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

        $this->auth_adapters[waWebasystIDAuthAdapter::PROVIDER_ID] = new waWebasystIDSiteAuth(['app_id' => '', 'app_secret' => '']);
    }

    protected function getDomainApps($domain)
    {
        $all_apps = $this->getAllApps();
        $domain_apps = array();
        $routes = $this->getRouting()->getRoutes($domain);
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
        $this->setBoolValue('can_login_by_contact_login', $enabled);
    }


    /**
     * Transform phone by rules for specified (or all) domains
     * @param $phone
     * @param bool $is_reverse
     * @param null|array $domains - NULL - for all domains, otherwise for specified domains
     * @return array
     * @throws waException
     */
    public static function transformPhonePrefixForDomains($phone, $is_reverse = false, $domains = null)
    {
        if ($domains !== null) {
            $domains = waUtils::toStrArray($domains);
            $domains = array_fill_keys($domains, true);
        }

        // auth config by all domains
        $auth_config = wa()->getConfig()->getAuth();

        // collect options for phone transformation for all domains
        $domain_transform_options = array();
        foreach ($auth_config as $domain => $config) {
            if ($domains === null || !empty($domains[$domain])) {
                $config = is_array($config) ? $config : array();
                $phone_transform_prefix = ifset($config['phone_transform_prefix']);
                $phone_transform_prefix = is_array($phone_transform_prefix) ? $phone_transform_prefix : array();
                $domain_transform_options[$domain] = $phone_transform_prefix;
            }
        }

        // apply transformations and collect results
        $results = array();
        foreach ($domain_transform_options as $domain => $options) {
            $options['is_reverse'] = $is_reverse;
            $result = parent::transformPhonePrefix($phone, $options);
            $results[$domain] = $result;
        }

        return $results;
    }
}
