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
class waViewHelper
{
	/**
	 * @var waSystem
	 */
	protected $wa;
	protected $version;
	protected static $helpers = array();
	
	public function __construct()
	{
		$this->wa = wa();
	}
	
	public function header()
	{
		return wa_header();
	}
	
	public function app()
	{
		return $this->wa->getApp();
	}
	
	
	public function user($field = null)
	{
		$user = $this->wa->getUser();
		if ($field !== null) {
			return $user[$field];
		} else {
			return $user;
		}
	}
	
	public function appName()
	{
		$app_info = $this->wa->getAppInfo();
		return $app_info['name'];
	}
	
	public function accountName()
	{
		$app_settings_model = new waAppSettingsModel();
		return $app_settings_model->get('webasyst', 'name', 'Webasyst');
	}
	
	public function module($default = null)
	{
		return waRequest::get('module', $default);
	}
	
	public function css()
	{
		return '<link href="'.$this->wa->getRootUrl().'wa-content/css/wa/wa-1.0.css" rel="stylesheet" type="text/css" >
<!--[if IE 8]><link type="text/css" href="'.$this->wa->getRootUrl().'wa-content/css/wa/wa-1.0.ie8.css" rel="stylesheet"><![endif]-->
<!--[if IE 7]><link type="text/css" href="'.$this->wa->getRootUrl().'wa-content/css/wa/wa-1.0.ie7.css" rel="stylesheet"><![endif]-->'.
		$this->wa->getResponse()->getCss(true);
	}
	
	public function js($include_jquery = true)
	{
		return ($include_jquery ? 
			'<script src="'.$this->wa->getRootUrl().'wa-content/js/jquery/jquery-1.5.2.min.js" type="text/javascript"></script>' :
			'').$this->wa->getResponse()->getJs(true);
	}
	
	public function version($system = false)
	{
		if ($system) {
			$app_info = $this->wa->getAppInfo('webasyst');
			return isset($app_info['version']) ? $app_info['version'] : '0.0.1';
		} else {
			if ($this->version === null) {
				$app_info = $this->wa->getAppInfo();
				$this->version = isset($app_info['version']) ? $app_info['version'] : '0.0.1';
				if (SystemConfig::isDebug()) {
					$this->version .= ".".time();
				} elseif (!$system) {
					$file = $this->wa->getAppPath('lib/config/build.php');
					if (file_exists($file)) {
						$build = include($file);
						$this->version .= '.'.$build;
					}
				}
			}
			return $this->version;		
		}
	}
	
	public function get($name)
	{
		return waRequest::get($name);
	}
	
	public function post($name)
	{
		return waRequest::post($name);
	}	
	
	public function url($backend = false)
	{
		if ($backend) {
			return waSystem::getInstance()->getConfig()->getBackendUrl(true);
		} 
		return wa_url();
	}
	
	public function contacts($hash = null, $fields = 'id,name')
	{
		$collection = new waContactsCollection($hash, array('check_rights' => false));
		return $collection->getContacts($fields);
	}
	
	public function contact($id)
	{
		if (!is_numeric($id)) {
			$collection = new waContactsCollection('/search/'.$id.'/', array('check_rights' => false));
			$result = $collection->getContacts('id', 0, 1);
			if ($result) {
				$c = current($result);
				return new waContact($c['id']);
			} else {
				throw new waException('contact not found', 404);
			}
		}
		return new waContact($id);
	}
	
	public function title($title = null)
	{
		if (!$title) {
			return $this->wa->getResponse()->getTitle();
		} else {
			return $this->wa->getResponse()->setTitle($title);
		}
	}	
	
	public function isMobile()
	{
	    return waRequest::isMobile();
	}
	
	
	public function __get($app)
	{
	    if (!isset(self::$helpers[$app])) {
	        $class = $app.'ViewHelper';
	        if (class_exists($class)) {
	            self::$helpers[$app] = new $class($this->wa);
	        } else {
	            self::$helpers[$app] = null;
	        }
	    }
	    return self::$helpers[$app];
	}
}