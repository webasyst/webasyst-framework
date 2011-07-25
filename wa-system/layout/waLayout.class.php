<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 */
class waLayout extends waController
{

    protected $blocks = array();
    protected $template = null;
    /**
     * @var waSmartyView
     */
    protected $view;

    public function __construct()
    {
        $this->view = waSystem::getInstance()->getView();
    }


    public function setBlock($name, $content)
    {
        if (isset($this->blocks[$name])) {
            $this->blocks[$name] .= $content;
        } else {
            $this->blocks[$name] = $content;
        }
    }

    public function executeAction($name, $action, waDecorator $decorator = null)
    {
        $action->setLayout($this);
        $content = $decorator ? $decorator->display($action) : $action->display();
        $this->setBlock($name, $content);
    }

    protected function getTemplate()
    {
        if ($this->template === null) {
            $prefix = waSystem::getInstance()->getConfig()->getPrefix();
            $template = substr(get_class($this), strlen($prefix), -6);
            return 'templates/layouts/' . $template . $this->view->getPostfix();
        } else {
            if (strpbrk($this->template, '/:') !== false) {
                return $this->template;
            }
            return 'templates/layouts/' . $this->template . $this->view->getPostfix();
        }
    }
    
    public function assign($name, $value)
    {
    	$this->blocks[$name] = $value;
    }

    public function execute()
    {

    }
    

    public function display()
    {
        $this->execute();
        $this->view->assign($this->blocks);
        waSystem::getInstance()->getResponse()->sendHeaders();
        $this->view->display($this->getTemplate());
    }
}

