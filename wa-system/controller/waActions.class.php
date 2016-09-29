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
            $this->action = $action;
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
        $this->preExecute();
        $this->execute($action);
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
            preg_match("/[A-Z][^A-Z]+/", get_class($this), $match);
            $template = $this->getPluginRoot().'templates/actions/'.
                strtolower($match[0])."/".$match[0].$template.$this->getView()->getPostfix();
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
            echo json_encode(array('status' => 'ok', 'data' => $data));
        } else {
            echo json_encode(array('status' => 'fail', 'errors' => $errors, 'data' => $data));
        }
    }
}
