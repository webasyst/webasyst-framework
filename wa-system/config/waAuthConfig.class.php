<?php

abstract class waAuthConfig
{
    /**
     * @var array
     */
    protected $config;

    const AUTH_TYPE_USER_PASSWORD = 'user_password';
    const AUTH_TYPE_GENERATE_PASSWORD = 'generate_password';
    const AUTH_TYPE_ONETIME_PASSWORD = 'onetime_password';

    const AUTH_METHOD_EMAIL = 'email';
    const AUTH_METHOD_SMS = 'sms';
    const AUTH_METHOD_SOCIAL = 'social';

    const LOGIN_CAPTCHA_NONE = '';
    const LOGIN_CAPTCHA_ALWAYS = 'always';

    const RECOVERY_PASSWORD_TIMEOUT = 60;
    const CONFIRMATION_CODE_TIMEOUT = 60;
    const ONETIME_PASSWORD_TIMEOUT = 60;

    /**
     * @param null|array $options
     *   string|null 'env' Environment. If skip or NULL get current env
     * @return waApiAuthConfig|waBackendAuthConfig|waDomainAuthConfig|null
     */
    public static function factory($options = null)
    {
        if (waConfig::get('is_template')) {
            return null;
        }

        $options = is_array($options) ? $options : array();

        $env = isset($options['env']) && is_scalar($options['env']) ? $options['env'] : null;
        $env = $env === null ? wa()->getEnv() : $env;

        if ($env === 'backend') {
            return waBackendAuthConfig::getInstance();
        } elseif ($env === 'api') {
            return waApiAuthConfig::getInstance();
        } elseif ($env === 'frontend') {
            return waDomainAuthConfig::factory(isset($options['domain']) ? $options['domain'] : null);
        } else {
            return null;
        }
    }

    /**
     * Ensure that some channel set in config and it exists
     */
    public function ensureChannelExists()
    {
        // The essence of logic is
        // if there is empty array of channels we try use default system email channel by filling this array

        $channel_ids = $this->getRawVerificationChannelIds();
        $empty_channels = empty($channel_ids);

        $vcm = $this->getVerificationChannelModel();

        if (!$empty_channels) {
            $channels = $vcm->getChannels($channel_ids);
            $empty_channels = empty($channels);
        }

        if ($empty_channels) {
            $channel = $vcm->getDefaultSystemEmailChannel();
            $channel_ids[] = isset($channel['id']) ? $channel['id'] : null;
            $this->setRawVerificationChannelIds($channel_ids);
        }
    }

    /**
     * @return bool
     */
    public function isAuthEnabled()
    {
        return $this->getAuth();
    }

    /**
     * @return bool
     */
    public function isAuthDisabled()
    {
        return !$this->isAuthEnabled();
    }

    /**
     * @return bool
     */
    public function getAuth()
    {
        return $this->getBoolValue('auth');
    }

    public function setAuth($enable = true)
    {
        $this->setBoolValue('auth', $enable);
    }

    /**
     * @return bool
     */
    abstract public function getRememberMe();

    /**
     * @param bool $enable
     */
    abstract public function setRememberMe($enable = true);

    /**
     * @return string
     */
    public function getAuthType()
    {
        $types = $this->getAuthTypes();
        $type_ids = array_keys($types);
        $default = $this->getDefaultVariant($types);
        return $this->getVariant('auth_type', $type_ids, $default);
    }

    public function setAuthType($type)
    {
        $types = $this->getAuthTypes();
        $type_ids = array_keys($types);
        $this->setVariant('auth_type', $type, $type_ids);
    }

    /**
     * @return string
     */
    public function getLoginCaptcha()
    {
        $variants = $this->getLoginCaptchaVariants();
        $variant_ids = array_keys($variants);
        $default = $this->getDefaultVariant($variants);
        return $this->getVariant('login_captcha', $variant_ids, $default);
    }

    public function setLoginCaptcha($value)
    {
        $variants = $this->getLoginCaptchaVariants();
        $variant_ids = array_keys($variants);
        $this->setVariant('login_captcha', $value, $variant_ids);
    }

    public function needLoginCaptcha()
    {
        $type = $this->getLoginCaptcha();
        return $type === self::LOGIN_CAPTCHA_ALWAYS;
    }

    /**
     * @return string
     */
    public function getLoginCaption()
    {
        return $this->getScalarValue('login_caption');
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
        return $this->getScalarValue('login_placeholder');
    }

