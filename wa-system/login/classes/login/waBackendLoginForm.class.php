<?php

class waBackendLoginForm extends waLoginForm
{
    /**
     * @var waBackendAuthConfig
     */
    protected $auth_config;

    protected $env = 'backend';
    
    public function __construct($options = array())
    {
        parent::__construct(array_merge($options, array(
            'need_placeholder' => true,
            'include_js' => true,
            'include_css' => true
        )));
        $this->auth_config = waBackendAuthConfig::getInstance();
        $this->default_templates_path = waConfig::get('wa_path_system') . '/login/templates/login/backend/';
    }

    public function renderRememberMe()
    {
        $model = new waAppSettingsModel();
        if (!$model->get('webasyst', 'rememberme', 1)) {
            return '';
        }
        return parent::renderRememberMe();
    }

    protected function getSignupUrl()
    {
        return null;
    }

    protected function getLoginUrl()
    {
        return null;
    }

    protected function prepareTemplateAssign($assign = array())
    {
        $assign = parent::prepareTemplateAssign($assign);
        $assign['is_api_oauth'] = isset($this->options['is_api_oauth']) ? $this->options['is_api_oauth'] : false;
        return $assign;
    }
}
