<?php

abstract class waSetPasswordForm extends waLoginFormRenderer
{
    /**
     * @var waAuthConfig
     */
    protected $auth_config;

    protected function prepareForm()
    {
        return array(
            'data' => $this->data,
            'errors' => $this->getAllErrors(),
            'renderer' => $this,
            'need_placeholder' => $this->options['need_placeholder'],
            'timeout' => $this->auth_config->getRecoveryPasswordTimeout(),
            'url' => $this->getForgotpasswordUrl(),
            'login_url' => $this->getLoginUrl()
        );
    }

    protected function prepareFormWrapper($form_html)
    {
        return array(
            'timeout' => $this->auth_config->getRecoveryPasswordTimeout()
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
