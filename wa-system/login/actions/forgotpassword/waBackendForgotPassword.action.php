<?php

/**
 * Class waBackendForgotPasswordAction
 *
 * Abstract action for restore password for backend
 *
 */
abstract class waBackendForgotPasswordAction extends waBaseForgotPasswordAction
{
    protected $env = 'backend';

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
