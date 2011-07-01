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
class waAppConfig extends SystemConfig
{
	protected $application = null;
	protected $info = array();
	protected $log_actions = array(); 
	protected $prefix;
	protected $plugins = null;
	protected $options = array();
		
	public function __construct($enviroment, $root_path, $application = null)
	{
		if ($application) {
			$this->application = $application;
		} else {
			$this->application = substr(get_class($this), 0, -6);
		}
		parent::__construct($enviroment, $root_path);
	}
	
	public function getApplication()
	{
		return $this->application;
	}
	
	public function getLogActions()
	{
		$path = $this->getAppPath().'/lib/config/logs.php';
		if (!file_exists($path)) {
			return array();
		}
		if (!$this->log_actions) {
			$this->log_actions = include($path); 
		}
		return $this->log_actions;
	}
	
	protected function configure()
	{
		
	}
	
	public function getOption($name)
	{
		return isset($this->options[$name]) ? $this->options[$name] : null;
	}	
	
	public function init()
	{
		$file_path = $this->getPath('config').'/apps/'.$this->application.'/config.php';
		if (file_exists($file_path)) {
			$config = include($file_path);
			if ($config && is_array($config)) {
				foreach ($config as $name => $value) {
					$this->options[$name] = $value;
				}
			}
		}		
		
		$this->info = include($this->getAppPath().'/lib/config/app.php');
		waAutoload::getInstance()->add($this->getClasses());
		
		if (file_exists($this->getAppPath().'/lib/config/factories.php')) {
			$this->factories = include($this->getAppPath().'/lib/config/factories.php'); 
		}
		if (waSystem::isLoaded()) {
			$this->setLocale(waSystem::getInstance()->getLocale());
		}		
		
		$this->checkUpdates();
	}
	
	protected function checkUpdates()
	{
		try {
			$app_settings_model = new waAppSettingsModel();
			$time = $app_settings_model->get($this->application, 'update_time');
		} catch (waDbException $e) {
			// table doesn't exist
			if ($e->getCode() == 1146) {
				$time = null;
			} elseif ($e->getCode() == 2002 && !waSystemConfig::isDebug()) {
				return;
			} else {
				throw $e;
			}
		}
		if (!$time) {
			try {
				$this->install();
			} catch (Exception $e) {
				waLog::log($e->__toString());
				return;
			}
			$ignore_all = true;
		} else {
			$ignore_all = false;
		}
		$path = $this->getAppPath().'/lib/updates';
		$cache_database_dir = $this->getPath('cache').'/db';
		if (file_exists($path)) {
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
			$files = array();
			foreach ($iterator as $file) {
				if ($file->isFile() && preg_match('/^[0-9]+\.php$/', $file->getFilename())) {
					$t = substr($file->getFilename(), 0, -4);
					if ($t > $time) {
						$files[$t] = $file->getPathname();
					}	
				}
			}
			ksort($files);
			foreach ($files as $t => $file) {
  				try {
  					if (!$ignore_all) {
						include($file);
						waFiles::delete($cache_database_dir);
						$app_settings_model->set($this->application, 'update_time', $t);
  					}
				} catch (Exception $e) {
					if (waSystemConfig::isDebug()) {
						echo $e;	
					}
					// log errors
					waLog::log($e->__toString());
					break;
				}
			}			
		} else {
			$t = 1;
		}
		if ($ignore_all) {
			if (!$t) {
				$t = 1;
			}
			if (!isset($app_settings_model)) {
				$app_settings_model = new waAppSettingsModel();
			}
			$app_settings_model->set($this->application, 'update_time', $t);
		}
		
	}
	
	public function install()
	{
		// check app.sql
		$file_sql = $this->getAppPath('lib/config/app.sql');
		if (file_exists($file_sql)) {
			// execute sql
			$sqls = file_get_contents($file_sql);
			while(strpos($sqls,($separator = uniqid(':SQL:')))!== false) {
			}
			$sqls = preg_replace("/;\r?\n/", $separator, $sqls);
			$sqls = explode($separator, $sqls);
			$sqls = array_map('trim', $sqls);
			$sqls = array_filter($sqls, 'strlen');
			$model = new waModel();
			foreach ($sqls as $sql) {
				// ignore drop table
				if (preg_match('/drop[\s\t\r\n]+table/is', $sql)) {
					continue;
				}
				$model->exec($sql);
			}
		}
		$file = $this->getAppConfigPath('install');
		if (file_exists($file)) {
			$app_id = $this->application;
			include($file);
		}
	}
	
