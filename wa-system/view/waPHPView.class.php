<?php

class waPHPView extends waView
{
    protected $postfix = '.php';

    protected $vars = array();
    protected $template_dir = array();

    protected $current_template;

    public function __construct(waSystem $system, $options = array())
    {
        parent::__construct($system, $options);
        $this->template_dir = isset($options['template_dir']) ? $options['template_dir'] : $system->getAppPath();
    }

    public function assign($name, $value = null, $escape = false)
    {
        if (is_array($name)) {
            $this->vars += $name;
        } else {
            $this->vars[$name] = $value;
        }
    }

    public function clearAssign($name)
    {
        if (isset($this->vars[$name])) {
            unset($this->vars[$name]);
        }
    }

    public function clearAllAssign()
    {
        $this->vars = array();
    }

    public function getVars($name = null)
    {
        if ($name === null) {
            return $this->vars;
        } else {
            return isset($this->vars[$name]) ? $this->vars[$name] : null;
        }
    }

    public function fetch($template, $cache_id = null)
    {
        ob_start();
        $this->display($template, $cache_id);
        $result = ob_get_contents();
        ob_end_clean();
        return $result;
    }

    public function display($template, $cache_id = null)
    {
        if ($this->templateExists($template)) {
            $this->current_template = $template;
            $this->prepare();
            extract($this->vars);
            include($this->template_dir.'/'.$this->current_template);
        } else {
            throw new waException("Template ".$template.' not found');
        }
    }

    public function templateExists($template)
    {
        return file_exists($this->template_dir.'/'.$template);
    }

    public function setTemplateDir($path)
    {
        $this->template_dir = $path;
    }


}