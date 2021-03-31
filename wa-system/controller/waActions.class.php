<?php

abstract class waActions extends waController
{
    use waActionTemplatePathBuilder;

    protected $action;
    /**
     * @var waLayout
     */
    protected $layout;

    protected $template = null;

    /**
     * Is relative template path ($this->template), so we can use auto mechanism of choosing template folder (waActionTemplatePathBuilder)
     * Relative means relative from template dir of current application (plugin)
     * @var bool
     */
    protected $is_relative = false;

    public function setLayout(waLayout $layout)
    {
        $this->layout = $layout;
    }

    protected function preExecute()
    {

    }

    protected function execute($action)
    {
        $method = $action.'Action';
        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            throw new waException(sprintf("Invalid action or missed method %s at %s for action %s", $method, get_class($this), $action));
        }
    }

    protected function postExecute()
    {

    }

    public function run($params = null)
    {
        $action = $params;
        if (!$action) {
            $action = 'default';
        }
        $this->action = $action;
        $this->preExecute();
        $this->execute($this->action);
        $this->postExecute();
    }

    protected function getView()
    {
        return wa()->getView();
    }

    /**
     * Set custom template
     *
     * @param string $template
     * Template path
     *
     * @param bool $is_relative
     * Is relative from template dir of current application (plugin)
     */
    public function setTemplate($template, $is_relative = false)
    {
        $this->template = $template;
        $this->is_relative = $is_relative;
    }

    /**
     * @return string
     * @throws waException
     */
    protected function getTemplate()
    {
        $template = $this->template;
        if (!$template) {
            $template = ucfirst($this->action);
        }

        // If path contains / or : then it's a full path to template
        if (strpbrk($template, '/:') === false) {
            $match = array();
            if (!preg_match("/^[a-z]+([A-Z][a-z]+Plugin)?([A-Z][^A-Z]+)([A-Za-z]*)Actions$/", get_class($this), $match)) {
                throw new Exception('bad class name for waActions class');
            }

            $view = $this->getView();
            $app_id = $this->getAppId();
            $plugin_root = $this->getPluginRoot();

            // forced that is relative path form application (plugin) templates dir
            if ($this->is_relative) {
                $template = $this->buildTemplatePath($view, $app_id, $template, $plugin_root);
            } else {
                $template_path = strtolower($match[2]) . "/" . $match[2] . $match[3] . $template;
                $template = $this->buildTemplatePath($view, $app_id, $template_path, $plugin_root);
            }

            if ($plugin_root && !file_exists(wa()->getAppPath($template))) {
                $match = array();
                preg_match("/[A-Z][^A-Z]+/", get_class($this), $match);

                $template_path = strtolower($match[0])."/".$match[0].ucfirst($this->action);
                $template2 = $this->buildTemplatePath($view, $app_id, $template_path, $plugin_root);

                if (file_exists(wa()->getAppPath($template2))) {
                    return $template2;
                }
            }
        }

        return $template;
    }

    public function display(array $data, $template = null, $return = false)
    {
        $view = $this->getView();

        if ($template === null) {
            $template = $this->getTemplate();
        }

        // assign vars
        $view->assign($data);

        if ($this->layout && $this->layout instanceof waLayout) {
            // assign result to layout
            $this->layout->setBlock('content', $view->fetch($template));
            $this->layout->display();
        } else {
            // send headers
            $this->getResponse()->sendHeaders();
            // display
            if ($return) {
                return $view->fetch($template);
            } else {
                $view->display($template);
            }
        }
    }

    public function displayJson($data, $errors = null)
    {
        if (waRequest::isXMLHttpRequest()) {
            $this->getResponse()->addHeader('Content-type', 'application/json');
        }
        $this->getResponse()->sendHeaders();
        if (!$errors) {
            echo waUtils::jsonEncode(array('status' => 'ok', 'data' => $data));
        } else {
            echo waUtils::jsonEncode(array('status' => 'fail', 'errors' => $errors, 'data' => $data));
        }
    }

    /**
     * @inheritDoc
     */
    protected function getTemplateDir()
    {
        return 'templates/actions/';
    }

    /**
     * @inheritDoc
     */
    protected function getLegacyTemplateDir()
    {
        return 'templates/actions-legacy/';
    }
}
