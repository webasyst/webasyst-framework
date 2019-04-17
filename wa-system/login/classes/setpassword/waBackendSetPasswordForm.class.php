<?php

class waBackendSetPasswordForm extends waSetPasswordForm
{
    protected $env = 'backend';

    /**
     * waBackendSetPasswordForm constructor.
     * @param array $options - options are inherited
     */
    public function __construct(array $options = array())
    {
        parent::__construct($options);
        $this->auth_config = waBackendAuthConfig::getInstance();
        $this->default_templates_path = waConfig::get('wa_path_system') . '/login/templates/setpassword/backend/';
    }
}
