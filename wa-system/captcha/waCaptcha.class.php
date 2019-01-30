<?php

/**
 * Wraps around a different captcha class.
 * Selects between captcha implementations depending on system options.
 *
 *
 * IMPLEMENTS waAbstractCaptcha interface
 * @method getOption($key = null, $default = null)
 * @method getHtml()
 * @method display()
 *
 */
class waCaptcha
{
    /**
     * Determine type of captcha that will be used for given up.
     * This unwraps instances of waCaptcha if present.
     *
     * @param string|object app_id or captcha class
     * @return string class name
     */
    public static function getCaptchaType($app_id = null)
    {
        if (is_object($app_id)) {
            $captcha = $app_id;
        } else {
            $captcha = wa($app_id)->getCaptcha();
        }
        if ($captcha instanceof waAbstractCaptcha) {
            return get_class($captcha);
        }

        if (method_exists($captcha, 'getRealCaptcha')) {
            return get_class($captcha->getRealCaptcha());
        }

        return get_class($captcha);
    }

    /**
     * @object waAbstractCaptcha
     */
    protected $captcha = null;

    protected $options = array();

    /**
     * @param array $options
     */
    public function __construct($options = array())
    {
        $this->options = ifempty($options, array()) + $this->options;
    }

    public function getRealCaptcha()
    {
        if ($this->captcha === null) {
            list($class, $options) = $this->readCaptchaConfig();
            if (empty($class) || !class_exists($class)) {
                $class = 'waPHPCaptcha';
                $options = array();
            }
            if ($this->options) {
                if(!isset($options)) {
                    $options = array();
                }
                if (is_array($options)) {
                    $options += $this->options;
                }
            }
            if (method_exists($class, 'factory')) {
                $this->captcha = call_user_func(array($class, 'factory'), $options);
            } else {
                $this->captcha = new $class($options);
            }
        }

        return $this->captcha;
    }

    protected function readCaptchaConfig()
    {
        $captcha_config_path = wa()->getConfig()->getConfigPath('config.php', true, 'webasyst');
        if (file_exists($captcha_config_path)) {
            $config = include($captcha_config_path);
            $class = ifset($config, 'captcha', 0, 'waPHPCaptcha');
            $options = ifset($config, 'captcha', 1, array());
            return array($class, $options);
        }
        return array(null, null);
    }

    public function isValid($code = null, &$error = '')
    {
        return $this->getRealCaptcha()->isValid($code, $error);
    }

    public function __call($name, $arguments)
    {
        $captcha = $this->getRealCaptcha();
        if (method_exists($captcha, $name)) {
            return call_user_func_array(array($captcha, $name), $arguments);
        } else {
            throw new waException('Call undefined method', 500);
        }
    }
}
