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
abstract class waView
{
	
	protected $postfix = '.html';
		
    /**
     * @var waSystem
     */
    protected $system;
    
	public function __construct(waSystem $system, $options = array())
	{
		$this->system = $system;	
	}
	
    public function getPostfix()
    {
    	return $this->postfix;
    }	
	
	abstract public function assign($name, $value = null);
	
	abstract public function clearAssign($name);
	
	abstract public function clearAllAssign();
	
	abstract public function getVars($name = null);
	
	protected function prepare() 
	{
   	   $this->assign('wa_url', $this->system->getRootUrl());
   	   $this->assign('wa_backend_url', waSystem::getInstance()->getConfig()->getBackendUrl(true));
   	   $this->assign('wa_app', $this->system->getApp());
   	   $this->assign('wa_app_url', $this->system->getAppUrl());
   	   $this->assign('wa_app_static_url', $this->system->getAppStaticUrl());
   	   $this->assign('wa', new waViewHelper());
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
		
	}
	
	public function clearAllCache($exp_time = null, $type = null)
	{
		
	}
	
	public function cache($lifetime) 
	{
		
	}
}