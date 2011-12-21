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
    protected $cache_time = null;
    protected $cache_id = null;

    protected $title = "";
    protected $template = null;
    protected $params = null;

    protected $controller = null;
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
    }

    protected function setThemeTemplate($template, $theme = null)
    {
        if ($theme === null) {
            $theme = $this->getTheme();
        }
        if (strpos($theme, ':') !== false) {
            list($app_id, $theme) = explode(':', $theme, 2);
        } else {
            $app_id = null;
        }
        $theme_path = wa()->getDataPath('themes', true, $app_id).'/'.$theme;
        if (!file_exists($theme_path) || !file_exists($theme_path.'/theme.xml')) {
            $theme_path = wa()->getAppPath().'/themes/'.$theme;
            $this->view->assign('wa_theme_url', wa()->getAppStaticUrl($app_id).'themes/'.$theme.'/');
        } else {
            $this->view->assign('wa_theme_url', wa()->getDataUrl('themes', true, $app_id).'/'.$theme.'/');
        }
        $this->view->setTemplateDir($theme_path);
        $this->template = 'file:'.$template;
        return file_exists($theme_path.'/'.$template);
    }
    
    private function themeExists($theme)
    {
        $theme_path = wa()->getDataPath('themes', true).'/'.$theme;
        if (file_exists($theme_path) && file_exists($theme_path.'/theme.xml')) {
            return true;
        }
        return file_exists(wa()->getAppPath().'/themes/'.$theme);
    }
 

    protected function getTheme()
    {
        $key = $this->getConfig()->getApplication();
        $key .= '/'.wa()->getRouting()->getDomain().'/theme';
        if (($theme_hash = waRequest::get('theme_hash')) && ($theme = waRequest::get('set_force_theme')) !== null) {
            $app_settings_model = new waAppSettingsModel();
            $hash = $app_settings_model->get('site', 'theme_hash');
            if ($theme_hash == md5($hash)) {
                if ($theme && $this->themeExists($theme)) {
                    wa()->getStorage()->set($key, $theme);
                    return $theme;
                } else {
                    wa()->getStorage()->del($key);
                }
            }
        } elseif (($theme = wa()->getStorage()->get($key)) && $this->themeExists($theme)) {
            return $theme;
        }
        if (waRequest::isMobile()) {
            return waRequest::param('theme_mobile', 'default');
        }
        return waRequest::param('theme', 'default');
    }

    protected function getThemeUrl()
    {
        $theme = $this->getTheme();
        $theme_path = wa()->getDataPath('themes', true).'/'.$theme;
        if (!file_exists($theme_path) || !file_exists($theme_path.'/theme.xml')) {
            return wa()->getAppStaticUrl().'/themes/'.$theme.'/';
        }
        return wa()->getDataUrl('themes/'.$theme.'/', true);
    }

    /**
     *
     * @return waViewController
     */
    public function getController()
    {
        return $this->controller;
    }

    public function setLayout(waLayout $layout=null)
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
        preg_match("/^[A-Z]?[^A-Z]+/", $template, $match);
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
        if ($this->cache_time && $this->isCached())  {
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

    public function getLayout()
    {
        return $this->layout;
    }
}

