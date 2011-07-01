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
 * @subpackage config
 */
class waSystemConfig
{
	protected $root_path = null;
	protected $enviroment = null;
	protected static $root_url = null;
	
	protected static $active = null;
	protected static $debug = false;
	
	protected static $system_options = array(
		'backend_url' => 'webasyst',
		'mod_rewrite' => true
	);
	
	public static $time = null;
	
	protected $factories = array();
	
	protected static $helpers = false;
	
	
	public function __construct($enviroment = null, $root_path = null)
	{
		self::$time = microtime(true);
		if (self::$active == null || $this instanceof waAppConfig) {
			self::$active = $this;
		}
		
		
		if ($root_path === null) {
			$this->root_path = realpath(dirname(__FILE__).'/../..');
		} else {
			$this->root_path = realpath($root_path);
		}	
		$this->setPath($this->root_path);

		if (!self::$helpers) {
			self::$helpers = true;
			include($this->getRootPath()."/wa-system/helper/load.php");
		}
		
		$this->configure();
		$this->init();
		
		if ($enviroment === null) {
			$url = explode("/", $this->getRequestUrl(true));
			$url = $url[0];
			
			$this->enviroment = $url === $this->getSystemOption('backend_url') ? 'backend' : 'frontend';
		} else {
			$this->enviroment = $enviroment;
		}
		
	}
	
	public static function getTime($diff = true)
	{
		if ($diff) {
			return microtime(true) - self::$time;
		} else {
			return self::$time;
		}
	}

	protected function getSystemOption($name)
	{
		return self::systemOption($name);
	}
	
	public static function systemOption($name)
	{
		return isset(self::$system_options[$name]) ? self::$system_options[$name] : null;
	}
	
	protected function getOption($name)
	{
		return $this->getSystemOption($name);
	}
	
	public function getFactory($name)
	{
		return isset($this->factories[$name]) ? $this->factories[$name] : null;
	}
	
	
	public function getCurrentUrl()
	{
		return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
	}
	
	public function getRequestUrl($without_root = true)
	{
		$url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
		if ($without_root) {
			$url = substr($url, strlen($this->getRootUrl()));
		}
		if (!$this->getSystemOption('mod_rewrite')) {
			if (substr($url, 0, 9) == 'index.php') {
				$url = substr($url, 10);
			}
		}
		return $url;
	}
	

	public function getRootUrl($absolute = false, $script = false)
	{
		if (!self::$root_url) {
			if (isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME']) {
				self::$root_url = $_SERVER['SCRIPT_NAME'];
			} elseif (isset($_SERVER['PHP_SELF']) && $_SERVER['PHP_SELF']) {
				self::$root_url = $_SERVER['PHP_SELF'];
			} else {
				self::$root_url = '/';
			}
			self::$root_url = preg_replace('!/[^/]*$!', '/', self::$root_url);
		}
		if ($absolute) {
			$https = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : '';
			if (strtolower($https) == 'on') {
    			$url = 'https://'; 
    		} else {
    			$url = 'http://';
    		}
    		$url .= $_SERVER['HTTP_HOST'];
    		return $url.self::$root_url.($script && !$this->getSystemOption('mod_rewrite') ? 'index.php/' : '');
    	}
		return self::$root_url.($script && !$this->getSystemOption('mod_rewrite') ? 'index.php/' : ''); 
	}	
	
	protected function configure()
	{
		@mb_internal_encoding('UTF-8');
		@ini_set('magic_quotes_gpc', 'off');
		@ini_set('magic_quotes_runtime', 'off');
		if (function_exists('set_magic_quotes_runtime')) {
			// User @ for hide warning for PHP 5.3, because set_magic_quotes_runtime is deprecated
			@set_magic_quotes_runtime(false);
		}
		@ini_set('register_globals', 'off');
		
		// IIS
		if (!isset($_SERVER['REQUEST_URI'])) {
			$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
			if (isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING'])) {
  				$_SERVER['REQUEST_URI'].= '?'.$_SERVER['QUERY_STRING'];
 			}
 			self::$system_options['mod_rewrite'] = false;
		}
		
		if (!get_magic_quotes_gpc()) {
			return;
		}		
		
		function array_stripslashes($array) {
			return is_array($array) ? array_map("array_stripslashes", $array) : stripslashes($array);
		}
		
		$_GET = array_stripslashes($_GET);
		$_POST = array_stripslashes($_POST);
		$_COOKIE = array_stripslashes($_COOKIE);
		$_REQUEST = array_stripslashes($_REQUEST);
	}

	public function init()
	{
		$file_path = $this->root_path.'/wa-config/config.php';
		if (file_exists($file_path)) {
			$config = include($file_path);
			foreach ($config as $name => $value) {
				self::$system_options[$name] = $value;
			}
		}
	}
	
