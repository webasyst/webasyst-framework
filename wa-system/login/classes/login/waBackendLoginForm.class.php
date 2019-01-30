<?php

/**
 * Class waBackendLoginForm
 *
 * Concrete class for rendering login form in backend environment
 */
class waBackendLoginForm extends waLoginForm
{
    /**
     * @var waBackendAuthConfig
     */
    protected $auth_config;

    /**
     * @var string
     */
    protected $env = 'backend';

    /**
     * waBackendLoginForm constructor.
     * @param array $options - options are inherited
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        $this->auth_config = waBackendAuthConfig::getInstance();
        $this->default_templates_path = waConfig::get('wa_path_system') . '/login/templates/login/backend/';
    }

    /**
     * Render 'remember me' control
     * @return string
     */
    public function renderRememberMe()
    {
        $model = new waAppSettingsModel();
        if (!$model->get('webasyst', 'rememberme', 1)) {
            return '';
        }
        return parent::renderRememberMe();
    }

    /**
     * @return null
     */
    protected function getSignupUrl()
    {
        return null;
    }

    /**
     * @return null
     */
    protected function getLoginUrl()
    {
        return null;
    }

    /**
     * Prepare assign array before any rendering
     * Mix in extra necessary vars for current class
     * @param array $assign
     * @return array
     */
    protected function prepareTemplateAssign($assign = array())
    {
        $assign = parent::prepareTemplateAssign($assign);
        // for api oauth authorization (e.g. mobile phones)
        $assign['is_api_oauth'] = isset($this->options['is_api_oauth']) ? $this->options['is_api_oauth'] : false;
        return $assign;
    }
}
