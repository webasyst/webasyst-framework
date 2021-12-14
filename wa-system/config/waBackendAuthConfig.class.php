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
            self::$instance->ensureVerificationChannelIdsConsistency();
        }
        return self::$instance;
    }

    protected function initConfigData()
    {
        $this->config = wa()->getBackendAuthConfig();
    }

    protected function ensureVerificationChannelIdsConsistency()
    {
        $this->ensureChannelExists();

        $channel_ids = $this->getRawVerificationChannelIds();

        $vcm = $this->getVerificationChannelModel();
        $channels = $vcm->getChannels($channel_ids);

        $email_type_present = false;
        foreach ($channels as $channel) {
            if ($channel['type'] === waVerificationChannelModel::TYPE_EMAIL) {
                $email_type_present = true;
                break;
            }
        }

        if (!$email_type_present) {
            $channel = $vcm->getDefaultSystemEmailChannel();
            $channel_ids[] = isset($channel['id']) ? $channel['id'] : null;
            $this->setRawVerificationChannelIds($channel_ids);
        }
    }

    public function getVerificationChannelIds()
    {
        $this->ensureVerificationChannelIdsConsistency();
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
                'password_placeholder',
                'verification_channel_ids',
                'used_auth_methods',
                'rememberme',
                'phone_transform_prefix',
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
        $this->ensureChannelExists();
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
    public function getSignUpConfirm()
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
     * Always can
     * @return mixed
     */
    public function getCanLoginByContactLogin()
    {
        return true;
    }

    /**
     * Placeholder for input 'login' for Login form
     * @return string
     */
    public function getLoginPlaceholder()
    {
        return $this->getScalarValue('login_placeholder', _ws('Login or email'));
    }

    /**
     * Placeholder for input 'password' for Login form
     * @return string
     */
    public function getPasswordPlaceholder()
    {
        return $this->getScalarValue('password_placeholder', _ws('Password'));
    }

    /**
     * See parent description, especially in light of invariant
     * @return array
     */
    public function getUsedAuthMethods()
    {
        $methods = parent::getUsedAuthMethods();
        if (!in_array(self::AUTH_METHOD_EMAIL, $methods)) {
            $methods[] = self::AUTH_METHOD_EMAIL;
        }
        return $methods;
    }

    /**
     * @return bool
     */
    public function getRememberMe()
    {
        try {
            $app_settings_model = new waAppSettingsModel();
            $enable = !!$app_settings_model->get('webasyst', 'rememberme', true);
        } catch (waDbException $e) {
            $enable = true;
        }
        return $enable;
    }

    /**
     * @param bool $enable
     */
    public function setRememberMe($enable = true)
    {
        try {
            $app_settings_model = new waAppSettingsModel();
            $app_settings_model->set('webasyst', 'rememberme', $enable ? '1' : '0');
        } catch (waDbException $e) {

        }
    }
}
