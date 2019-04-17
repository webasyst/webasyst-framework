<?php

/**
 * Class waLoginForm
 *
 * Abstract class for rendering login form
 *
 */
abstract class waLoginForm extends waLoginFormRenderer
{
    /**
     * Which part is rendered
     * @var array
     */
    protected $is_rendered = array();

    /**
     * Prepares assign array before form rendering
     * @return array
     */
    protected function prepareForm()
    {
        $assign = parent::prepareForm();
        return array_merge($assign, array(
            'fields' => array(
                'login' => array(),
                'password' => array('forgotpassword_url' => $this->getForgotpasswordUrl())
            )
        ));
    }

    /**
     * Gets errors that not assign to any field (or control)
     * @return array
     */
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

    /**
     * Render form field
     * @param string $field_id
     * @param array $params
     * @return string
     */
    public function renderField($field_id, $params = array())
    {
        if (!$field_id) {
            return '';
        }
        $params = is_array($params) && $params ? $params : array();
        return $this->renderContactField($field_id, $params);
    }

    /**
     * Render 'remember me' control
     * @return string
     */
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
            'input_name' => $this->getInputName('remember')
        ));

        $this->is_rendered['rememberme'] = strlen($html) > 0;
        return $html;
    }

    /**
     * Render captcha
     * Already takes into account proper auth config option
     * @return string
     */
    public function renderCaptcha()
    {
        $html = parent::renderCaptcha();
        $this->is_rendered['captcha'] = strlen($html) > 0;
        return $html;
    }

    /**
     * Render contact id
     * @param string $field_id
     * @param array $params
     * @return string
     */
    protected function renderContactField($field_id, array $params)
    {
        $field = $this->getContactField($field_id);
        $params['data_field_id'] = $field_id;
        $assign = $this->prepareContactField($field, $params);
        $assign['namespace'] = $this->getNamespace();
        $assign['auth_type'] = $this->auth_config->getAuthType();
        $html = $this->renderTemplate($this->getTemplate('field'), $assign);
        $this->is_rendered[$field_id] = strlen($html) > 0;
        return $html;
    }

    /**
     * Get value for field
     * @param string $field_id
     * @return mixed
     */
    protected function getContactFieldValue($field_id)
    {
        return isset($this->data[$field_id]) ? $this->data[$field_id] : null;
    }

    /**
     * Prepare assign array for template before rendering for contact field
     * @param waContactField $field
     * @param array $params
     * @return array
     */
    protected function prepareContactField(waContactField $field, array $params)
    {
        $field_id = $field->getId();

        $params['value'] = $this->getContactFieldValue($field_id);
        $params['caption'] = $this->getContactFieldCaption($field);
        $params['namespace'] = $this->getNamespace();

        $params['placeholder'] = $this->getContactFieldPlaceholder($field);

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

    /**
     * Get field caption for concrete field
     * Takes into proper account auth config option
     * @param waContactField $field
     * @return null|string
     */
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

    /**
     * Get field caption for concrete field
     * Takes into proper account auth config option
     * @param waContactField $field
     * @return null|string
     */
    protected function getContactFieldPlaceholder(waContactField $field)
    {
        $field_id = $field->getId();
        $placeholder = '';
        if ($field_id === 'login') {
            $login_placeholder = $this->auth_config->getLoginPlaceholder();
            if (strlen($login_placeholder) > 0) {
                $placeholder = $login_placeholder;
            }
        } elseif ($field_id === 'password') {
            $password_placeholder = $this->auth_config->getPasswordPlaceholder();
            if (strlen($password_placeholder) > 0) {
                $placeholder = $password_placeholder;
            }
        }
        return $placeholder;
    }

    /**
     * Constructor-getter of waContactField
     * @param string $field_id
     * @return waContactPasswordField|waContactStringField|waContactField
     */
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

    /**
     * Url of action that generates one time password
     * Takes into account proper auth config option
     * @return string|null
     */
    protected function getSendOnetimePasswordUrl()
    {
        if ($this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD) {
            return parent::getSendOnetimePasswordUrl();
        } else {
            return null;
        }
    }

    /**
     * Recover password page url
     * Takes into account proper auth config option
     * @return mixed|null
     */
    protected function getForgotpasswordUrl()
    {
        if ($this->auth_config->getAuthType() !== waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD) {
            return parent::getForgotpasswordUrl();
        } else {
            return null;
        }
    }
}
