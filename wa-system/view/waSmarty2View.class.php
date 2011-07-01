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
class waSmarty2View extends waView
{
    protected $postfix = '.html';

    /**
     * @var Smarty
     */
    protected $smarty;

    /**
     * @var waSystem
     */
    protected $system;

    /**
     *
     * @return Smarty
     */
    public function __construct(waSystem $system, $options = array())
    {
        $this->system = $system;

        $this->smarty = new Smarty();
        $this->smarty->left_delimiter = isset($options['left_delimiter']) ? $options['left_delimiter'] : "{{";
        $this->smarty->right_delimiter = isset($options['right_delimiter']) ? $options['right_delimiter'] : "}}";
        $this->smarty->template_dir = isset($options['template_dir']) ? $options['template_dir'] : $this->system->getAppPath();
        $this->smarty->compile_dir = isset($options['compile_dir']) ? $options['compile_dir'] : $this->system->getAppCachePath('templates/compiled/');
        $this->smarty->compile_id = isset($options['compile_id']) ? $this->system->getApp()."_".$options['compile_id'] : $this->system->getApp()."_".$this->system->getUser()->getLocale();
        $this->smarty->cache_dir = $this->system->getAppCachePath('templates/cache/');
        if (ini_get('safe_mode')) {
            $this->smarty->use_sub_dirs = false;
        } else {
            $this->smarty->use_sub_dirs = true;
        }
        $this->smarty->load_filter('pre', 'translate');
    }

    public function getPostfix()
    {
        return $this->postfix;
    }

    public function assign($name, $value = null)
    {
        $this->smarty->assign($name, $value);
    }

    public function clearAssign($name)
    {
        $this->smarty->clear_assign($name);
    }

    public function clearAllAssign()
    {
        $this->smarty->clear_all_assign();
    }

    public function getVars($name = null)
    {
        return $this->smarty->get_template_vars($name);
    }

    public function fetch($template, $cache_id = null)
    {
        waConfig::set('current_smarty', $this);
        $this->prepare();
        return $this->smarty->fetch($template, $cache_id);
    }

    public function display($template, $cache_id = null)
    {
        waConfig::set('current_smarty', $this);
        $this->prepare();
        return $this->smarty->display($template, $cache_id);
    }
    
    public function templateExists($template)
    {
    	return $this->smarty->template_exists($template);
    }
    

    public function isCached($template, $cache_id = null)
    {
        return $this->smarty->is_cached($template, $cache_id);
    }

    public function clearCache($template, $cache_id = null)
    {
        return $this->smarty->clear_cache($template, $cache_id);
    }
    
    public function clearAllCache($exp_time = null, $type = null)
    {
    	return $this->smarty->clear_all_cache($exp_time, $type);
    }    

    public function cache($lifetime)
    {
        if ($lifetime) {
            $this->smarty->caching = 2;
            $this->smarty->cache_lifetime = $lifetime;
        } else {
            $this->smarty->caching = false;
        }
    }
}

