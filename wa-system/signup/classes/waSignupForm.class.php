<?php

/**
 * Class waSignupForm
 *
 * That class responsible for rendering signup form
 *
 */
class waSignupForm
{
    /**
     * @var waDomainAuthConfig
     */
    protected $config;

    /**
     * @var array
     */
    protected $options = array();
    protected $namespace = 'data';
    protected $data = array();
    protected $errors = array();
    protected $templates = array();

    /**
     * waSignupForm constructor.

     * @param array $options
     *
     *   bool   'show_title' - need show own title. Default - FALSE
     *
     *   bool   'show_oauth_adapters' - need show html block of o-auth adapters - Eg vk, facebook etc. Default - FALSE
     *
     *   string 'namespace' - namespace for input names in form. Default - 'data'
     *
     *   bool   'need_redirects' - need server trigger redirects. Default - TRUE
     *
     *   string 'contact_type' - what type of contact to create 'person' or 'company'. Default - 'person'
     *
     *   bool 'include_css' - include or not default css. Default - TRUE
     *
     * @throws waException
     */
    public function __construct($options = array())
    {
        $this->config = waDomainAuthConfig::factory();

        $this->options = is_array($options) ? $options : array();

        $this->options['show_title'] = isset($this->options['show_title']) ? (bool)$this->options['show_title'] : false;
        $this->options['show_oauth_adapters'] = isset($this->options['show_oauth_adapters']) ? (bool)$this->options['show_oauth_adapters'] : false;

        if (isset($this->options['namespace']) && is_scalar($this->options['namespace'])) {
            $this->namespace = (string)$this->options['namespace'];
        }

        // init 'need_redirects' option. Notice that TRUE is default
        $need_redirects = true;
        if (array_key_exists('need_redirects', $this->options)) {
            $need_redirects = (bool)$this->options['need_redirects'];
        }
        $this->options['need_redirects'] = $need_redirects;

        // init 'need_placeholders' option. Notice that TRUE is default
        $need_placeholders = true;
        if (array_key_exists('need_placeholders', $this->options)) {
            $need_placeholders = (bool)$this->options['need_placeholders'];
        }
        $this->options['need_placeholders'] = $need_placeholders;

        // init 'contact_type' option
        if (isset($this->options['contact_type']) && is_scalar($this->options['contact_type'])) {
            $this->options['contact_type'] = (string)$this->options['contact_type'];
        } else {
            $this->options['contact_type'] = 'person';
        }

        // init 'include_css'
        $include_css = true;
        if (array_key_exists('include_css', $this->options)) {
            $include_css = (bool)$this->options['include_css'];
        }
        $this->options['include_css'] = $include_css;

    }

