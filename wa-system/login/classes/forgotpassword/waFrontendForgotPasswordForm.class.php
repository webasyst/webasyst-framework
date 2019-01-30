<?php

/**
 * Class waFrontendForgotPasswordForm
 *
 * Concrete class for forgot password form in frontend environment
 *
 * Forgot password form shows first in recover password process
 *
 */
class waFrontendForgotPasswordForm extends waForgotPasswordForm
{
    /**
     * @var waDomainAuthConfig
     */
    protected $auth_config;

    /**
     * waFrontendForgotPasswordForm constructor.
     * @param array $options - options are inherited
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        $this->default_templates_path = waConfig::get('wa_path_system') . '/login/templates/forgotpassword/frontend/';
        $this->auth_config = waDomainAuthConfig::factory();

        if (!isset($this->options['title'])) {
            $this->options['title'] = _ws('Password recovery');
        }
    }

    /**
     * @param array $options that options will be passed to proper factory/constructor
     * @return waFrontendForgotPasswordForm
     */
    public static function factory($options = array())
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        $auth_config = waDomainAuthConfig::factory();
        return wa($auth_config->getApp())->getForgotPasswordForm($options);
    }
}
