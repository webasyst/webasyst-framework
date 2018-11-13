<?php

abstract class waLoginFormRenderer
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var string|null
     */
    protected $namespace;

    protected $templates = array();
    protected $data = array();
    protected $errors = array();
    protected $messages = array();
    protected $default_templates_path;
    protected $env;
    protected $is_json_mode;

    public function __construct($options = array())
    {
        if (!$this->env) {
            $this->env = wa()->getEnv();
        }
        $this->options = is_array($options) ? $options : array();
        $this->options['need_placeholder'] = isset($this->options['need_placeholder']) ? (bool)$this->options['need_placeholder'] : false;

        $this->namespace = isset($options['namespace']) && is_scalar($options['namespace']) ? (string)$options['namespace'] : '';
        if (strlen($this->namespace) <= 0) {
            $this->namespace = null;
        }

        $this->options['show_title'] = isset($this->options['show_title']) ? (bool)$this->options['show_title'] : false;
        $this->options['show_sub_title'] = isset($this->options['show_sub_title']) ? (bool)$this->options['show_sub_title'] : false;
        $this->options['show_oauth_adapters'] = isset($this->options['show_oauth_adapters']) ? (bool)$this->options['show_oauth_adapters'] : false;

        // init 'need_redirects' option. Notice that TRUE is default
        $need_redirects = true;
        if (array_key_exists('need_redirects', $this->options)) {
            $need_redirects = (bool)$this->options['need_redirects'];
        }
        $this->options['need_redirects'] = $need_redirects;

        $this->is_json_mode = true;
    }

    public function getNamespace()
    {
        return $this->namespace;
    }

    public function setTitle($title)
    {
        if (is_scalar($title)) {
            $title = (string)$title;
            $this->options['title'] = $title;
        } else {
            $this->options['title'] = null;
        }
    }

    public function setSubtitle($sub_title)
    {
        if (is_scalar($sub_title)) {
            $sub_title = (string)$sub_title;
            $this->options['sub_title'] = $sub_title;
        } else {
            $this->options['sub_title'] = null;
        }
    }

    public function getTitle()
    {
        if (isset($this->options['title']) && is_scalar($this->options['title'])) {
            return (string)$this->options['title'];
        } else {
            return '';
        }
    }

    public function getSubtitle()
    {
        if (isset($this->options['sub_title']) && is_scalar($this->options['sub_title'])) {
            return (string)$this->options['sub_title'];
        } else {
            return '';
        }
    }

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

    protected function renderForm()
    {
        $assign = $this->prepareForm();
        $html = $this->renderTemplate($this->getTemplate('form'), $assign);
        return $html;

    }

    protected function renderFormWrapper($form_html)
    {
        $assign = $this->prepareFormWrapper($form_html);
        $assign = array_merge($assign, array(
            'html'              => $form_html,
            'errors'            => $this->getAllErrors(),
            'messages'          => $this->getMessages(),
            'namespace'         => $this->getNamespace(),
            'include_js'        => isset($this->options['include_js']) ? (bool)$this->options['include_js'] : false,
            'include_css'       => isset($this->options['include_css']) ? (bool)$this->options['include_css'] : false,
            'title'             => $this->getTitle(),
            'sub_title'         => $this->getSubtitle(),
            'renderer'          => $this
        ));
        return $this->renderTemplate($this->getTemplate('form_wrapper'), $assign);
    }

    public function render($data, $errors = array(), $messages = array())
    {
        $this->data = is_array($data) ? $data : array();
        $this->errors = is_array($errors) ? $errors : array();
        $this->messages = is_array($messages) ? $messages : array();
        $html = $this->renderForm();
        return $this->renderFormWrapper($html);
    }

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

    public function renderMessages()
    {
        $template = $this->getTemplate('messages');
        return $this->renderTemplate($template, array(
            'messages' => $this->getMessages()
        ));
    }

    public function renderUncaughtErrors()
    {
        $template = $this->getTemplate('errors');
        return $this->renderTemplate($template, array(
            'type' => 'uncaught',
            'errors' => $this->getUncaughtErrors()
        ));
    }

    public function getUncaughtErrors()
    {
        return array();
    }

    public function setMessages($messages)
    {
        if (is_array($messages)) {
            $this->messages = $messages;
        } else {
            $this->messages = array();
        }
    }

    public function getMessages()
    {
        return $this->messages;
    }

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

    public function setAllErrors($errors)
    {
        if (is_array($errors)) {
            $this->errors = $errors;
        } else {
            $this->errors = array();
        }
    }

    abstract public function renderField($field_id, $params = null);

    abstract public function renderCaptcha();

    public function renderCsrf()
    {
        if ($this->env === 'backend' || waRequest::param('secure')) {
            return wa()->getView()->getHelper()->csrf();
        }
        return '';
    }

    public function getInputName($id)
    {
        return $this->namespace ? "{$this->namespace}[{$id}]" : $id;
    }

    protected function getErrors($name, $join = null)
    {
        $all_errors = $this->getAllErrors();
        $errors = isset($all_errors[$name]) ? $all_errors[$name] : array();
        if ($name && $join) {
            $errors = join($join = is_scalar($join) ? $join : ' ', $errors);
        }
        return $errors;
    }

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
     * Help yourself to extend this method and mix-in any vars you need
     * @param array $assign
     * @return array
     */
    protected function prepareTemplateAssign($assign = array())
    {
        $assign['is_json_mode'] = $this->is_json_mode;
        $assign['show_title'] = $this->options['show_title'];
        $assign['show_sub_title'] = $this->options['show_sub_title'];
        $assign['show_oauth_adapters'] = $this->options['show_oauth_adapters'];
        $assign['need_redirects'] = $this->options['need_redirects'];
        return $assign;
    }

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

    abstract protected function prepareFormWrapper($form_html);
    abstract protected function prepareForm();

    abstract protected function getSignupUrl();
    abstract protected function getLoginUrl();
    abstract protected function getSendOnetimePasswordUrl();
    abstract protected function getForgotpasswordUrl();
}