	public function uninstall()
	{		
		// check uninstall.php
		$file = $this->getAppConfigPath('uninstall');
		if (file_exists($file)) {
			include($file);
		}
		// check app.sql
		$file_sql = $this->getAppPath('lib/config/app.sql');
		if (file_exists($file_sql)) {
			// execute sql
			$sqls = file_get_contents($file_sql);
			while(strpos($sqls,($separator = uniqid(':SQL:')))!== false) {
			}
			$sqls = preg_replace("/;\r?\n/", $separator, $sqls);
			$sqls = explode($separator, $sqls);
			$sqls = array_map('trim', $sqls);
			$sqls = array_filter($sqls, 'strlen');
			$model = new waModel();
			foreach ($sqls as $sql) {
				// execute only drop table
				if (preg_match('/drop[\s\t\r\n]+table/is', $sql)) {
					$model->exec($sql);
				}
			}
		}
		// Remove all app settings
		$app_settings_model = new waAppSettingsModel();
		$app_settings_model->deleteByField('app_id', $this->application);
		
		$contact_settings_model = new waContactSettingsModel();
		$contact_settings_model->deleteByField('app_id', $this->application);
		// Remove all rights to app
		$contact_rights_model = new waContactRightsModel();
		$contact_rights_model->deleteByField('app_id', $this->application);	
		// Remove logs
		$log_model = new waLogModel();
		$log_model->deleteByField('app_id', $this->application);
		// Remove cache
		waFiles::delete($this->getPath('cache').'/apps/'.$this->application);
	}
	
	public function setLocale($locale)
	{
		waLocale::load($locale, $this->getAppPath('locale'), $this->application, true);
	}
	
	public function setActive()
	{
		$this->setLocale(waSystem::getInstance()->getLocale());
	}
	
	public function getClasses()
	{
		$cache_file = waConfig::get('wa_path_cache').'/apps/'.$this->application.'/config/autoload.php';
		if (self::isDebug() || !file_exists($cache_file)) {
			waFiles::create(waConfig::get('wa_path_cache').'/apps/'.$this->application.'/config');
			$paths = array($this->getAppPath().'/lib/');
			if (file_exists($this->getAppPath().'/plugins')) {
				$paths[] = $this->getAppPath().'/plugins/';
			}
			$result = array();
			foreach ($paths as $path) {
				$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
				$length = strlen($this->getRootPath());
				foreach ($iterator as $file) {
				    if ($file->isFile() && substr($file, -4) == '.php') {
				    	$class = $this->getClassByFilename($file->getFilename());
				    	if ($class) {
				    			$result[strtolower($class)] = substr($file, $length + 1);
				    	}
				    }
				}
			}
			waUtils::varExportToFile($result, $cache_file);
			return $result;
		}
		return include($cache_file);
	}
	
	protected function getClassByFilename($filename) 
	{
	    	$file_parts = explode('.', $filename);
	    	if (count($file_parts) <= 2) {
	    		return false;
	    	}
	    	$class = null;
	    	switch ($file_parts[1]) {
	    		case 'class':
	    			return $file_parts[0];
	    		default: 
	    			$result = $file_parts[0];
	    			for ($i = 1; $i < count($file_parts) - 1; $i++) {
	    				$result .= ucfirst($file_parts[$i]);
	    			}
	    			return $result;
	    	}
	}
	 
	
	public function getAppPath($path = null)
	{
		return $this->getRootPath().DIRECTORY_SEPARATOR.'wa-apps'.DIRECTORY_SEPARATOR.$this->application.($path ? DIRECTORY_SEPARATOR.$path : '');
	}
	
	public function getAppConfigPath($name) 
	{
		return $this->getAppPath("lib/config/".$name.".php");
	}
	
	public function getConfigPath($name, $user_config = true, $app = null)
	{
		if ($app === null) {
			$app = $this->application;
		}
		return parent::getConfigPath($name, $user_config, $app);
	}
	
	public function getRouting()
	{
		$path = $this->getAppConfigPath('routing');
		if (file_exists($path)) {
			return include($path);
		} else {
			return array();
		}	
	}
	
	public function getPrefix()
	{
		if (!$this->prefix) {
			$this->prefix = $this->getInfo('prefix');
			if (!$this->prefix) {
				$this->prefix = $this->getApplication();
			}
		} 
		return $this->prefix;
	}
	
	public function getName()
	{
		return $this->getInfo('name');
	}
	
	public function getInfo($name = null)
	{
		if ($name === null) {
			return $this->info;
		} else {
			return isset($this->info[$name]) ? $this->info[$name] : null; 
		}
	}
	
	protected function getPluginPath($plugin_id)
	{
		return $this->getAppPath()."/plugins/".$plugin_id;
	}
	
	public function getPlugins()
	{	
		if ($this->plugins === null) {
			$file = waConfig::get('wa_path_cache')."/apps/".$this->application.'/config/plugins.php';
			if (!file_exists($file) || SystemConfig::isDebug()) {
				waFiles::create(waConfig::get('wa_path_cache')."/apps/".$this->application.'/config');
				$all_plugins = include($this->getAppConfigPath('plugins'));
				$this->plugins = array();
				foreach ($all_plugins as $plugin_id => $enabled) {
					if ($enabled) {
						$plugin_config = $this->getPluginPath($plugin_id)."/lib/plugin.php";
						if (!file_exists($plugin_config)) {
							continue;
						}
						$plugin_info = include($plugin_config);
						//$plugin_info['name'] = _wd($plugin_id, $plugin_info['name']);
						if (isset($plugin_info['img'])) {
							$plugin_info['img'] = 'wa-apps/'.$this->application.'/plugins/'.$plugin_id.'/'.$plugin_info['img'];
						}
						$this->plugins[$plugin_id] = $plugin_info;
					}
				}
				waUtils::varExportToFile($this->plugins, $file);
			} else {
				$this->plugins = include($file);
			}
		}
		return $this->plugins; 
	}
	
	
	public function count()
	{
		return null;
	}
	
}