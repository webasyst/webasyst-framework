<?php

/**
 * Class waLoginFormRenderer
 *
 * Abstract renderer for all forms in login module
 *
 */
abstract class waLoginFormRenderer
{
    /**
     * @var waAuthConfig
     */
    protected $auth_config;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string|null
     */
    protected $namespace;

    /**
     * @var array
     */
    protected $templates = array();

    /**
     * @var array
     */
    protected $data = array();

    /**
     * @var array
     */
    protected $errors = array();

    /**
     * @var array
     */
    protected $messages = array();

    /**
     * @var
     */
    protected $default_templates_path;

    /**
     * @var null|string
     */
    protected $env;

    /**
     * @var bool
     */
    protected $is_json_mode;

    /**
     * waLoginFormRenderer constructor.
     *
     * @param array $options
     *
     *   bool   'show_title' - need show own title. Default - FALSE
     *
     *   bool   'show_sub_title' - need show own title. Default - FALSE
     *
     *   bool   'show_oauth_adapters' - need show html block of o-auth adapters - Eg vk, facebook etc. Default - FALSE
     *
     *   string 'namespace' - namespace for input names in form. Default - no namespace
     *
     *   bool   'need_redirects' - need server trigger redirects. Default - TRUE
     *
     *   bool   'include_css' - include or not default css. Default - TRUE
     *
     *   string 'title' - Custom title in form
     *
     *   bool   'sub_title' - Custom sub title in form
     */
    public function __construct($options = array())
    {
        $this->initOptions($options);
        $this->env = $this->env ? $this->env : wa()->getEnv();
        if (strlen($this->options['namespace']) > 0) {
            $this->namespace = $this->options['namespace'];
        }
        $this->is_json_mode = true;
    }

    protected function initOptions($options)
    {
        $this->options = is_array($options) ? $options : array();
        $this->options['namespace'] = $this->getStrVal($this->options, 'namespace');
        $this->options['show_title'] = $this->getBoolVal($this->options, 'show_title');
        $this->options['show_sub_title'] = $this->getBoolVal($this->options, 'show_sub_title');
        $this->options['show_oauth_adapters'] = $this->getBoolVal($this->options, 'show_oauth_adapters');
        $this->options['need_redirects'] = $this->getBoolVal($this->options, 'need_redirects', true);
        $this->options['need_placeholders'] = $this->getBoolVal($this->options, 'need_placeholders', true);
        $this->options['include_css'] = $this->getBoolVal($this->options, 'include_css', true);
        $this->options['include_js'] = true;
    }


    /**
     * Get namespace for this form
     * @return null|string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Set custom title
     * @param $title
     */
    public function setTitle($title)
    {
        if (is_scalar($title)) {
            $title = (string)$title;
            $this->options['title'] = $title;
        } else {
            $this->options['title'] = null;
        }
    }

    /**
     * Set custom sub title
     * @param $sub_title
     */
    public function setSubtitle($sub_title)
    {
        if (is_scalar($sub_title)) {
            $sub_title = (string)$sub_title;
            $this->options['sub_title'] = $sub_title;
        } else {
            $this->options['sub_title'] = null;
        }
    }

    /**
     * Get custom title
     * @return string
     */
    public function getTitle()
    {
        if (isset($this->options['title']) && is_scalar($this->options['title'])) {
            return (string)$this->options['title'];
        } else {
            return '';
        }
    }

    /**
     * Get custom sub title
     * @return string
     */
    public function getSubtitle()
    {
        if (isset($this->options['sub_title']) && is_scalar($this->options['sub_title'])) {
            return (string)$this->options['sub_title'];
        } else {
            return '';
        }
    }

    /**
     * Renders title of form
     * @param bool $escape
     * @return string html
     */
    public function renderTitle($escape = true)
    {
        $title = $this->getTitle();
        if (strlen($title) > 0) {
            if ($escape) {
                $title = htmlspecialchars($title);
            }
            return '<h1 class="wa-login-form-title">' . $title . '</h1>';
        }
        return '';
    }

