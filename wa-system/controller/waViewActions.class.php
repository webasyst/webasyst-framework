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
        if ($this->getRequest()->isMobile() && method_exists($this, $action."MobileAction")) {
            $action = $action."Mobile";
        }

        $this->action = $action;
        $this->preExecute();
        $this->execute($this->action);
        $this->postExecute();

        $this->display();
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
        preg_match("/([A-Z][^A-Z]+)/", get_class($this), $match);
        if ($pluginRoot) {
            $old_style_match = $match;
            $match = array();
            preg_match("/Plugin([A-Z][^A-Z]+)/", get_class($this), $match);
        }

        $full_template = $pluginRoot.$this->template_folder.strtolower($match[1])."/".$match[1].$template.$this->view->getPostfix();
        if (!$pluginRoot || file_exists(wa()->getAppPath().'/'.$full_template)) {
            return $full_template;
        }

        // There used to be a bug that made this class look for plugin templates in the wrong place.
        // The bug was fixed, and the path calculated above should go for all modern uses.
        // But for compatibility with older plugins we still check for a template in old place.
        $full_template2 = $pluginRoot.$this->template_folder.strtolower($old_style_match[1])."/".$old_style_match[1].$template.$this->view->getPostfix();
        if (file_exists(wa()->getAppPath().'/'.$full_template2)) {
            return $full_template2;
        }

        return $full_template;
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
     * @throws waException
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