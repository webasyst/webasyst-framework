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
 * @subpackage view
 */

require_once realpath(dirname(__FILE__).'/../').'/vendors/smarty3/Smarty.class.php';

class waSmarty3View extends waView
{
    protected $postfix = '.html';

    /**
     * @var Smarty
     */
    public $smarty;

    /**
     * @param waSystem $system
     * @param array $options
     * @throws SmartyException
     */
    public function __construct(waSystem $system, $options = array())
    {
        $this->smarty = new Smarty();

        parent::__construct($system, $options);

        if (isset($options['auto_literal'])) {
            $this->smarty->auto_literal = $options['auto_literal'];
        }
        if (isset($options['left_delimiter'])) {
            $this->smarty->left_delimiter = $options['left_delimiter'];
        }
        if (isset($options['right_delimiter'])) {
            $this->smarty->right_delimiter = $options['right_delimiter'];
        }
        $this->smarty->setTemplateDir(isset($options['template_dir']) ? $options['template_dir'] : $system->getAppPath());
        $this->smarty->setCompileDir(isset($options['compile_dir']) ? $options['compile_dir'] : $system->getAppCachePath('templates/compiled/'));
        $this->smarty->setCacheDir($system->getAppCachePath('templates/cache/'));
        if (ini_get('safe_mode')) {
            $this->smarty->use_sub_dirs = false;
        } else {
            $this->smarty->use_sub_dirs = true;
        }
        // not use
        //$this->smarty->setCompileCheck(wa()->getConfig()->isDebug()?true:false);

        $this->smarty->addPluginsDir($system->getConfig()->getPath('system').'/vendors/smarty-plugins');
        $this->smarty->loadFilter('pre', 'translate');
    }

    /**
     * @param array $options
     * @return void|waView
     */
    public function setOptions($options)
    {
        foreach ($options as $k => $v) {
            $this->options[$k] = $v;
            switch ($k) {
                case "left_delimiter":
                    $this->smarty->left_delimiter = $v;
                    break;
                case "right_delimiter":
                    $this->smarty->right_delimiter = $v;
                    break;
            }
        }
    }

    /**
     * @return void|waView
     * @throws waException
     */
    protected function prepare()
    {
        $this->setCompileId();
        parent::prepare();
    }

    /**
     * @param $name
     * @param null $value
     * @param bool $escape
     */
    public function assign($name, $value = null, $escape = false)
    {
        if ($escape) {
            if (is_array($value)) {
                $value = array_map('htmlspecialchars', $value);
            } else {
                $value = htmlspecialchars($value);
            }
        }
        $this->smarty->assign($name, $value);
    }

    /**
     * @param $name
     */
    public function clearAssign($name)
    {
        $this->smarty->clearAssign($name);
    }

    /**
     *
     */
    public function clearAllAssign()
    {
        $this->smarty->clearAllAssign();
    }

    /**
     * @param null $name
     * @return string
     */
    public function getVars($name = null)
    {
        return $this->smarty->getTemplateVars($name);
    }

    /**
     * @param $template
     * @param null $cache_id
     * @return string
     * @throws SmartyException
     * @throws waException
     */
    public function fetch($template, $cache_id = null)
    {
        $is_template = $this->setTemplateAndTheme();
        try {
            $result = $this->smarty->fetch($template, $cache_id);
            $this->resetTemplateAndTheme($is_template);
        } catch (Exception $ex) {
            $this->resetTemplateAndTheme($is_template);
            throw $ex;
        }

        return $result;
    }

    /**
     * @param $template
     * @param null $cache_id
     * @throws waException
     */
    public function display($template, $cache_id = null)
    {
        $is_template = $this->setTemplateAndTheme();
        $this->smarty->display($template, $cache_id);
        $this->resetTemplateAndTheme($is_template);
    }

    /**
     * @return mixed|null
     * @throws waException
     */
    protected function setTemplateAndTheme()
    {
        waConfig::set('current_smarty', $this);
        $this->prepare();
        $is_template = waConfig::get('is_template');
        waConfig::set('is_template', true);

        wa()->pushActiveTheme($this->getActiveThemes());
        return $is_template;
    }

    /**
     * @param $is_template
     * @throws waException
     */
    protected function resetTemplateAndTheme($is_template)
    {
        wa()->popActiveTheme($this->getActiveThemes());
        waConfig::set('is_template', $is_template);
    }

    /**
     * @param $template
     * @return bool
     */
    public function templateExists($template)
    {
        return $this->smarty->templateExists($template);
    }

    /**
     * @param $template
     * @param null $cache_id
     * @return bool
     * @throws waException
     */
    public function isCached($template, $cache_id = null)
    {
        $this->setCompileId();
        return $this->smarty->isCached($template, $cache_id);
    }

    /**
     * @param null $exp_time
     * @param null $type
     * @return int|waView
     */
    public function clearAllCache($exp_time = null, $type = null)
    {
        return $this->smarty->clearAllCache($exp_time, $type);
    }

    /**
     * @param $template
     * @param null $cache_id
     * @return int|waView
     */
    public function clearCache($template, $cache_id = null)
    {
        return $this->smarty->clearCache($template, $cache_id);
    }

    /**
     * @param $lifetime
     * @return void|waView
     */
    public function cache($lifetime)
    {
        if ($lifetime) {
            $this->smarty->caching = Smarty::CACHING_LIFETIME_SAVED;
            $this->smarty->cache_lifetime = $lifetime;
        } else {
            $this->smarty->caching = false;
        }
    }

    /**
     * @return string|null
     */
    public function getCacheId()
    {
        if ($this->smarty->parent && $this->smarty->parent->getTemplateVars('cache_id')) {
            $cache_id = $this->smarty->parent->getTemplateVars('cache_id');
        } else {
            $cache_id = null;
        }
        if ($cache_id && isset($cache_id->value)) {
            $cache_id = $cache_id->value;
        }
        return $cache_id;
    }

    /**
     * @param $path
     * @return void|waView
     */
    public function setTemplateDir($path)
    {
        $this->smarty->setTemplateDir($path);
    }

    /**
     * @param null $value
     * @return bool|waView|null
     */
    public function autoescape($value = null)
    {
        if ($value === null) {
            return $this->smarty->escape_html;
        } else {
            return $this->smarty->escape_html = $value;
        }
    }

    /**
     * @throws waException
     */
    private function setCompileId()
    {
        $this->smarty->compile_id = isset($this->options['compile_id']) ?
            wa()->getApp()."_".$this->options['compile_id'] :
            wa()->getApp()."_".wa()->getLocale();
    }
}
