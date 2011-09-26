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

require_once dirname(__FILE__).'/../vendors/smarty3/Smarty.class.php';

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
     * 
     * @return Smarty
     */
    public function __construct(waSystem $system, $options = array()) 
    {
    	$this->system = $system;
    	
        $this->smarty = new Smarty();
        if (isset($options['auto_literal'])) {
        	$this->smarty->auto_literal =  $options['auto_literal'];
        }
        if (isset($options['left_delimiter'])) {
			$this->smarty->left_delimiter =  $options['left_delimiter'];
        }
		if (isset($options['right_delimiter'])) {
			$this->smarty->right_delimiter = $options['right_delimiter'];
		}
		$this->smarty->template_dir = isset($options['template_dir']) ? $options['template_dir'] : $this->system->getAppPath();
		$this->smarty->compile_dir = isset($options['compile_dir']) ? $options['compile_dir'] : $this->system->getAppCachePath('templates/compiled/');
		$this->smarty->compile_id = isset($options['compile_id']) ? $this->system->getApp()."_".$options['compile_id'] : $this->system->getApp()."_".$this->system->getUser()->getLocale();
		$this->smarty->cache_dir = $this->system->getAppCachePath('templates/cache/');
		if (ini_get('safe_mode')) {
		    $this->smarty->use_sub_dirs = false;
		} else {
		    $this->smarty->use_sub_dirs = true;
		}
			
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
        
    public function assign($name, $value = null)
    {
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
    	if (!$name) {
        	return $this->smarty->tpl_vars;
    	} else {
    		return isset($this->smarty->tpl_vars[$name]) ? $this->smarty->tpl_vars[$name] : null;
    	}
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
    
    public function setTemplateDir($path)
    {
    	$this->smarty->template_dir = $path;
    }
}