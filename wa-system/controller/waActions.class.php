<?php

abstract class waActions extends waController
{
    protected $action;
    /**
     * @var waLayout
     */
    protected $layout;

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

    protected function getTemplate()
    {
        $template = ucfirst($this->action);

        if (strpbrk($template, '/:') === false) {
            $match = array();
            if (!preg_match("/^[a-z]+([A-Z][a-z]+Plugin)?([A-Z][^A-Z]+)([A-Za-z]*)Actions$/", get_class($this), $match)) {
                throw new Exception('bad class name for waActions class');
            }
            $template = $this->getPluginRoot().'templates/actions/'.strtolower($match[2])."/".$match[2].$match[3].$template.$this->getView()->getPostfix();
            if ($this->getPluginRoot() && !file_exists(wa()->getAppPath($template))) {
                $match = array();
                preg_match("/[A-Z][^A-Z]+/", get_class($this), $match);
                $template2 = $this->getPluginRoot().'templates/actions/'.strtolower($match[0])."/".$match[0].ucfirst($this->action).$this->getView()->getPostfix();
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
}
