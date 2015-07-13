<?php
/**
 * Abstract View.
 * Абстрактный Вид.
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
	 * Initialize view properties.
	 * 
     * @param  waSystem $system  Instance of system object
     * @param  array    $options Configuration options
     * @return void
     */
    public function __construct(waSystem $system, $options = array())
    {
        $this->setOptions($options);

        if (wa()->getEnv() == 'frontend') {
            $domain = wa()->getRouting()->getDomain(null, true);
            $domain_config_path = wa()->getConfig()->getConfigPath('domains/' . $domain . '.php', true, 'site');
            if (file_exists($domain_config_path)) {
                $domain_config = include($domain_config_path);
                if (!empty($domain_config['cdn'])) {
                    $this->options['cdn'] = $domain_config['cdn'];
                }
            }
        }

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
     * @param  array $options New configuration options
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
     */
    protected function prepare()
    {
		$wa = wa();

		// Add global variables
		$this->assign(array(
			'wa_url'            => $wa->getRootUrl(),
            'wa_static_url'     => $this->getStaticUrl($wa->getRootUrl()),
			'wa_backend_url'    => waSystem::getInstance()->getConfig()->getBackendUrl(true),
			'wa_app'            => $wa->getApp(),
			'wa_app_url'        => $wa->getAppUrl(null, true),
			'wa_app_static_url' => $this->getStaticUrl($wa->getAppStaticUrl()),
			'wa'                => $this->getHelper()
		));

		// "Chainable" method
		return $this;
    }

    protected function getStaticUrl($url)
    {
        if (!empty($this->options['cdn'])) {
            return rtrim($this->options['cdn'], '/').$url;
        }
        return $url;
    }

    abstract public function fetch($template, $cache_id = null);

    abstract public function display($template, $cache_id = null);

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
     * @param  waTheme $theme    Instance of theme object
     * @param  string  $template Path to template or resource string specifying template
     * @return bool
     */
    public function setThemeTemplate($theme, $template)
    {
        $this->assign('wa_active_theme_path', $theme->path);
        $this->assign('wa_active_theme_url', $this->getStaticUrl($theme->url));
        $theme_settings = $theme->getSettings(true);
        $theme_settings_config = $theme->getSettings();

        $locales = $theme->getLocales();

        $version = $theme->version(true);

        $file = $theme->getFile($template);
        if ($parent_theme = $theme->parent_theme) {
            $edition = $theme->edition + $parent_theme->edition;
            if (!empty($file['parent'])) {
                if ($parent_theme->version($edition) > $version) {
                    $version = $parent_theme->version($edition);
                } else {
                    $version = $theme->version($edition);
                }
                $theme = $parent_theme;
            }
            $this->assign('wa_parent_theme_url', $this->getStaticUrl($parent_theme->url));
            $this->assign('wa_parent_theme_path', $parent_theme->path);
            if ($parent_settings = $parent_theme->getSettings(true)) {
                $theme_settings = $theme_settings + $parent_settings;
                foreach ($parent_theme->getSettings() as $k => $v) {
                    if (!isset($theme_settings_config[$k])) {
                        $v['parent'] = 1;
                        $theme_settings_config[$k] = $v;
                    }
                }
            }
            if ($parent_theme->getLocales()) {
                $locales += $parent_theme->getLocales();
            }
        }
        $this->assign('wa_theme_version', $version);
        waLocale::setStrings($locales);
        $this->assign('theme_settings', $theme_settings);
        $this->assign('theme_settings_config', $theme_settings_config);
        $this->assign('wa_theme_url', $this->getStaticUrl($theme->url));
        $this->setTemplateDir($theme->path);
        return file_exists($theme->path.'/'.$template);
    }
}