	public function getBackendUrl($with_rool_url = false)
	{
		if (!$this->getSystemOption('mod_rewrite')) {
			$backend_url = 'index.php/'.$this->getSystemOption('backend_url');
		} else {
			$backend_url = $this->getSystemOption('backend_url');
		}
		if ($with_rool_url) {
			return $this->getRootUrl().$backend_url."/";	
		} else {
			return $backend_url;
		}
	}

	public function setPath($root_path)
	{
		$this->root_path = $root_path;
		waConfig::add(array(
			'wa_path_root'		=> $root_path,
			'wa_path_apps'		=> $root_path.DIRECTORY_SEPARATOR.'wa-apps',
			'wa_path_system'	=> $root_path.DIRECTORY_SEPARATOR.'wa-system',
			'wa_path_lib'		=> $root_path.DIRECTORY_SEPARATOR.'wa-system/lib',
			'wa_path_log'		=> $root_path.DIRECTORY_SEPARATOR.'wa-log',
			'wa_path_data'		=> $root_path.DIRECTORY_SEPARATOR.'wa-data',
			'wa_path_config'	=> $root_path.DIRECTORY_SEPARATOR.'wa-config',
			'wa_path_cache'		=> $root_path.DIRECTORY_SEPARATOR.'wa-cache',
			'wa_path_plugins'	=> $root_path.DIRECTORY_SEPARATOR.'wa-plugins',
			'wa_path_installer'	=> $root_path.DIRECTORY_SEPARATOR.'wa-installer',
		));
	}
	
	public function getPath($name, $file = null)
	{
		$path = waConfig::get('wa_path_'.$name);
		if ($path) {
			if ($file) {
				$path .= DIRECTORY_SEPARATOR.$file.".php";
			}
		}
		return $path;
	}
	
	public function getAppsPath($app, $path = null)
	{
		return $this->getRootPath().DIRECTORY_SEPARATOR.'wa-apps'.DIRECTORY_SEPARATOR.$app.($path ? DIRECTORY_SEPARATOR.$path : '');
	}
	
	
	public function getConfigFile($name, $file, $default = array())
	{
		$path = $this->getPath($name, $file);
		if (file_exists($path)) {
			return include($path);
		} else {
			return $default;
		}
	}
	
	public function getConfigPath($name, $user_config = true, $app = null) 
	{
		if ($app === null) {
			$app = 'webasyst';
		} 
		if ($user_config) {
			$path = $this->getPath('config').'/apps/'.$app.'/'.$name;
			return waFiles::create($path);
		} else {
			return $this->getPath('apps').'/'.$app.'/lib/config/'.$name;
		}			
	}	
	
	public function getRouting()
	{
		return $this->getConfigFile('config', 'routing'); 
	}

	public function getRootPath()
	{
		return $this->root_path;
	}


	public static function getActive()
	{
		if (!self::hasActive()) {
			throw new RuntimeException('There is no active configuration.');
		}

		return self::$active;
	}

	public static function hasActive()
	{
		return null !== self::$active;
	}

	/**
	 * Returns instance of AppConfig
	 * 
	 * @param $application
	 * @param $enviroment
	 * @param $root_path
	 * 
	 * @return waAppConfig
	 */
	public static function getAppConfig($application, $enviroment = null, $root_path = null)
	{
		$class_name = $application.'Config';
		if ($root_path === null) {
			$root_path = realpath(dirname(__FILE__).'/../..');
		}
		
		if ($enviroment === null) {
			$enviroment = waSystem::getInstance()->getEnv();
		}

		if ($application === 'webasyst') {
			require_once($root_path.'/wa-system/webasyst/lib/config/webasystConfig.class.php');
			return new webasystConfig($enviroment, $root_path);
		}
		
		if (file_exists($file = $root_path.'/wa-apps/'.$application.'/lib/config/'.$class_name.'.class.php')) {
			require_once($file);
			return new $class_name($enviroment, $root_path);
		} elseif (file_exists($file = $root_path.'/wa-apps/'.$application.'/lib/config/app.php')) {
			return new waAppConfig($enviroment, $root_path, $application);
		} else {
			throw new waException(sprintf('Application "%s" does not exist.', $application));
		}
	}
	
	public function getEnviroment()
	{
    	return $this->enviroment;
	}	
	
	public function getDatabase()
	{
		$file = $this->getPath('config', 'db');
		if (!file_exists($file)) {
			throw new waException("File wa-config/db.php not found.", 600);
		} 
		return include($file);
	}
	
	public static function isDebug()
	{
		return isset(self::$system_options['debug']) ? self::$system_options['debug'] : false;
	}
	
	public static function setDebug()
	{
		
	}
		
	public function getMessage($type = 'email')
	{
		$settings = $this->getConfigFile('config', 'message');
		return isset($settings[$type]) ? $settings[$type] : array();
	}
	
	public function getLocales($type = false)
	{
		return waLocale::getAll($type);
	}

}