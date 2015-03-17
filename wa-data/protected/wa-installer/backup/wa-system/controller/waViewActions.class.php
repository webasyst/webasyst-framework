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
 * @subpackage controller
 */
abstract class waViewActions extends waController
{
    protected $action;
    protected $template;
    protected $template_folder = 'templates/actions/';

    /**
     * @var waLayout
     */
    protected $layout;

    /**
     * @var waSmarty3View
     */
    protected $view;

    /**
     * @var waTheme
     */
    protected $theme;

    /**
     * @var waSystem
     */
    protected $system;

    public function __construct(waSystem $system = null)
    {
        if ($system) {
            $this->system = $system;
        } else {
            $this->system = waSystem::getInstance();
        }
        $this->view = waSystem::getInstance()->getView();
    }

    public function setLayout(waLayout $layout)
    {
        $this->layout = $layout;
    }

    protected function preExecute()
    {

    }

    public function execute($action, $params = null)
    {
        $method = $action.'Action';
        if (method_exists($this, $method)) {
            $this->action = $action;
            $this->$method($params);
        } else {
            throw new waException('Action '.$method.' not found', 404);
        }
    }

    public function postExecute()
    {

    }

    public function run($params = null)
    {
        $action = $params;
        if (!$action) {
            $action = 'default';
        }
        if ($action != 'logout') {
            wa()->getUser()->updateLastPage();
        }
        $this->preExecute();
        if ($this->getRequest()->isMobile() && method_exists($this, $action."MobileAction")) {
            $action = $action."Mobile";
        }
        $this->execute($action);
        $this->postExecute();

        //if ($this->action == $action) {
            $this->display();
        //}

    }

    protected function getTemplate()
    {
        if ($this->template === null) {
            $template = ucfirst($this->action);
        } else {
            // If path contains / or : then it's a full path to template
            if (strpbrk($this->template, '/:') !== false) {
                return $this->template;
            }

            // otherwise it's a template name and we need to figure out its directory
            $template = $this->template;
        }

        $pluginRoot = $this->getPluginRoot();

        $match = array();
        preg_match("/[A-Z][^A-Z]+/", get_class($this), $match);
        $template = $pluginRoot.$this->template_folder.strtolower($match[0])."/".$match[0].$template.$this->view->getPostfix();
        return $template;
    }


    public function setTemplate($template)
    {
        $this->template = $template;
    }

    protected function setThemeTemplate($template)
    {
        $this->template = 'file:'.$template;
        return $this->view->setThemeTemplate($this->getTheme(), $template);
    }

    /**
     * Return current theme
     *
     * @return waTheme
     */
    public function getTheme()
    {
        if ($this->theme == null) {
            $this->theme = new waTheme(waRequest::getTheme());
        }
        return $this->theme;
    }


    public function display()
    {
        if ($this->layout && $this->layout instanceof waLayout) {
            $this->layout->setBlock('content', $this->view->fetch($this->getTemplate()));
            $this->layout->display();
        } else {
            waSystem::getInstance()->getResponse()->sendHeaders();
            $this->view->display($this->getTemplate());
        }
    }
}

// EOF