    public static function factory($options = array())
    {
        $config = waDomainAuthConfig::factory();
        $app_id = $config->getApp();
        if ($app_id && wa()->appExists($app_id)) {
            $class = "{$app_id}SignupForm";
            if (class_exists($class)) {
                $form = new $class($options);
                if ($form instanceof waSignupForm) {
                    return $form;
                }
            }
        }
        return new waSignupForm($options);
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @param array $data Input data - map of format <field_id> => <value>
     * @param array $errors Errors to render right aways - map of format <field_id> => array <list_of_errors>

     * @return mixed|string
     */
    public function render($data, $errors = array())
    {
        $this->data = is_array($data) ? $data : array();
        $this->errors = is_array($errors) ? $errors : array();

        $signup_action_response = $this->getSignupLastResponse();

        if (!empty($signup_action_response['email_confirmed'])) {
            return $this->renderTemplate($this->getTemplate('email_confirmed'), array(
                'app_url' => wa()->getAppUrl(),
            ));
        }

        $html = $this->renderTemplate($this->getTemplate('form'), array(
            'auth_config'            => $this->config->getData(),
            'renderer'               => $this,
            'data'                   => $this->data,
            'errors'                 => $this->errors,
            'signup_action_response' => $signup_action_response
        ));

        $is_secure = waRequest::param('secure');
        if ($is_secure) {
            $input = wa()->getView()->getHelper()->csrf();
            $html = preg_replace('!<\/form>!', $input.'</form>', $html);
        }

        $html = $this->renderTemplate($this->getTemplate('form_wrapper'), array(
            'form' => $html,
            'signup_action_response' => $signup_action_response
        ));

        return $html;
    }

    /**
     * Render concrete contact field
     * @param string $field_id
     * @param null|array $params Map of <key> => <value> that controls rendering of field
     *
     * Template: field.html
     *
     * @return string
     */
    public function renderField($field_id, $params = null)
    {
        if (!$field_id) {
            return $this->renderSeparator();
        }
        if (!is_array($params)) {
            $fields = $this->config->getFields();
            $params = isset($fields[$field_id]) ? $fields[$field_id] : array();
        }
        return $this->renderContactField($field_id, $params);
    }

    /**
     * Render captcha block
     *
     * Template: captcha.html
     *
     * @return string
     */
    public function renderCaptcha()
    {
        if (!$this->config->getSignUpCaptcha()) {
            return '';
        }
        $template = $this->getTemplate('captcha');
        $object = wa()->getCaptcha([
            'version' => 2,
            'wrapper_class' => 'wa-captcha-section',
            'app_id' => $this->config->getApp()
        ]);
        $assign = array(
            'object'       => $object,
            'is_invisible' => $object->getOption('invisible'),
            'class'        => get_class($object),
            'real_class'   => waCaptcha::getCaptchaType($object),
            'errors'       => $this->getErrors('captcha')
        );
        return $this->renderTemplate($template, $assign);
    }

    /**
     * Render service agreement block
     *
     * Template: service_agreement.html
     *
     * @return string
     */
    public function renderServiceAgreement()
    {
        $config_params = $this->config->getParams();
        if (empty($config_params['service_agreement'])) {
            return '';
        }
        $template = $this->getTemplate('service_agreement');
        $text = isset($config_params['service_agreement_text']) && is_scalar($config_params['service_agreement_text']) ? (string)$config_params['service_agreement_text'] : '';
        $assign = array(
            'text'    => $text,
            'type'    => $config_params['service_agreement'],
            'checked' => !empty($this->data['terms_accepted']),
            'errors'  => $this->getErrors('terms_accepted')
        );
        return $this->renderTemplate($template, $assign);
    }

    /**
     * Get concrete waContactField object for passed field_id
     * @param string $field_id
     * @return waContactCompositeField|waContactField|waContactPasswordField|waContactStringField
     */
    protected function getContactField($field_id)
    {
        if (strpos($field_id, '.')) {
            $field_id_parts = explode('.', $field_id);
            $id = $field_id_parts[0];
        } else {
            $id = $field_id;
        }
        $field = waContactFields::get($id);
        if ($field) {
            return $field;
        }
        if ($field_id == 'login') {
            return new waContactStringField($field_id, _ws('Login'));
        }
        if ($field_id == 'password') {
            return new waContactPasswordField($field_id, _ws('Password'));
        }
        return new waContactStringField($field_id, _ws('Unknown field'));
    }

    /**
     * Prepare not-composite waContactField for rendering
     *
     * @param waContactField $field
     * @param array $params
     * @return array
     *   Array of assignment that will be passed to template
     */
    protected function prepareContactNotCompositeField(waContactField $field, array $params)
    {
        $field_id = $field->getId();
        $data_field_id = isset($params['data_field_id']) ? $params['data_field_id'] : $field_id;
        $params['value'] = isset($this->data[$data_field_id]) ? $this->data[$data_field_id] : null;
        $params['caption'] = $this->getContactFieldCaption($field, $params);
        $params['namespace'] = $this->namespace;
        $params['ext'] = $this->getExt($data_field_id);

        if (isset($params['placeholder'])) {
            $params['placeholder'] = is_scalar($params['placeholder']) ? (string)$params['placeholder'] : '';
        } else {
            $params['placeholder'] = '';
        }

        if ($field_id === 'password') {
            $params['password_confirm_placeholder'] = $params['placeholder'];
        }

        $info = array(
            'field' => $field,
            'class' => get_class($field),
            'is_composite' => false,
            'is_hidden' => $field instanceof waContactHiddenField,
            'params' => $params,
            'errors' => $this->getErrors($data_field_id)
        );
        return $info;
    }

    /**
     * Prepare composite waContactField for rendering
     * @param waContactField $field
     * @param array $params
     * @return array
     *   Array of assignment that will be passed to template
     */
    protected function prepareContactCompositeField(waContactField $field, array $params)
    {
        $result = $this->prepareContactNotCompositeField($field, $params);
        $result['is_composite'] = true;
        $result['sub_fields'] = array();
        foreach ($field->getFields() as $sub_field_id => $sub_field) {
            $values = isset($result['params']['value']) ? $result['params']['value'] : array();
            $value = isset($values[$sub_field_id]) ? $values[$sub_field_id] : null;

            $params = array(
                'parent' => isset($result['params']['data_field_id']) ? $result['params']['data_field_id'] : $field->getId()
            );
            $params['value'] = $value;
            $params['caption'] = $this->getContactFieldCaption($sub_field, $params);
            $params['namespace'] = $this->namespace;

            $params['placeholder'] = '';

            $errors = isset($result['errors']) ? $result['errors'] : array();
            $errors = isset($errors[$sub_field_id]) ? $errors[$sub_field_id] : array();
            $errors = is_array($errors) || is_scalar($errors) ? (array)$errors : array();

            $info = array(
                'field' => $sub_field,
                'class' => get_class($sub_field),
                'is_hidden' => $sub_field instanceof waContactHiddenField,
                'params' => $params,
                'errors' => $errors
            );
            $result['sub_fields'][$sub_field_id] = $info;
        }

        return $result;
    }

    /**
     * Prepare concrete waContactField for rendering
     * @param waContactField $field
     * @param array $params
     * @return array
     *   Array of assignment that will be passed to template
     */
    protected function prepareContactField(waContactField $field, array $params)
    {
        if ($field instanceof waContactCompositeField) {
            return $this->prepareContactCompositeField($field, $params);
        } else {
            return $this->prepareContactNotCompositeField($field, $params);
        }
    }

    /**
     * Get array of errors for that name (Eg. field_id)
     * @param $name
     * @return array
     */
    protected function getErrors($name)
    {
        $all_errors = $this->errors;

        $errors = array();
        if (isset($all_errors[$name])) {
            $errors = $all_errors[$name];
            if (is_array($errors) || is_scalar($errors)) {
                $errors = (array)$errors;
            } else {
                $errors = array();
            }
        }

        return $errors;
    }

    /**
     * Get path to template by name
     * @param $name
     * @return mixed
     */
    protected function getTemplate($name)
    {
        if (!isset($this->templates[$name])) {
            $this->templates[$name] = waConfig::get('wa_path_system') . '/signup/templates/' . $name . '.html';
        }
        return $this->templates[$name];
    }

    /**
     * Render concrete contact field block
     *
     * @param string $field_id
     * @param array $params Controls rendering
     * @return string
     */
    protected function renderContactField($field_id, array $params)
    {
        $field = $this->getContactField($field_id);
        $params['data_field_id'] = $field_id;
        $assign = $this->prepareContactField($field, $params);
        return $this->renderTemplate($this->getTemplate('field'), $assign);
    }

    /**
     * Get caption for contact field
     * @param waContactField $field
     * @param $params
     * @return string
     */
    protected function getContactFieldCaption(waContactField $field, $params)
    {
        if (isset($params['caption'])) {
            return $params['caption'];
        }

        $caption = $field->getName(null, true);

        $data_field_id = isset($params['data_field_id']) ? $params['data_field_id'] : $field->getId();
        $ext = $this->getExt($data_field_id);
        if (!$ext) {
            return $caption;
        }

        $exts = $field->getParameter('ext');
        if (isset($exts[$params['ext']])) {
            $caption .= ' (' . _ws($exts[$ext]) . ')';
        } else {
            $caption .= ' (' . $ext . ')';
        }

        return $caption;
    }

    /**
     * Extract 'ext' suffix from field_id
     * @param $data_field_id
     * @return string
     */
    protected function getExt($data_field_id)
    {
        if (strpos($data_field_id, '.')) {
            $field_id_parts = explode('.', $data_field_id);
            $ext = (string)ifset($field_id_parts[1]);
            return $ext;
        }
        return '';
    }

    /**
     * @return string
     */
    protected function renderSeparator()
    {
        return '<div class="wa-field wa-separator"></div>';
    }

    /**
     * Render concrete template
     * @param string $template Path to template
     * @param array $assign assigned vars
     * @return string result
     */
    protected function renderTemplate($template, $assign = array())
    {
        $view = wa()->getView();
        $old_vars = $view->getVars();
        $view->clearAllAssign();
        $view->assign($this->prepareTemplateAssign($assign));
        $html = $view->fetch($template);
        $view->clearAllAssign();
        $view->assign($old_vars);
        return $html;
    }


    /**
     * Prepare any assigned vars array before pass to template
     * Help yourself to extend this method and mix-in any vars you need
     * @param array $assign
     * @return array
     */
    protected function prepareTemplateAssign($assign = array())
    {
        $assign = array_merge($assign, array(
            'is_onetime_password_auth_type' => $this->config->getAuthType() === waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD,
            'url' => $this->config->getSignUpUrl(),
            'login_url' => $this->config->getLoginUrl(),
            'is_need_confirm' => $this->config->getSignUpConfirm(),
            'namespace' => $this->namespace,
            'show_title' => $this->options['show_title'],
            'show_oauth_adapters' => $this->options['show_oauth_adapters'],
            'need_redirects' => $this->options['need_redirects'],
            'need_placeholders' => $this->options['need_placeholders'],
            'contact_type' => $this->options['contact_type'],
            'include_css' => $this->options['include_css'],
            'include_js' => true,
            'is_email_channel_available' => !!$this->config->getEmailVerificationChannel(),
            'is_sms_channel_available' => !!$this->config->getSMSVerificationChannel(),
        ));

        return $assign;
    }

    /**
     * Get last response from signup action
     * @return array
     */
    protected function getSignupLastResponse()
    {
        $response = wa()->getStorage()->get('wa/signup/last_response');
        $response = is_array($response) ? $response : array();
        return $response;
    }
}