    /**
     * Renders sub title of form
     * @param bool $escape
     * @return string html
     */
    public function renderSubTitle($escape = true)
    {
        $sub_title = $this->getSubtitle();
        if (strlen($sub_title) > 0) {
            if ($escape) {
                $sub_title = htmlspecialchars($sub_title);
            }
            return '<h2 class="wa-form-sub-title">' . $sub_title . '</h2>';
        }
        return '';
    }

    /**
     * Renders just form snippet of whole form
     * Uses form.html template
     * @return string html
     */
    protected function renderForm()
    {
        $assign = $this->prepareForm();
        $html = $this->renderTemplate($this->getTemplate('form'), $assign);
        return $html;

    }

    /**
     * Render form wrapper snippet of whole form
     * @param string $form_html already rendered form snippet
     * @see renderForm
     *
     * Uses form_wrapper.html template
     *
     * @return string html
     */
    protected function renderFormWrapper($form_html)
    {
        $assign = $this->prepareFormWrapper($form_html);
        return $this->renderTemplate($this->getTemplate('form_wrapper'), $assign);
    }

    /**
     * Render WHOLE form
     * @param array $data input data
     *
     *   Format
     *     string <field_id> => string|array <values>
     *
     *   Example
     *      array(
     *          'login' => 'user_login_12345'
     *      )
     *
     * @param array $errors input errors. Will be rendered right aways
     *
     *   Format
     *     string <ID> => string|array <values>
     *     <ID> - can be <field_id>.
     *          In that case error must be attached to it field
     *     <ID> - also may be some string key
     *          In that case error would be attached in the bottom or
     *              some other place that depends of UI login of concrete form
     *
     * @param array $messages input messages. Will be rendered right aways
     *   Format and meanings same as the $errors but they are not errors, just some info kind messages
     *
     * @return string html
     */
    public function render($data, $errors = array(), $messages = array())
    {
        $this->data = is_array($data) ? $data : array();
        $this->errors = is_array($errors) ? $errors : array();
        $this->messages = is_array($messages) ? $messages : array();
        $html = $this->renderForm();
        return $this->renderFormWrapper($html);
    }

    /**
     * Render captcha snippet
     * Already takes into account proper auth config option
     * @return string
     */
    public function renderCaptcha()
    {
        if (!$this->auth_config->needLoginCaptcha()) {
            return '';
        }

        $captcha = $this->getCaptcha();
        $assign = $this->prepareCaptcha($captcha);
        return $this->renderTemplate($this->getTemplate('captcha'), $assign);
    }

    /**
     * Wrap any html snippet into wrapper (wrapper.html)
     * @param string $html
     * @param array $options
     * @return string
     */
    public function wrap($html, $options = array())
    {
        $assign = $options;
        $assign = array_merge(
            array(
                'title' => $this->getTitle()
            ),
            $assign,
            array(
                'html' => $html,
                'child_id' => 'messages',
                'renderer' => $this
            ));
        $template = $this->getTemplate('wrapper');
        return $this->renderTemplate($template, $assign);
    }

    /**
     * Render messages for form
     * Uses messages.html template
     * @return string
     */
    public function renderMessages()
    {
        $template = $this->getTemplate('messages');
        return $this->renderTemplate($template, array(
            'messages' => $this->getMessages()
        ));
    }

    /**
     * Render uncaught errors - that errors that are not attached with fields
     * Uses errors.html template
     * @return string
     */
    public function renderUncaughtErrors()
    {
        $template = $this->getTemplate('errors');
        return $this->renderTemplate($template, array(
            'type' => 'uncaught',
            'errors' => $this->getUncaughtErrors()
        ));
    }

    /**
     * Uncaught errors - that errors that are not attached with fields
     * Methods for overriding in child classes
     * @return array
     */
    public function getUncaughtErrors()
    {
        return array();
    }

