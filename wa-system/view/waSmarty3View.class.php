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
     * @var waSystem
     */
    protected $system;

    /**
     * @param waSystem $system
     * @param array $options
     * @return waSmarty3View
     */
    public function __construct(waSystem $system, $options = array()) 
    {
        parent::__construct($system, $options);

        $this->smarty = new Smarty();
        $this->setOptions($options);
        if (isset($options['auto_literal'])) {
            $this->smarty->auto_literal =  $options['auto_literal'];
        }
        if (isset($options['left_delimiter'])) {
            $this->smarty->left_delimiter =  $options['left_delimiter'];
        }
        if (isset($options['right_delimiter'])) {
            $this->smarty->right_delimiter = $options['right_delimiter'];
        }
        $this->smarty->setTemplateDir(isset($options['template_dir']) ? $options['template_dir'] : $this->system->getAppPath());
        $this->smarty->setCompileDir(isset($options['compile_dir']) ? $options['compile_dir'] : $this->system->getAppCachePath('templates/compiled/'));
        $this->smarty->setCacheDir($this->system->getAppCachePath('templates/cache/'));
        if (ini_get('safe_mode')) {
            $this->smarty->use_sub_dirs = false;
        } else {
            $this->smarty->use_sub_dirs = true;
        }
        // not use
        //$this->smarty->setCompileCheck(wa()->getConfig()->isDebug()?true:false);

        $this->smarty->addPluginsDir($this->system->getConfig()->getPath('system').'/vendors/smarty-plugins');
        $this->smarty->loadFilter('pre', 'translate');


    }
    
    public function setOptions($options)
    {
        foreach ($options as $k => $v) {
            $this->options[$k] = $v;
            switch ($k) {
                case "left_delimiter":
                case "right_delimiter":
                    $this->smarty->$k = $v;
                    break;
            }
        }
    }

    protected function prepare()
    {
           $this->smarty->compile_id = isset($this->options['compile_id']) ?
               $this->system->getApp()."_".$this->options['compile_id'] :
               $this->system->getApp()."_".$this->system->getLocale();
           parent::prepare();
    }
        
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
    
    public function clearAssign($name)
    {
        $this->smarty->clearAssign($name);       
    }
    
    public function clearAllAssign()
    {
        $this->smarty->clearAllAssign();
    }

    public function getVars($name = null) 
    {
        return $this->smarty->getTemplateVars($name);
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
        return $this->smarty->templateExists($template);
    }
    
    public function isCached($template, $cache_id = null)
    {
        return $this->smarty->isCached($template, $cache_id);
    }
    
    public function clearAllCache($exp_time = null, $type = null)
    {
        return $this->smarty->clearAllCache($exp_time, $type);
    }
    
    public function clearCache($template, $cache_id = null)
    {
        return $this->smarty->clearCache($template, $cache_id);
    }
    
    public function cache($lifetime)
    {
        if ($lifetime) {
            $this->smarty->caching = Smarty::CACHING_LIFETIME_SAVED;
            $this->smarty->cache_lifetime = $lifetime;
        } else {
            $this->smarty->caching = false;
        }
    }
    
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
    
    public function setTemplateDir($path)
    {
        $this->smarty->setTemplateDir($path);
    }
    
    public function autoescape($value = null)
    {
        if ($value === null) {
            return $this->smarty->escape_html;   
        } else {
            return $this->smarty->escape_html = $value;
        }
    }
}
