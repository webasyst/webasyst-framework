<?php

/**
 * Class waBackendLoginAction
 *
 * Abstract action for login to backend
 *
 */
abstract class waBackendLoginAction extends waBaseLoginAction
{
    protected $env = 'backend';

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->auth_config = waBackendAuthConfig::getInstance();
    }

    protected function checkAuthConfig()
    {
    }

    protected function saveReferer()
    {
    }

    protected function afterAuth()
    {
    }

    /**
     * @param array $options
     * @return waBackendLoginForm
     */
    protected function getFormRenderer($options = array())
    {
        return new waBackendLoginForm($options);
    }
}
