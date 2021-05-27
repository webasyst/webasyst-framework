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
    protected $env = 'frontend';

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
     * @param array $options
     * @return waFrontendForgotPasswordForm
     * @throws waException
     */
    public static function factory($options = [])
    {
        $config = waDomainAuthConfig::factory();
        $app_id = $config->getApp();
        if ($app_id && wa()->appExists($app_id)) {
            $class = $app_id . 'FrontendForgotPasswordForm';
            if (class_exists($class)) {
                $form = new $class($options);
                if ($form instanceof waFrontendForgotPasswordForm) {
                    return new $form($options);
                }
            }

        }
        return new waFrontendForgotPasswordForm($options);
    }


}
