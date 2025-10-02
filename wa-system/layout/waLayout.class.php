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
    use waActionTemplatePathBuilder;

    protected $blocks = array();
    protected $template = null;

    /**
     * @var waSmarty3View
     */
    protected $view;
    /**
    * @var waTheme
    */
    protected $theme;


    public function __construct()
    {
        $this->view = waSystem::getInstance()->getView();

        if (wa()->getEnv() == 'frontend') {
            // save utm to cookie
            $utm = array();
            foreach (waRequest::get() as $k => $v) {
                if (substr($k, 0, 4) == 'utm_') {
                    $utm[substr($k, 4)] = $v;
                }
            }
            if ($utm) {
                // save utm to cookie
                wa()->getResponse()->setCookie('utm', json_encode($utm), time() + 90 * 86400, null, '', false, true);
            }
            // save referer
            if ($ref = waRequest::server('HTTP_REFERER')) {
                $ref_host = @parse_url($ref, PHP_URL_HOST);
                if ($ref_host != waRequest::server('HTTP_HOST')) {
                    wa()->getResponse()->setCookie('referer', waRequest::server('HTTP_REFERER'), time() + 90 * 86400, null, '', false, true);
                }
            }
            // save landing page
            if (!waRequest::cookie('landing')) {
                wa()->getResponse()->setCookie('landing', waRequest::server('REQUEST_URI'), 0, null, '', false, true);
            }
        }
    }


    public function setBlock($name, $content)
    {
        if (isset($this->blocks[$name])) {
            $this->blocks[$name] .= $content;
        } else {
            $this->blocks[$name] = $content;
        }
    }

    /**
     * @param string $name
     * @param waViewAction $action
     * @param waDecorator $decorator
     */
    public function executeAction($name, $action, ?waDecorator $decorator = null)
    {
        $action->setLayout($this);
        $content = $decorator ? $decorator->display($action) : $action->display();
        $this->setBlock($name, $content);
    }

    /**
     * Get template path for current layout in current app (or plugin)
     * @return string
     * @throws waException
     */
    protected function getTemplate()
    {
        $app_id = $this->getAppId();

        if ($this->template === null) {
            $prefix = waSystem::getInstance()->getConfig()->getPrefix();
            $template = substr(get_class($this), strlen($prefix), -6);
            if (strpos($template, 'Plugin') !== false) {
                $plugin_root = $this->getPluginRoot();
                if ($plugin_root) {
                    $template = preg_replace("~^.*Plugin~", '', $template);
                    return $this->buildTemplatePath($this->view, $app_id, $template, $plugin_root);
                }
            }
            return $this->buildTemplatePath($this->view, $app_id, $template);
        } else {
            if (strpbrk($this->template, '/:') !== false) {
                return $this->template;
            }
            return $this->buildTemplatePath($this->view, $app_id, $this->template);
        }
    }

    protected function setThemeTemplate($template)
    {
        $this->template = 'file:'.$template;
        return $this->view->setThemeTemplate($this->getTheme(), $template);
    }

    protected function getThemeUrl()
    {
        return $this->getTheme()->getUrl();
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

        if ((wa()->getEnv() == 'frontend') && waRequest::param('theme_mobile') &&
            (waRequest::param('theme') != waRequest::param('theme_mobile'))) {
            wa()->getResponse()->addHeader('Vary', 'User-Agent');
        }
        $this->view->cache(false);
        if ($this->view->autoescape() && $this->view instanceof waSmarty3View) {
            $this->view->smarty->loadFilter('pre', 'content_nofilter');
        }

        // fetch() is slightly slower because of output filtering,
        // but it allows to modify page headers from inside theme templates.
        $html = $this->view->fetch($this->getTemplate());
        wa()->getResponse()->sendHeaders();
        echo $html;
    }

    /**
     * @inheritDoc
     */
    protected function getTemplateDir()
    {
        return 'templates/layouts/';
    }

    /**
     * @inheritDoc
     */
    protected function getLegacyTemplateDir()
    {
        return 'templates/layouts-legacy/';
    }
}
