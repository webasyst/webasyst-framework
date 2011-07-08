<?php

class waPlugin
{
	protected $id;
    protected $app_id;
    protected $info = array();
    protected $path;

    public function __construct($info)
    {
    	$this->info = $info;
    	$this->id = $this->info['id'];
    	if (isset($this->info['app_id'])) {
    		$this->app_id = $this->info['app_id'];
    	} else {
    		$this->app_id = waSystem::getInstance()->getApp();
    	}
    	$this->path = wa()->getAppPath('plugins/'.$this->id, $this->app_id);
    	
    	$this->checkUpdates();
    }
    
    protected function checkUpdates()
    {
		$app_settings_model = new waAppSettingsModel();
		$time = $app_settings_model->get($this->app_id, 'plugin.'.$this->id.'.update_time');
		if (!$time) {
			try {
				$this->install();
				$app_settings_model->set($this->app_id, 'plugin.'.$this->id.'.update_time', 1);
			} catch (Exception $e) {
				waLog::log($e->__toString());
				return;
			}
		}
    }
    
    protected function install()
    {
    	// check plugin.sql
		$file_sql = $this->path.'/lib/config/plugin.sql';
		if (file_exists($file_sql)) {
			waAppConfig::executeSQL($file_sql, 1);
		}
		// check install.php
		$file = $this->path.'/lib/config/install.php';
		if (file_exists($file)) {
			$app_id = $this->app_id;
			include($file);
		}        
    }
    
	public function uninstall()
	{		
		// check uninstall.php
		$file = $this->path.'/lib/config/uninstall.php';
		if (file_exists($file)) {
			include($file);
		}
		// check plugin.sql
		$file_sql = $this->path.'/lib/config/plugin.sql';
		if (file_exists($file_sql)) {
			waAppConfig::executeSQL($file_sql, 2);
		}
		// Remove plugin settings
		$app_settings_model = new waAppSettingsModel();
		$sql = "DELETE FROM ".$app_settings_model->getTableName()." 
				WHERE app_id = s:app_id AND (
					name = '".$app_settings_model->escape('plugin.'.$this->id)."' OR 
					name LIKE '".$app_settings_model->escape('plugin.'.$this->id).".%'
				)";
		$app_settings_model->exec($sql, array('app_id' => $this->app_id));
		
		if (!empty($this->info['rights'])) {		
    		// Remove rights to plugin
    		$contact_rights_model = new waContactRightsModel();
    		$sql = "DELETE FROM ".$contact_rights_model->getTableName()." 
    				WHERE app_id = s:app_id AND (
    					name = '".$contact_rights_model->escape('plugin.'.$this->id)."' OR 
    					name LIKE '".$contact_rights_model->escape('plugin.'.$this->id).".%'
    				)";
    		$contact_rights_model->exec($sql, array('app_id' => $this->app_id));
		}	

		// Remove cache of the appliaction
		waFiles::delete($this->getPath('cache').'/apps/'.$this->app_id);
	}    
    

    public function getPluginStaticUrl() 
    {
        return wa()->getAppStaticUrl($this->app_id).'plugins/'.$this->id.'/';
    }

    public function getRights($name = '', $assoc = true) 
    {
    	$right = 'plugin.'.$this->id;
    	if ($name) {
    		$right .= '.'.$name;
    	} 
        return wa()->getUser()->getRights(wa()->getConfig()->getApplication(), $right, $assoc);
    }
    
    public function rightsConfig(waRightConfig $rights_config)
    {
    	$rights_config->addItem('plugin.'.$this->id, $this->info['name'], 'checkbox');
    }
    
    protected function getUrl($url, $is_plugin)
    {
    	if ($is_plugin) {
    		return $this->getPluginStaticUrl().$url;
    	} else {
    		return $url;
    	}
    }
    
    protected function addJs($url, $is_plugin = true)
    {
		waSystem::getInstance()->getResponse()->addJs($this->getUrl($url, $is_plugin));
    }
    
    protected function addCss($url, $is_plugin = true)
    {
    	waSystem::getInstance()->getResponse()->addCss($this->getUrl($url, $is_plugin));
    }
}

