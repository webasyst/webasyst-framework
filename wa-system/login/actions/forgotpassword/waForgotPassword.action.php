<?php

/**
 * Class waForgotPasswordAction
 *
 * Abstract action for restore password for frontend
 *
 * Must be called waFrontendForgotPasswordAction
 * But for backward compatibility with old Shop (and other apps) MUST be called waForgotPasswordAction
 *
 */
class waForgotPasswordAction extends waBaseForgotPasswordAction
{
    /**
     * @var waDomainAuthConfig
     */
    protected $auth_config;
    protected $env = 'frontend';

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->auth_config = waDomainAuthConfig::factory();
    }

    /**
     * @param array $options
     * @return null
     */
    protected function getFormRenderer($options = array())
    {
        return null;
    }
}