    /**
     * Set placeholder for input 'login' for Login form
     * @param $placeholder
     */
    public function setLoginPlaceholder($placeholder)
    {
        $this->setScalarValue('login_placeholder', $placeholder);
    }

    /**
     * Placeholder for input 'password' for Login form
     * @return string
     */
    public function getPasswordPlaceholder()
    {
        return $this->getScalarValue('password_placeholder');
    }


    /**
     * Set placeholder for input 'password' for Login form
     * @param $placeholder
     */
    public function setPasswordPlaceholder($placeholder)
    {
        $this->setScalarValue('password_placeholder', $placeholder);
    }

    /**
     * IMPORTANT:
     *
     * Return array of ids in config
     * That array is RAW - means looks like in config
     * So BE CAREFUL, cause getVerificationChannels CAN have own logic for temporary OFF some of channels
     *
     * @see getVerificationChannels
     *
     * @return int[]
     */
    public function getVerificationChannelIds()
    {
        return $this->getRawVerificationChannelIds();
    }

    protected function getRawVerificationChannelIds()
    {
        $channel_ids = array();
        if (isset($this->config['verification_channel_ids']) && is_array($this->config['verification_channel_ids'])) {
            $channel_ids = $this->config['verification_channel_ids'];
        }
        $channel_ids = waUtils::toIntArray($channel_ids);
        $channel_ids = waUtils::dropNotPositive($channel_ids);
        return $channel_ids;
    }

    public function setVerificationChannelIds($channel_ids)
    {
        $this->setRawVerificationChannelIds($channel_ids);
    }

    protected function setRawVerificationChannelIds($channel_ids)
    {
        $channel_ids = is_array($channel_ids) ? $channel_ids : array();
        $channel_ids = waUtils::toIntArray($channel_ids);
        $channel_ids = waUtils::dropNotPositive($channel_ids);
        $this->setArrayValue('verification_channel_ids', $channel_ids);
    }

    /**
     * IMPORTANT:
     * Type compatible demands (in other words - MUST BE invariants)
     * waAuthConfig::AUTH_METHOD_EMAIL === waVerificationChannelModel::TYPE_EMAIL
     * waAuthConfig::AUTH_METHOD_SMS === waVerificationChannelModel::TYPE_SMS
     *
     * @return array of waAuthConfig::AUTH_METHOD_EMAIL | waAuthConfig::AUTH_METHOD_SMS | waAuthConfig::AUTH_METHOD_SOCIAL
     */
    public function getUsedAuthMethods()
    {
        $default = array(self::AUTH_METHOD_EMAIL);
        if (!isset($this->config['used_auth_methods'])) {
            return $default;
        }

        $methods = (array)$this->config['used_auth_methods'];

        if (!in_array(self::AUTH_METHOD_EMAIL, $methods) && !in_array(self::AUTH_METHOD_SMS, $methods)) {
            $methods = array_merge($default, $methods);
        }

        return $methods;
    }

    /**
     * @param $auth_methods
     */
    public function setUsedAuthMethods($auth_methods)
    {
        $default = array(self::AUTH_METHOD_EMAIL);

        $auth_methods = (array)$auth_methods;
        if (empty($auth_methods) || (!in_array(self::AUTH_METHOD_EMAIL, $auth_methods) && !in_array(self::AUTH_METHOD_SMS, $auth_methods))) {
            $auth_methods = array_merge($default, $auth_methods);
        }

        $legal_variants = $this->getUsedMethodVariants();
        $legal_variants = array_fill_keys($legal_variants, true);
        foreach ($auth_methods as $index => $auth_method) {
            if (!isset($legal_variants[$auth_method])) {
                unset($auth_methods[$index]);
            }
        }

        $auth_methods = array_values($auth_methods);
        $auth_methods = array_unique($auth_methods);

        $this->setArrayValue('used_auth_methods', $auth_methods);
    }

    /**
     * @return array
     */
    public function getUsedMethodVariants()
    {
        return array(
            self::AUTH_METHOD_EMAIL,
            self::AUTH_METHOD_SMS,
            self::AUTH_METHOD_SOCIAL
        );
    }

    /**
     * Number of attempts to verify code
     * @return int
     */
    public function getVerifyCodeTriesCount()
    {
        return 3;
    }

