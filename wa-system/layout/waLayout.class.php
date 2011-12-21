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
            $theme_path = wa()->getAppPath('themes/', $app_id).$theme;
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
        $this->view->cache(false);
        if ($this->view->autoescape() && $this->view instanceof waSmarty3View) {
            $this->view->smarty->loadFilter('pre', 'content_nofilter');
        }
        $this->view->display($this->getTemplate());
    }
}

