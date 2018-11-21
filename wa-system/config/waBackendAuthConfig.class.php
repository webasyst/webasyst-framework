<?php

class waBackendAuthConfig extends waAuthConfig
{
    protected $config;

    /**
     * @var waBackendAuthConfig
     */
    protected static $instance = null;

    protected function __construct()
    {
        $this->initConfigData();
    }

    /**
     * @return waBackendAuthConfig
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            self::$instance->ensureChannelExists();
        }
        return self::$instance;
    }

    protected function initConfigData()
    {
        $this->config = wa()->getBackendAuthConfig();
    }

    public function getVerificationChannelIds()
    {
        $this->ensureChannelExists();
        return parent::getRawVerificationChannelIds();
    }

    public function getAuthTypes()
    {
        return array(
            waAuthConfig::AUTH_TYPE_USER_PASSWORD    => array(
                'default' => true,
                'name'    => _ws('Permanent user password'),
            ),
            waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD => array(
                'name' => _ws('One-time password (4-digit code)'),
            )
        );
    }

    /**
     * @return bool
     */
    public function getAuth()
    {
        return true;
    }

    /**
     * @param $type 'set' | 'get'
     * @param null|string $key
     * @return mixed
     */
    protected function getMethodByKey($type, $key = null)
    {
        static $methods;

        if ($methods === null) {
            $keys = array(
                'auth_type',
                'recovery_password_timeout',
                'onetime_password_timeout',
                'confirmation_code_timeout',
                'timeout',
                'login_caption',
                'login_captcha',
                'login_placeholder',
                'verification_channel_ids',
                'used_auth_methods'
            );
            $methods = array();
            foreach ($keys as $k) {
                $get_method = array('get');
                $set_method = array('set');
                $k_parts = explode('_', $k);
                foreach ($k_parts as $k_part) {
                    $k_part = ucfirst($k_part);
                    $get_method[] = $k_part;
                    $set_method[] = $k_part;
                }
                $get_method = join('', $get_method);
                if (method_exists($this, $get_method)) {
                    $methods[$k]['get'] = $get_method;
                }
                $set_method = join('', $set_method);
                if (method_exists($this, $set_method)) {
                    $methods[$k]['set'] = $set_method;
                }
            }
        }

        $type = $type === 'get' ? 'get' : 'set';
        if ($key === null) {
            return waUtils::getFieldValues($methods, $type, true);
        } else {
            return isset($methods[$key][$type]) ? $methods[$key][$type] : null;
        }
    }

    public function commit()
    {
        return wa()->getConfig()->setBackendAuth($this->config);
    }

    public function getRecoveryPasswordUrl($params = array(), $absolute = false)
    {
        $get = 'forgotpassword=1';
        if (isset($params['get'])) {
            $params['get'] = $this->mergeGetParams($params['get'], $get);
        } else {
            $params['get'] = $get;
        }
        return $this->getBackendLoginUrl($params);
    }

    public function getForgotPasswordUrl($params = array(), $absolute = false)
    {
        $get = 'forgotpassword=1';
        if (isset($params['get'])) {
            $params['get'] = $this->mergeGetParams($params['get'], $get);
        } else {
            $params['get'] = $get;
        }
        return $this->getBackendLoginUrl($params);
    }

    public function getLoginUrl($params = array(), $absolute = false)
    {
        return $this->getBackendLoginUrl();
    }

    public function getSendOneTimePasswordUrl($params = array(), $absolute = false)
    {
        $get = 'send_onetime_password=1';
        if (isset($params['get'])) {
            $params['get'] = $this->mergeGetParams($params['get'], $get);
        } else {
            $params['get'] = $get;
        }
        return $this->getBackendLoginUrl($params);
    }

    public function getSignupUrl($params = array(), $absolute = false)
    {
        return null;
    }

    protected function getBackendLoginUrl($params = array())
    {
        $url = wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl(false) . '/';
        return $this->buildUrl($url, is_array($params) ? ifset($params['get']) : null);
    }

    /**
     * @return bool
     */
    public function getSignupConfirm()
    {
        return false;
    }

    public function getSiteUrl()
    {
        return $this->getBackendLoginUrl();
    }

    public function getSiteName()
    {
        return wa()->accountName();
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
        $field_ids[] = waAuth::LOGIN_FIELD_LOGIN;
        return $field_ids;
    }
}