    /**
     * Return channels info arrays (not objects)
     *
     * @param null|string $priority_type
     *   waVerificationChannelModel::TYPE_* const
     *   Channels of this type will be move to front places of array
     *
     *   ALSO Take into account 'used_methods' setting ( @see getUsedAuthMethods )
     *   Therefore getVerificationChannelIds not compatible with getVerificationChannels ( @see getVerificationChannelIds )
     *   So BE CAREFUL
     *
     *
     * TODO: take into account getAuth() status of authorization
     *
     * @return array
     *   Array indexed by channel type
     */
    public function getVerificationChannels($priority_type = null)
    {
        $channel_ids = $this->getVerificationChannelIds();
        $vcm = $this->getVerificationChannelModel();
        $channels = $vcm->getChannels($channel_ids);

        if ($priority_type && is_scalar($priority_type)) {
            $channels = waUtils::groupBy($channels, 'type');
            $channels = waUtils::orderKeys($channels, $priority_type);
            $channels = $this->flatten2DArray($channels);
        }

        $used_methods = $this->getUsedAuthMethods();
        $used_methods = array_fill_keys($used_methods, true);

        foreach ($channels as $index => $channel) {

            $proper_used_method = null;
            if ($channel['type'] === waVerificationChannelModel::TYPE_SMS) {
                $proper_used_method = self::AUTH_METHOD_SMS;
            } elseif ($channel['type'] === waVerificationChannelModel::TYPE_EMAIL) {
                $proper_used_method = self::AUTH_METHOD_EMAIL;
            }

            // means that type of channel not available temporary
            if (empty($used_methods[$proper_used_method])) {
                unset($channels[$index]);
            }
        }

        return $channels;
    }

    /**
     * @param null|string $priority_type
     *   waVerificationChannelModel::TYPE_* const
     * @see getVerificationChannels
     * @return waVerificationChannel[]
     */
    public function getVerificationChannelInstances($priority_type = null)
    {
        $channels = $this->getVerificationChannels($priority_type);
        foreach ($channels as $channel_id => $channel) {
            $channels[$channel_id] = waVerificationChannel::factory($channel);
        }
        return $channels;
    }

    /**
     * @return null|array
     */
    public function getEmailVerificationChannel()
    {
        return $this->getVerificationChannel(waVerificationChannelModel::TYPE_EMAIL);
    }

    /**
     * @return waVerificationChannel
     */
    public function getEmailVerificationChannelInstance()
    {
        $channel = $this->getEmailVerificationChannel();
        return waVerificationChannel::factory($channel);
    }

    /**
     * @return null|array
     */
    public function getSMSVerificationChannel()
    {
        return $this->getVerificationChannel(waVerificationChannelModel::TYPE_SMS);
    }

    /**
     * @return waVerificationChannel
     */
    public function getSMSVerificationChannelInstance()
    {
        $channel = $this->getVerificationChannel(waVerificationChannelModel::TYPE_SMS);
        return waVerificationChannel::factory($channel);
    }

    /**
     * @param string $type waVerificationChannelModel::TYPE_*
     * @return null|array
     */
    public function getVerificationChannel($type)
    {
        $channels = waUtils::groupBy($this->getVerificationChannels(), 'type', 'first');
        return isset($channels[$type]) ? $channels[$type] : null;
    }

    /**
     * @param string $type waVerificationChannelModel::TYPE_*
     * @return waVerificationChannel
     */
    public function getVerificationChannelInstance($type)
    {
        $channel = $this->getVerificationChannel($type);
        return waVerificationChannel::factory($channel);
    }

    public function getAvailableVerificationChannels()
    {
        return $this->getVerificationChannelModel()->getChannels();
    }

    public function getVerificationChannelTypes()
    {
        return $this->getVerificationChannelModel()->getTypes();
    }

    public function getLoginCaptchaVariants()
    {
        return array(
            waAuthConfig::LOGIN_CAPTCHA_NONE => array(
                'default' => true,
                'name' => _ws('Never require'),
            ),
            waAuthConfig::LOGIN_CAPTCHA_ALWAYS => array(
                'name' => _ws('Require at once'),
            )
        );
    }

    public function getData()
    {
        $data = array();
        $methods = $this->getMethodByKey('get');
        foreach ($methods as $key => $_) {
            $data[$key] = $this->getValue($key);
        }
        return $data;
    }

    public function setData($data)
    {
        $data = is_array($data) ? $data : array();
        foreach ($data as $key => $value) {
            $this->setValue($key, $value);
        }
    }

