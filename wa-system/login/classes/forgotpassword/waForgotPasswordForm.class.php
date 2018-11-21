<?php

/**
 * Class waForgotPasswordForm
 *
 * Abstract class for forgot password form
 *
 * Forgot password form shows first in recover password process
 */
abstract class waForgotPasswordForm extends waLoginFormRenderer
{
    /**
     * @var waAuthConfig
     */
    protected $auth_config;

    /**
     * @param array $options that options will be passed to proper factory/constructor
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

    /**
     * Prepare assign array before form rendering
     * @return array
     */
    protected function prepareForm()
    {
        $assign = parent::prepareForm();

        $login_placeholder = $this->auth_config->getLoginPlaceholder();
        $code_placeholder = _ws('Confirmation code');

        $login_caption = $this->auth_config->getLoginCaption();

        return array_merge($assign, array(
            'login_caption'     => $login_caption,
            'login_placeholder' => $login_placeholder,
            'code_placeholder'  => $code_placeholder
        ));
    }

    /**
     * @param $field_id
     * @param array $params
     * @return string
     */
    public function renderField($field_id, $params = array())
    {
        // Render directly in form template
        return '';
    }
}
