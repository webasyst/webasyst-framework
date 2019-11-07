<?php

/**
 * Abstract View.
 *
 * @package   wa-system
 * @category  view
 * @author    Webasyst LLC
 * @copyright 2014 Webasyst LLC
 * @license   http://webasyst.com/framework/license/ LGPL
 */
abstract class waView
{
    /**
     * @var string Template extension
     */
    protected $postfix = '.html';

    /**
     * @var array Configuration options
     */
    protected $options = array();

    /**
     * @var waViewHelper Helper object
     */
    protected $helper;

    /**
     * @var string[] locale_domain
     */
    protected $themes = [];

    /**
     * Initialize view properties.
     *
     * @param waSystem $system Instance of system object
     * @param array $options Configuration options
     * @return void
     */
    public function __construct(waSystem $system, $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Get helper object.
     *
     * @return waViewHelper
     */
    public function getHelper()
    {
        if (!isset($this->helper)) {
            // Lazy load
            $this->helper = new waViewHelper($this);
        }
        return $this->helper;
    }

    /**
     * Set view options.
     *
     * @param array $options New configuration options
     * @return waView
     */
    public function setOptions($options)
    {
        foreach ($options as $k => $v) {
            $this->options[$k] = $v;
        }
        // "Chainable" method
        return $this;
    }

    /**
     * Get template extension.
     *
     * @return string
     */
    public function getPostfix()
    {
        return $this->postfix;
    }

    abstract public function assign($name, $value = null, $escape = false);

    abstract public function clearAssign($name);

    abstract public function clearAllAssign();

    abstract public function getVars($name = null);

    /**
     * Execute prepare render temaplate.
     *
     * @return waView
     * @throws waException
     */
    protected function prepare()
    {
        $wa = wa();

        // Add global variables
        $this->assign(array(
            'wa_url'                 => $wa->getRootUrl(),
            'wa_static_url'          => $this->getStaticUrl($wa->getRootUrl()),
            'wa_backend_url'         => waSystem::getInstance()->getConfig()->getBackendUrl(true),
            'wa_app'                 => $wa->getApp(),
            'wa_app_url'             => $wa->getAppUrl(null, true),
            'wa_app_static_url'      => $this->getStaticUrl($wa->getAppStaticUrl()),
            'wa_real_app_static_url' => $wa->getAppStaticUrl(),
            'wa'                     => $this->getHelper()
        ));

        // "Chainable" method
        return $this;
    }

    protected function getStaticUrl($url)
    {
        return wa()->getCdn($url);
    }

    abstract public function fetch($template, $cache_id = null);

    abstract public function display($template, $cache_id = null);

    /**
     * Render some template without any influence on current assign scope
     *
     * @param string $template
     * @param array $assign
     * @param bool $capture - Capture current assign vars or not. By default is FALSE
     * @param mixed|null $cache_id
     * @return mixed
     */
    public function renderTemplate($template, $assign = array(), $capture = false, $cache_id = null)
    {
        $old_vars = $this->getVars();
        if (!$capture) {
            $this->clearAllAssign();
        }
        $this->assign($assign);
        $html = $this->fetch($template, $cache_id);
        $this->clearAllAssign();
        $this->assign($old_vars);
        return $html;
    }

    abstract public function templateExists($template);

    public function isCached($template, $cache_id = null)
    {
        return false;
    }

    public function clearCache($template, $cache_id = null)
    {
        // "Chainable" method
        return $this;
    }

    public function clearAllCache($exp_time = null, $type = null)
    {
        // "Chainable" method
        return $this;
    }

    public function cache($lifetime)
    {
        // "Chainable" method
        return $this;
    }

    public function getCacheId()
    {
        return null;
    }

    public function autoescape($value = null)
    {
        // "Chainable" method
        return $this;
    }

    public function setTemplateDir($path)
    {
        // "Chainable" method
        return $this;
    }

    /**
     * Set template directory and global valiables.
     *
     * @param waTheme $theme Instance of theme object
     * @param string $template Path to template or resource string specifying template
     * @return bool
     * @throws waException
     */
    public function setThemeTemplate($theme, $template)
    {
        // Upstairs because the topic can be redefined
        $this->assign('wa_active_theme_path', $theme->path);
        $this->assign('wa_active_theme_url', $this->getStaticUrl($theme->url));
        $this->assign('wa_real_active_theme_url', $theme->url);

        $theme_settings = $theme->getSettings(true);
        $theme_settings_config = $theme->getSettings();
        $version = $theme->version(true);
        $file = $theme->getFile($template);
        $parent_theme = $theme->parent_theme;

        $this->setActiveTheme($theme->locale_domain);
        $this->setLocales($theme, $parent_theme);

        if ($parent_theme) {
            $this->setActiveTheme($parent_theme->locale_domain);
            $edition = $theme->edition + $parent_theme->edition;
            if (!empty($file['parent'])) {
                if ($parent_theme->version($edition) > $version) {
                    $version = $parent_theme->version($edition);
                } else {
                    $version = $theme->version($edition);
                }
                // !!! Reset main theme !!!
                $theme = $parent_theme;
            }
            $parent_settings = $parent_theme->getSettings(true);
            if ($parent_settings) {
                $theme_settings = $theme_settings + $parent_settings;
                foreach ($parent_theme->getSettings() as $setting_name => $setting_data) {
                    if (!isset($theme_settings_config[$setting_name])) {
                        $setting_data['parent'] = 1;
                        $theme_settings_config[$setting_name] = $setting_data;
                    }
                }
            }

            $this->assign('wa_parent_theme_url', $this->getStaticUrl($parent_theme->url));
            $this->assign('wa_real_parent_theme_url', $parent_theme->url);
            $this->assign('wa_parent_theme_path', $parent_theme->path);
        }
        $this->assign('wa_theme_version', $version);
        $this->assign('theme_settings', $theme_settings);
        $this->assign('theme_settings_config', $theme_settings_config);
        $this->assign('wa_theme_url', $this->getStaticUrl($theme->url));
        $this->assign('wa_real_theme_url', $theme->url);

        $this->setTemplateDir($theme->path);
        return file_exists($theme->path.'/'.$template);
    }

    /**
     * Here are the strings in the domain format for gettext
     * @return string[]
     */
    public function getActiveThemes()
    {
        return $this->themes;
    }

    /**
     * @param string $theme_domain
     */
    public function setActiveTheme($theme_domain)
    {
        $this->themes[] = $theme_domain;
    }

    /**
     * @param waTheme $theme
     * @param waTheme $parent_theme
     * @throws waException
     */
    protected function setLocales(waTheme $theme, $parent_theme = null)
    {
        $locales = $theme->getLocales();
        $locale = wa()->getLocale();

        if ($parent_theme instanceof waTheme) {
            $parent_locales = $parent_theme->getLocales();

            if ($parent_locales) {
                $locales += $parent_locales;
            }
            $this->loadWrapper($locale, $parent_theme->locale_path, $parent_theme->locale_domain, false);
        }

        // load main theme locale
        $this->loadWrapper($locale, $theme->locale_path, $theme->locale_domain, false);

        // set lines from xml file
        $this->setStringsWrapper($locales);
    }

    /**
     * waLocale::load wrapper
     * Need for unitTests
     * @param $locale
     * @param $locale_path
     * @param $domain
     * @param bool $textdomain
     */
    protected function loadWrapper($locale, $locale_path, $domain, $textdomain = true)
    {
        waLocale::load($locale, $locale_path, $domain, $textdomain);
    }

    /**
     * waLocale::setStrings wrapper
     * Need for unit Tests
     * @param $locales
     */
    protected function setStringsWrapper($locales)
    {
        waLocale::setStrings($locales);
    }

}