    public function getValue($key)
    {
        $method = $this->getMethodByKey('get', $key);
        return $method ? call_user_func(array($this, $method)) : null;
    }

    public function setValue($key, $value)
    {
        $method = $this->getMethodByKey('set', $key);
        if ($method) {
            call_user_func_array(array($this, $method), array($value));
        }
    }

    /**
     * In seconds
     * @param int $timeout
     */
    public function setRecoveryPasswordTimeout($timeout)
    {
        $this->setScalarValue('recovery_password_timeout', (int)$timeout);
    }

    /**
     * In seconds
     * @return int
     */
    public function getRecoveryPasswordTimeout()
    {
        $recovery_password_timeout = (int)$this->getScalarValue('recovery_password_timeout');
        if (!$recovery_password_timeout) {
            $recovery_password_timeout = self::RECOVERY_PASSWORD_TIMEOUT;
        }

        return $recovery_password_timeout;
    }

    public function getRecoveryPasswordTimeoutMessage()
    {
        $timeout = $this->getRecoveryPasswordTimeout();
        return $this->formatTimeoutMessage(_ws("You can request password recovery in <strong>%s:%s</strong>."), $timeout);
    }

    public function getRecoveryPasswordTimeoutErrorMessage()
    {
        $timeout = $this->getRecoveryPasswordTimeout();
        return $this->formatTimeoutMessage(_ws("You have been requesting password recovery too frequently. Try again in <strong>%s:%s</strong>."), $timeout);
    }

    public function getOnetimePasswordTimeout()
    {
        $timeout = (int)$this->getScalarValue('onetime_password_timeout');
        if (!$timeout) {
            $timeout = self::ONETIME_PASSWORD_TIMEOUT;
        }
        return $timeout;
    }

    public function getOnetimePasswordTimeoutMessage()
    {
        $timeout = $this->getOnetimePasswordTimeout();
        return $this->formatTimeoutMessage(_ws("You can request a one-time password in <strong>%s:%s</strong>."), $timeout);
    }

    public function getOnetimePasswordTimeoutErrorMessage()
    {
        $timeout = $this->getOnetimePasswordTimeout();
        return $this->formatTimeoutMessage(_ws("You have been requesting a one-time password too frequently. Try again in <strong>%s:%s</strong>."), $timeout);
    }

    /**
     * In seconds
     * @param int $timeout
     */
    public function setOnetimePasswordTimeout($timeout)
    {
        $this->setScalarValue('onetime_password_timeout', (int)$timeout);
    }

    /**
     * In seconds
     * @param int $timeout
     */
    public function setConfirmationCodeTimeout($timeout)
    {
        $this->setScalarValue('confirmation_code_timeout', (int)$timeout);
    }

    /**
     * In seconds
     * @return int
     */
    public function getConfirmationCodeTimeout()
    {
        $confirmation_code_timeout = (int)$this->getScalarValue('confirmation_code_timeout');
        if (!$confirmation_code_timeout) {
            $confirmation_code_timeout = self::CONFIRMATION_CODE_TIMEOUT;
        }

        return $confirmation_code_timeout;
    }

    public function getConfirmationCodeTimeoutMessage()
    {
        $timeout = $this->getConfirmationCodeTimeout();
        return $this->formatTimeoutMessage(_ws("You can request a code in <strong>%s:%s</strong>."), $timeout);
    }

    public function getConfirmationCodeTimeoutErrorMessage()
    {
        $timeout = $this->getOnetimePasswordTimeout();
        return $this->formatTimeoutMessage(_ws("You have been requesting a code too frequently. Try again in <strong>%s:%s</strong>."), $timeout);
    }

