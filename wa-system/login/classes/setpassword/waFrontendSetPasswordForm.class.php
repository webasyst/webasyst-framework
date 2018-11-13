<?php

class waFrontendSetPasswordForm extends waSetPasswordForm
{
    /**
     * @var waDomainAuthConfig
     */
    protected $auth_config;

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
     * @param array $options
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
