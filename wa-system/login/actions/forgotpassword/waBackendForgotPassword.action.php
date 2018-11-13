<?php

/**
 * Class waBackendForgotPasswordAction
 *
 * Abstract action for restore password for backend
 *
 */
class waBackendForgotPasswordAction extends waBaseForgotPasswordAction
{
    /**
     * @var waBackendAuthConfig
     */
    protected $auth_config;
    protected $env = 'frontend';

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->auth_config = waBackendAuthConfig::getInstance();
    }

    /**
     * @param array $options
     * @return waLoginFormRenderer
     */
    protected function getFormRenderer($options = array())
    {
        if ($this->isSetPasswordMode()) {
            return new waBackendSetPasswordForm($options);
        } else {
            return new waBackendForgotPasswordForm($options);
        }
    }
}
