<?php

abstract class waForgotPasswordForm extends waLoginFormRenderer
{
    /**
     * @var waAuthConfig
     */
    protected $auth_config;

    /**
     * @param array $options
     * @return waForgotPasswordForm
     */
    public static function factory($options = array())
    {
        if (waConfig::get('is_template')) {
            return null;
        }

        if (wa()->getEnv() === 'backend') {
            return new waBackendForgotPasswordForm($options);
        } else {
            return waFrontendForgotPasswordForm::factory($options);
        }
    }

    protected function prepareForm()
    {
        $login_placeholder = $this->auth_config->getLoginPlaceholder();
        $login_placeholder = strlen($login_placeholder) > 0 ? $login_placeholder : _ws('Login');
        $login_caption = $this->auth_config->getLoginCaption();

        return array(
            'data'              => $this->data,
            'errors'            => $this->getAllErrors(),
            'renderer'          => $this,
            'url'               => $this->getForgotpasswordUrl(),
            'login_url'         => $this->getLoginUrl(),
            'need_placeholder'  => $this->options['need_placeholder'],
            'login_caption'     => $login_caption,
            'login_placeholder' => $login_placeholder,
        );
    }

    protected function prepareFormWrapper($form_html)
    {
        return array(
            'login_url' => $this->getLoginUrl()
        );
    }

    public function renderField($field_id, $params = null)
    {
        // Render directly in form template
        return '';
    }

    public function renderCaptcha()
    {
        if (!$this->auth_config->needLoginCaptcha()) {
            return '';
        }

        $template = $this->getTemplate('captcha');
        $object = wa()->getCaptcha(array(
            'namespace'     => $this->namespace,
        ));

        $assign = array(
            'object'       => $object,
            'class'        => get_class($object),
            'real_class'   => get_class($object->getRealCaptcha()),
            'errors'       => $this->getErrors('captcha'),
            'error'        => $this->getErrors('captcha', '<br>')
        );
        return $this->renderTemplate($template, $assign);
    }

    public function getUncaughtErrors()
    {
        // Handle directly in form template
        return array();
    }

    protected function getLoginUrl()
    {
        return $this->auth_config->getLoginUrl();
    }

    protected function getSignupUrl()
    {
        return $this->auth_config->getSignupUrl();
    }

    protected function getSendOnetimePasswordUrl()
    {
        return $this->auth_config->getSendOneTimePasswordUrl();
    }

    protected function getForgotpasswordUrl()
    {
        return $this->auth_config->getForgotPasswordUrl();
    }
}
