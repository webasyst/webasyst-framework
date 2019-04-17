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

}
