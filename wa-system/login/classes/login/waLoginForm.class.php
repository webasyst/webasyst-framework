<?php

class waLoginForm extends waLoginFormRenderer
{
    /**
     * @var waAuthConfig
     */
    protected $auth_config;

    /**
     * Which part is rendered
     * @var array
     */
    protected $is_rendered = array();

    /**
     * @param array $options
     * @return waLoginForm
     */
    public static function factory($options = array())
    {
        if (waConfig::get('is_template')) {
            return null;
        }

        if (wa()->getEnv() === 'backend') {
            return new waBackendLoginForm($options);
        } else {
            return waFrontendLoginForm::factory($options);
        }
    }

    protected function prepareForm()
    {
        $fields = array('login' => array(), 'password' => array('forgotpassword_url' => $this->getForgotpasswordUrl()));

        $urls = array(
            'url'                  => $this->getLoginUrl(),
            'signup_url'           => $this->getSignupUrl(),
            'forgotpassword_url'   => $this->getForgotpasswordUrl(),
            'onetime_password_url' => $this->getSendOnetimePasswordUrl(),
        );

        return array_merge($urls, array(
            'auth_config' => $this->auth_config->getData('login'),
            'fields'      => $fields,
            'renderer'    => $this,
            'data'        => $this->data,
            'errors'      => $this->errors,
            'messages'    => $this->messages,
            'is_onetime_password_auth_type' => $this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD
        ));
    }

    protected function prepareFormWrapper($form_html)
    {
        $urls = array(
            'url' => $this->getLoginUrl(),
            'signup_url' => $this->getSignupUrl(),
            'forgotpassword_url' => $this->getForgotpasswordUrl(),
            'onetime_password_url' => $this->getSendOnetimePasswordUrl(),
        );

        return array_merge($urls, array(
            'auth_config' => $this->auth_config->getData('login')
        ));
    }

    public function getUncaughtErrors()
    {
        $errors = array();
        $all_errors = $this->errors;
        foreach ($all_errors as $field_id => $error) {
            if (!isset($this->is_rendered[$field_id]) && (is_array($error) || is_scalar($error))) {
                $errors[$field_id] = (array)$error;
            }
        }
        return $errors;
    }

    public function renderField($field_id, $params = null)
    {
        if (!$field_id) {
            return '';
        }
        $params = is_array($params) && $params ? $params : array();
        return $this->renderContactField($field_id, $params);
    }

    public function renderRememberMe()
    {
        $template = $this->getTemplate('remember_me');

        if (array_key_exists('remember', $this->data)) {
            $checked = (bool)$this->data['remember'];
        } else {
            $checked = wa()->getRequest()->cookie('remember', 1);
        }

        $html = $this->renderTemplate($template, array(
            'checked'    => $checked,
            'input_name' => $this->getInputName('remember'),
            'is_onetime_password_auth_type' => $this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD
        ));

        $this->is_rendered['rememberme'] = strlen($html) > 0;
        return $html;
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
        $html = $this->renderTemplate($template, $assign);
        $this->is_rendered['captcha'] = strlen($html) > 0;
        return $html;
    }

    protected function renderContactField($field_id, array $params)
    {
        $field = $this->getContactField($field_id);
        $params['data_field_id'] = $field_id;
        $assign = $this->prepareContactField($field, $params);
        $assign['namespace'] = $this->namespace;
        $assign['auth_type'] = $this->auth_config->getAuthType();
        $html = $this->renderTemplate($this->getTemplate('field'), $assign);
        $this->is_rendered[$field_id] = strlen($html) > 0;

        return $html;
    }

    protected function getContactFieldValue($field_id)
    {
        return isset($this->data[$field_id]) ? $this->data[$field_id] : null;
    }

    protected function prepareContactField(waContactField $field, array $params)
    {
        $field_id = $field->getId();

        $params['value'] = $this->getContactFieldValue($field_id);
        $params['caption'] = $this->getContactFieldCaption($field);
        $params['namespace'] = $this->namespace;

        if ($this->options['need_placeholder']) {
            $params['placeholder'] = $this->getContactFieldPlaceholder($field);
        }

        $info = array(
            'field'     => $field,
            'class'     => get_class($field),
            'is_hidden' => $field instanceof waContactHiddenField,
            'params'    => $params,
            'errors'    => $this->getErrors($field_id),
            'is_onetime_password_auth_type' => $this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD,
            'onetime_password_url' => $this->getSendOnetimePasswordUrl(),
        );
        return $info;
    }

    protected function getContactFieldCaption(waContactField $field)
    {
        $field_id = $field->getId();
        $caption = null;
        if ($field_id === 'login') {
            $login_caption = $this->auth_config->getLoginCaption();
            if (strlen($login_caption) > 0) {
                $caption = $login_caption;
            }
        }
        if ($caption === null) {
            $caption = $field->getName();
        }
        return $caption;
    }

    protected function getContactFieldPlaceholder(waContactField $field)
    {
        $field_id = $field->getId();
        $placeholder = null;
        if ($field_id === 'login') {
            $login_placeholder = $this->auth_config->getLoginPlaceholder();
            if (strlen($login_placeholder) > 0) {
                $placeholder = $login_placeholder;
            }
        }
        if ($placeholder === null) {
            $placeholder = $field->getName();
        }
        return $placeholder;
    }

    protected function getContactField($field_id)
    {
        if ($field_id == 'login') {
            return new waContactStringField($field_id, _ws('Login'));
        }
        if ($field_id == 'password') {
            return new waContactPasswordField($field_id, _ws('Password'));
        }
        return new waContactStringField($field_id, _ws('Unknown field'));
    }

    protected function getSignupUrl()
    {
        return $this->auth_config->getSignupUrl();
    }

    protected function getLoginUrl()
    {
        return $this->auth_config->getLoginUrl();
    }

    protected function getSendOnetimePasswordUrl()
    {
        if ($this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD) {
            return $this->auth_config->getSendOneTimePasswordUrl();
        } else {
            return null;
        }
    }

    protected function getForgotpasswordUrl()
    {
        if ($this->auth_config->getAuthType() !== waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD) {
            return $this->auth_config->getForgotPasswordUrl();
        } else {
            return null;
        }
    }

    protected function prepareTemplateAssign($assign = array())
    {
        $assign = parent::prepareTemplateAssign($assign);
        $assign = array_merge($assign, array(
            'is_onetime_password_auth_type' => $this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD
        ));
        return $assign;
    }
}