    /**
     * In seconds
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->setOnetimePasswordTimeout($timeout);
        $this->setConfirmationCodeTimeout($timeout);
        $this->setRecoveryPasswordTimeout($timeout);
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

    protected function formatTimeoutMessage($message_template, $timeout)
    {
        $minutes = intval($timeout / 60);
        $seconds = $timeout % 60;
        $minutes_str = $minutes <= 9 ? "0{$minutes}" : $minutes;
        $seconds_str = $seconds <= 9 ? "0{$seconds}" : $seconds;
        return sprintf($message_template, $minutes_str, $seconds_str);
    }

    protected function getBoolValue($name, $default = false)
    {
        return isset($this->config[$name]) ? !!$this->config[$name] : (bool)$default;
    }

    protected function setBoolValue($name, $value)
    {
        $value = (bool)$value;
        $this->config[$name] = $value;
    }

    protected function getArrayValue($name)
    {
        return isset($this->config[$name]) && is_array($this->config[$name]) ? $this->config[$name] : array();
    }

    protected function setArrayValue($name, $value)
    {
        $value = is_array($value) ? $value : array();
        if (!empty($value)) {
            $this->config[$name] = $value;
        } else {
            $this->unsetKey($name);
        }
    }

    protected function getScalarValue($name, $default = '')
    {
        $value = isset($this->config[$name]) && is_scalar($this->config[$name]) ? (string)$this->config[$name] : $default;
        return trim($value);
    }

    protected function setScalarValue($name, $value)
    {
        $value = is_scalar($value) ? (string)$value : '';
        $value = trim($value);
        $this->config[$name] = $value;
    }

    protected function getVariant($name, $variants, $default)
    {
        $val = isset($this->config[$name]) ? $this->config[$name] : null;
        if (!in_array($val, $variants, true)) {
            $val = $default;
        }
        return $val;
    }

    protected function setVariant($name, $value, $variants)
    {
        if (in_array($value, $variants, true)) {
            $this->config[$name] = $value;
        } else {
            $this->unsetKey($name);
        }
    }

    protected function unsetKey($key)
    {
        if (isset($this->config[$key])) {
            unset($this->config[$key]);
        }
    }

    protected function getDefaultVariant($variants)
    {
        $variant_ids = array_keys($variants);
        $default = reset($variant_ids);
        foreach ($variants as $variant_id => $variant) {
            if (!empty($variant['default'])) {
                $default = $variant_id;
                break;
            }
        }
        return $default;
    }

    protected function flatten2DArray($array)
    {
        $result = array();
        foreach ($array as $_array) {
            foreach ($_array as $index => $value) {
                $result[$index] = $value;
            }
        }
        return $result;
    }

    protected function buildUrl($url, $get_params = null)
    {
        $get_params = $get_params !== null ? $get_params : '';
        if ($get_params && (is_scalar($get_params) || is_array($get_params))) {
            $get_params = is_scalar($get_params) ? strval($get_params) : $this->buildGetQuery($get_params);
            if (strpos($url, '?') !== false) {
                $url .= '&' . $get_params;
            } else {
                $url .= '?' . $get_params;
            }
        }
        return $url;
    }

    protected function buildGetQuery($params)
    {
        $query = array();
        foreach ($params as $key => $value) {
            $query[] = "{$key}={$value}";
        }
        return join('&', $query);
    }

    protected function mergeGetParams($get_1, $get_2)
    {
        $get = array();
        foreach (array($get_1, $get_2) as $_get) {
            if (is_scalar($_get)) {
                $result = array();
                parse_str((string)$_get, $result);
                $_get = $result;
            }
            if (is_array($_get)) {
                $get = array_merge($get, $_get);
            }
        }
        return $get;
    }

    /**
     * @return waVerificationChannelModel
     */
    protected function getVerificationChannelModel()
    {
        return new waVerificationChannelModel();
    }

    /**
     * @return mixed
     */
    abstract public function getCanLoginByContactLogin();

    /**
     * Url of site
     * @return string
     */
    abstract public function getSiteUrl();

    /**
     * Name of site
     * @return string
     */
    abstract public function getSiteName();

    /**
     * What auth types supported
     * @return array
     */
    abstract public function getAuthTypes();

    /**
     * Actual save data in physical store
     * @return bool
     */
    abstract public function commit();

    /**
     * Url of login page
     * @param array $params
     * @param bool $absolute
     * @return string
     */
    abstract public function getLoginUrl($params = array(), $absolute = false);

    /**
     * Url of signup page
     * @param array $params
     * @param bool $absolute
     * @return string
     */
    abstract public function getSignupUrl($params = array(), $absolute = false);

    /**
     * Url of action to send (re-send) onetime password
     * @param array $params
     * @param bool $absolute
     * @return mixed
     */
    abstract public function getSendOneTimePasswordUrl($params = array(), $absolute = false);


    /**
     * Url of forgot password-page
     * Can or can't be equal with url of recovery password page, a.k.a. set-password
     * @see getRecoveryPasswordUrl
     * @param array $params
     * @param bool $absolute
     * @return mixed
     */
    abstract public function getForgotPasswordUrl($params = array(), $absolute = false);


