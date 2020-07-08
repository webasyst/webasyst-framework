<?php

/**
 * Class waBackendLoginForm
 *
 * Concrete class for rendering login form in backend environment
 */
class waBackendLoginForm extends waLoginForm
{
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
        $remember_enabled = $this->auth_config->getRememberMe();
        if (!$remember_enabled) {
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

        // link to auth by webasyst ID
        $assign['webasyst_id_auth_url'] = isset($this->options['webasyst_id_auth_url']) ? $this->options['webasyst_id_auth_url'] : '';

        // special mode of form = login & bind to webasyst ID at the same time
        $assign['bind_with_webasyst_contact'] = isset($this->options['bind_with_webasyst_contact']) ? $this->options['bind_with_webasyst_contact'] : false;

        // in case of bind with webasyst id it we should has here webasyst contact info (another word customer center contact info)
        $assign['webasyst_contact_info'] = isset($this->options['webasyst_contact_info']) ? $this->options['webasyst_contact_info'] : null;

        $assign['webasyst_id_auth_result'] = isset($this->options['webasyst_id_auth_result']) ? $this->options['webasyst_id_auth_result'] : [];

        return $assign;
    }
}
