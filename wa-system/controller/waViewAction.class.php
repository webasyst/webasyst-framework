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

abstract class waViewAction extends waController
{
    /**
     * @var waView
     */
    protected $view;
    /**
     * @var waTheme
     */
    protected $theme;
    protected $cache_time = null;
    protected $cache_id = null;

    protected $title = "";
    protected $template = null;
    protected $params = null;

    /**
     * @var waViewController
     */
    protected $controller = null;
    /**
     * @var waLayout
     */
    protected $layout = null;

    public function __construct($params = null)
    {
        $this->view = waSystem::getInstance()->getView();
        $this->params = $params;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setController(waController $controller)
    {
        $this->controller = $controller;
        if ($this->controller instanceof waViewController) {
            $layout = $this->controller->getLayout();
            if ($layout) {
                $this->layout = $layout;
            }
        }
    }

    protected function setThemeTemplate($template)
    {
        $this->template = 'file:'.$template;
        return $this->view->setThemeTemplate($this->getTheme(), $template);
    }

    protected function getThemeUrl()
    {
        if (wa()->getEnv() == 'frontend') {
            $domain = wa()->getRouting()->getDomain(null, true);
            $domain_config_path = wa()->getConfig()->getConfigPath('domains/' . $domain . '.php', true, 'site');
            if (file_exists($domain_config_path)) {
                $domain_config = include($domain_config_path);
                if (!empty($domain_config['cdn'])) {
                    return rtrim($domain_config['cdn'], '/').$this->getTheme()->getUrl();
                }
            }
        }
        return $this->getTheme()->getUrl();
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

    /**
     *
     * @return waViewController
     */
    public function getController()
    {
        return $this->controller;
    }

    public function setLayout(waLayout $layout = null)
    {
        if ($this->controller !== null && $this->controller instanceof waViewController) {
            $this->controller->setLayout($layout);
        }
        $this->layout = $layout;
    }

    public function execute()
    {

    }

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    protected function getTemplate()
    {
        $plugin_root = $this->getPluginRoot();

        // Use template set up by a subclass, if any
        if ($this->template === null) {
            // figure it out by a class name by default
            $prefix = waSystem::getInstance()->getConfig()->getPrefix();
            $template = substr(get_class($this), strlen($prefix), -6);

            if ($plugin_root) {
                $template = preg_replace("~^.*Plugin~", '', $template);
            }
        } else {
            // If path contains / or : then it's a full path to template
            if (strpbrk($this->template, '/:') !== false) {
                return $this->template;
            }

            // otherwise it's a template name and we need to figure out its directory
            $template = $this->template;
        }

        // Path inside /templates dir is determined by template name prefix
        $match = array();
        preg_match("/^[A-Z]?[^A-Z]*/", $template, $match);
        $template = 'actions/'.strtolower($match[0])."/".$template.$this->view->getPostfix();
        return $plugin_root.'templates/'.$template;
    }

    protected function isCached()
    {
        return $this->view->isCached($this->getTemplate(), $this->cache_id);
    }

    public function display($clear_assign = true)
    {
        $this->view->cache($this->cache_time);
        if ($this->cache_time && $this->isCached()) {
            return $this->view->fetch($this->getTemplate(), $this->cache_id);
        } else {
            if (!$this->cache_time && $this->cache_id) {
                $this->view->clearCache($this->getTemplate(), $this->cache_id);
            }
            $this->execute();
            $result = $this->view->fetch($this->getTemplate(), $this->cache_id);
            if ($clear_assign) {
                $this->view->clearAllAssign();
            }            
            return $result;
        }
    }

    /**
     *
     * @return waLayout
     */
    public function getLayout()
    {
        return $this->layout;
    }
}