    /**
     * Set messages for current form
     * @param $messages
     */
    public function setMessages($messages)
    {
        if (is_array($messages)) {
            $this->messages = $messages;
        } else {
            $this->messages = array();
        }
    }

    /**
     * Get messages for current form
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Get all errors
     * @return array
     */
    public function getAllErrors()
    {
        $all_errors = array();
        foreach ($this->errors as $field_id => $errors) {
            if (is_array($errors) || is_scalar($errors)) {
                $errors = (array)$errors;
                $all_errors[$field_id] = $errors;
            }
        }
        return $all_errors;
    }

    /**
     * Set all errors
     * @param array $errors
     */
    public function setAllErrors($errors)
    {
        if (is_array($errors)) {
            $this->errors = $errors;
        } else {
            $this->errors = array();
        }
    }

    /**
     * Must be called in templates to inject csrf
     * Already takes into account environment
     * @return string
     */
    public function renderCsrf()
    {
        if ($this->env === 'backend' || waRequest::param('secure')) {
            return wa()->getView()->getHelper()->csrf();
        }
        return '';
    }

    /**
     * Build input name of field to use in template
     * Already takes into account namespace of form
     * @param $id
     * @return string
     */
    public function getInputName($id)
    {
        return $this->namespace ? "{$this->namespace}[{$id}]" : $id;
    }

    /**
     * Helper getter of errors by <ID> (field_id or some any name)
     * @param string $name
     * @param null $join string joiner (glue), if NULL errors will not be joined
     * @return array|mixed|string
     */
    protected function getErrors($name, $join = null)
    {
        $all_errors = $this->getAllErrors();
        $errors = isset($all_errors[$name]) ? $all_errors[$name] : array();
        if ($name && $join) {
            $join = is_scalar($join) ? $join : ' ';
            $errors = join($join, $errors);
        }
        return $errors;
    }

    /**
     * Get template by name
     * @param string $name
     * @return mixed
     * @throws waException
     */
    protected function getTemplate($name)
    {
        if (isset($this->templates[$name])) {
            return $this->templates[$name];
        }
        if (isset($this->options["{$name}_template"]) && file_exists($this->options["{$name}_template"])) {
            $this->templates[$name] = $this->options["{$name}_template"];
        } else {
            $path = $this->default_templates_path;
            if (!file_exists($path)) {
                throw new waException('Templates path not found');
            }
            $path = rtrim($path, '/');
            $this->templates[$name] = $path . '/' . $name . '.html';
        }
        return $this->templates[$name];
    }

    /**
     * Prepare assign array before any rendering
     * Help yourself to extend this method and mix-in any vars you need
     * @param array $assign
     * @return array
     */
    protected function prepareTemplateAssign($assign = array())
    {
        return array_merge($assign, array(
            'is_json_mode'                  => $this->is_json_mode,
            'show_title'                    => $this->options['show_title'],
            'show_sub_title'                => $this->options['show_sub_title'],
            'show_oauth_adapters'           => $this->options['show_oauth_adapters'],
            'need_redirects'                => $this->options['need_redirects'],
            'need_placeholders'             => $this->options['need_placeholders'],
            'is_onetime_password_auth_type' => $this->auth_config->getAuthType() === waAuthConfig::AUTH_TYPE_ONETIME_PASSWORD,
            'auth_config'                   => $this->auth_config->getData(),
            'is_need_confirm'               => $this->auth_config->getSignUpConfirm(),
            'namespace'                     => $this->getNamespace(),
            'include_js'                    => $this->options['include_js'],
            'include_css'                   => $this->options['include_css'],
            'title'                         => $this->getTitle(),
            'sub_title'                     => $this->getSubtitle(),
            'renderer'                      => $this,
            'login_url'                     => $this->getLoginUrl(),
            'signup_url'                    => $this->getSignupUrl(),
            'forgotpassword_url'            => $this->getForgotpasswordUrl(),
            'onetime_password_url'          => $this->getSendOnetimePasswordUrl(),
            'is_email_channel_available'    => !!$this->auth_config->getEmailVerificationChannel(),
            'is_sms_channel_available'      => !!$this->auth_config->getSMSVerificationChannel()
        ));
    }

