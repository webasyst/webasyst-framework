<?php

class waFrontendForgotPasswordForm extends waForgotPasswordForm
{
    /**
     * @var waDomainAuthConfig
     */
    protected $auth_config;

    public function __construct($options = array())
    {
        parent::__construct($options);
        $this->default_templates_path = waConfig::get('wa_path_system') . '/login/templates/forgotpassword/frontend/';
        $this->auth_config = waDomainAuthConfig::factory();

        if (!isset($this->options['title'])) {
            $this->options['title'] = _ws('Password recovery');
        }
    }

    /**
     * @param array $options
     * @return waFrontendForgotPasswordForm
     */
    public static function factory($options = array())
    {
        if (waConfig::get('is_template')) {
            return null;
        }
        $auth_config = waDomainAuthConfig::factory();
        return wa($auth_config->getApp())->getForgotPasswordForm($options);
    }

    protected function prepareTemplateAssign($assign = array())
    {
        $assign = parent::prepareTemplateAssign($assign);
        return array_merge($assign, array(
            'auth_config' => $this->auth_config
        ));
    }

    public function renderCaptcha()
    {
        if (!$this->auth_config->needLoginCaptcha()) {
            return '';
        }

        $template = $this->getTemplate('captcha');
        $object = wa()->getCaptcha(array(
            'namespace'     => $this->namespace,
            'version'       => 2,
            'wrapper_class' => 'wa-captcha-section',
        ));

        $assign = array(
            'object'       => $object,
            'is_invisible' => $object->getOption('invisible'),
            'class'        => get_class($object),
            'real_class'   => get_class($object->getRealCaptcha()),
            'errors'       => $this->getErrors('captcha'),
            'error'        => $this->getErrors('captcha', '<br>')
        );
        return $this->renderTemplate($template, $assign);
    }
}
