<?php

/**
 * Class waFrontendSetPasswordForm
 *
 * Concrete class for set password form in frontend environment
 *
 * Set password form shows second in recover password process
 *
 */
class waFrontendSetPasswordForm extends waSetPasswordForm
{
    /**
     * @var waDomainAuthConfig
     */
    protected $auth_config;

    /**
     * waFrontendSetPasswordForm constructor.
     * @param array $options - options are inherited
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->default_templates_path = waConfig::get('wa_path_system') . '/login/templates/setpassword/frontend/';
        $this->auth_config = waDomainAuthConfig::factory();

        if (!isset($this->options['title'])) {
            $this->options['title'] = _ws('Password recovery for %s');
            $this->options['title'] = sprintf($this->options['title'], $this->getAddress());
        }

        // Hash that gives contact rights set new password
        // Hash has expiration time
        $hash = '';
        if (isset($this->options['hash']) && is_scalar($this->options['hash'])) {
            $hash = (string)$this->options['hash'];
        }
        $this->options['hash'] = $hash;

    }

    /**
     * @param array $options - that options will be passed to proper factory/constructor
     * @return waFrontendSetPasswordForm
     */
    public static function factory($options = array())
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        $auth_config = waDomainAuthConfig::factory();
        return wa($auth_config->getApp())->getSetPasswordForm($options);
    }

    /**
     * Get info from last response of forgot-password action
     *
     * NOTICE: delete response from storage right away, cause we need process this response only 1 time!
     *
     */
    protected function getLastActionResponse()
    {
        static $response;
        if (!$response) {
            $key = 'wa/forgotpassword/last_response';
            $response = wa()->getStorage()->get($key);
            $response = is_array($response) ? $response : array();
            wa()->getStorage()->del($key);
        }
        return $response;
    }

    /**
     * @see getLastActionResponse
     * @return string
     */
    protected function getAddress()
    {
        $response = $this->getLastActionResponse();
        $address = isset($response['address']) && is_scalar($response['address']) ? (string)$response['address'] : '';
        return $address;
    }

    protected function prepareTemplateAssign($assign = array())
    {
        $assign = parent::prepareTemplateAssign($assign);
        $assign['hash'] = $this->options['hash'];
        return $assign;
    }
}