    /**
     * Url of action for actual set password, a.k.a. set-password action
     * Can or can't be equal with url of forgot password page
     * @see getForgotPasswordUrl
     * @param array $params
     * @param bool $absolute
     * @return mixed
     */
    abstract public function getRecoveryPasswordUrl($params = array(), $absolute = false);

    /**
     * Need signup confirm or not
     * @return bool
     */
    abstract public function getSignUpConfirm();

    /**
     * Driver method for getting and saving data all at once
     * @see getData
     * @see setData
     * @param $type
     * @param null $key
     * @return mixed
     */
    abstract protected function getMethodByKey($type, $key = null);

    /**
     * Get options for transform phone(s) prefix
     * @param array
     * @return array
     */
    public function getPhoneTransformPrefix()
    {
        return $this->getArrayValue('phone_transform_prefix');
    }

    /**
     * @param string|string[] $options
     */
    public function setPhoneTransformPrefix($options)
    {
        $input_code = ifset($options['input_code']);
        $input_code = is_scalar($input_code) && strlen((string)$input_code) ? (string)$input_code : null;

        $output_code = ifset($options['output_code']);
        $output_code = is_scalar($output_code) && strlen((string)$output_code) ? (string)$output_code : null;

        if (wa_is_int($input_code) && wa_is_int($output_code)) {
            $this->setArrayValue('phone_transform_prefix', array(
                'input_code' => $input_code,
                'output_code' => $output_code
            ));
        } else {
            $this->unsetKey('phone_transform_prefix');
        }

    }

    /**
     * @return bool
     */
    public function needTransformPhonePrefix()
    {
        return !!$this->getPhoneTransformPrefix();
    }

    /**
     * Transform phone by rule(s) that in current config
     * @param string $phone
     * @param bool $is_reverse Reverse (<=) or direct (=>) transformation
     * @return array $result
     *   bool   $result['status'] if TRUE phone is changed (transformed)
     *   string $result['phone']  Resulted phone. If status is false original (input) phone will be return here
     */
    public function transformPhone($phone, $is_reverse = false)
    {
        $result = array(
            'status' => false,
            'phone' => $phone
        );

        if (!is_scalar($phone)) {
            return $result;
        }

        $options = $this->getPhoneTransformPrefix();
        $result = self::transformPhonePrefix($phone, array_merge($options, array('is_reverse' => $is_reverse)));

        return $result;
    }

    /**
     * Transform phone by specified rule
     *
     * @param string|string[] $phone
     *
     * @param array $options - options of transformation:
     *   - string 'input_code', for example 8 for Russia
     *   - string 'output_code', for example 7 for Russia
     *   - bool 'is_reverse' - reverse transformation of direct. By default is false, so direct
     *          Reverse is transformation from output_code to input_code (for example for Russia, 7 => 8)
     *          Direct is transformation from input_code to output_code (for example for Russia, 8 => 7)
     *
     * @return array
     */
    protected static function transformPhonePrefix($phone, $options = array())
    {
        $is_input_scalar = is_scalar($phone) || $phone === null;
        $phones = waUtils::toStrArray($phone);
        $original_phones = $phones;

        $options = is_array($options) ? $options : array();
        // is reverse transformation
        $is_reverse = (bool)ifset($options['is_reverse']);

        foreach ($phones as &$phone) {
            $phone = waContactPhoneField::cleanPhoneNumber($phone);

            $input_code = ifset($options['input_code']);
            $input_code = is_scalar($input_code) && strlen((string)$input_code) > 0 ? (string)$input_code : null;

            $output_code = ifset($options['output_code']);
            $output_code = is_scalar($output_code) && strlen((string)$output_code) > 0 ? (string)$output_code : null;

            if (wa_is_int($input_code) && wa_is_int($output_code)) {
                $input_code_len = strlen($input_code);
                $output_code_len = strlen($output_code);
                if (!$is_reverse && substr($phone, 0, $input_code_len) === $input_code) {
                    $phone = $output_code . substr($phone, $input_code_len);
                } elseif ($is_reverse && substr($phone, 0, $output_code_len) === $output_code) {
                    $phone = $input_code . substr($phone, $output_code_len);
                }
            }
        }
        unset($phone);

        $result = array();
        foreach ($phones as $index => $phone) {
            $changed = !waContactPhoneField::isPhoneEquals($original_phones[$index], $phone);
            $result[$index] = array(
                'status' => $changed,
                'phone' => $phone
            );
        }

        if ($is_input_scalar) {
            return reset($result);
        } else {
            return $result;
        }
    }

}
