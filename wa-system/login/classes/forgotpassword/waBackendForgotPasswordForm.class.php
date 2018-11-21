<?php

class waBackendForgotPasswordForm extends waForgotPasswordForm
{
    /**
     * @var waBackendAuthConfig
     */
    protected $auth_config;

    /**
     * waBackendForgotPasswordForm constructor.
     * @param array $options - options are inherited
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);
        $this->auth_config = waBackendAuthConfig::getInstance();
        $this->default_templates_path = waConfig::get('wa_path_system') . '/login/templates/forgotpassword/backend/';
    }
}
