<?php

class waBackendForgotPasswordForm extends waForgotPasswordForm
{
    /**
     * @var waBackendAuthConfig
     */
    protected $auth_config;

    public function __construct(array $options = array())
    {
        parent::__construct(array_merge($options, array(
            'need_placeholder' => true,
            'include_js' => true,
            'include_css' => true
        )));
        $this->auth_config = waBackendAuthConfig::getInstance();
        $this->default_templates_path = waConfig::get('wa_path_system') . '/login/templates/forgotpassword/backend/';
    }
}