    /**
     * Renders template
     * @param string $template
     * @param array $assign
     * @return string
     */
    protected function renderTemplate($template, $assign = array())
    {
        $view = wa()->getView();
        $old_vars = $view->getVars();
        $view->clearAllAssign();
        $assign = $this->prepareTemplateAssign($assign);
        $view->assign($assign);
        $html = $view->fetch($template);
        $view->clearAllAssign();
        $view->assign($old_vars);
        return trim($html);
    }

    /**
     * Options get will be passed to captcha
     * @see getCaptcha
     * @return array
     */
    protected function getCaptchaOptions()
    {
        $captcha_options = [
            'namespace'     => $this->namespace,
            'wrapper_class' => 'wa-captcha-section',
            'version'       => 2,
        ];
        if ($this->auth_config instanceof waDomainAuthConfig) {
            $captcha_options['app_id'] = $this->auth_config->getApp();
        }
        return $captcha_options;
    }

    /**
     * @return waCaptcha
     * @throws waException
     */
    protected function getCaptcha()
    {
        return wa()->getCaptcha($this->getCaptchaOptions());
    }


    /**
     * @param waAbstractCaptcha $captcha
     * @return array
     */
    protected function prepareCaptcha($captcha)
    {
        return array(
            'object'       => $captcha,
            'is_invisible' => $captcha->getOption('invisible'),
            'class'        => get_class($captcha),
            'real_class'   => waCaptcha::getCaptchaType($captcha),
            'errors'       => $this->getErrors('captcha'),
            'error'        => $this->getErrors('captcha', '<br>')
        );
    }

    /**
     * Prepares assign array for form wrapper (layout) before its rendering
     * @param string $form_html already rendered form
     * @return array
     */
    protected function prepareFormWrapper($form_html)
    {
        return array(
            'html'     => $form_html,
            'errors'   => $this->getAllErrors(),
            'messages' => $this->getMessages(),
        );
    }

    /**
     * Prepares assign array before form rendering
     * @return array
     */
    protected function prepareForm()
    {
        return array(
            'data'     => $this->data,
            'errors'   => $this->getAllErrors(),
            'messages' => $this->getMessages(),
        );
    }

    /**
     * Abstract render form field
     * @param $field_id
     * @param array $params
     * @return mixed
     */
    abstract public function renderField($field_id, $params = array());


    /**
     * Sign up url
     * @return string
     */
    protected function getSignupUrl()
    {
        return $this->auth_config->getSignupUrl();
    }

    /**
     * Login url
     * @return string
     */
    protected function getLoginUrl()
    {
        return $this->auth_config->getLoginUrl();
    }

    /**
     * Url of action that generates one time password
     * @return string
     */
    protected function getSendOnetimePasswordUrl()
    {
        return $this->auth_config->getSendOneTimePasswordUrl();
    }

    /**
     * Recover password page url
     * @return string
     */
    protected function getForgotpasswordUrl()
    {
        return $this->auth_config->getForgotPasswordUrl();
    }

    /**
     * @param array $array
     * @param $key
     * @param bool $default
     * @return bool
     */
    private function getBoolVal(array $array, $key, $default = false)
    {
        $value = $default;
        if (array_key_exists($key, $array)) {
            $value = (bool)$array[$key];
        }
        return $value;
    }

    /**
     * @param array $array
     * @param $key
     * @param string $default
     * @return string
     */
    private function getStrVal(array $array, $key, $default = '')
    {
        //isset($options['namespace']) && is_scalar($options['namespace']) ? (string)$options['namespace'] : '';
        if (array_key_exists($key, $array) && is_scalar($array[$key])) {
            return (string)$array[$key];
        } else {
            return $default;
        }
    }
}
