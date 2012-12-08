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
 * @package installer
 */

class installerConfig extends waAppConfig
{
	protected $application_config = array();
	public function init()
	{
		parent::init();
		require_once ($this->getPath('installer', 'lib/init'));
	}

	public function onCount()
	{
		$args = func_get_args();
		$force = array_shift($args);

		$model = new waAppSettingsModel();
		$app_id = $this->getApplication();
		$update_counter = null;
		if (
		$force
		|| ( (time()-$model->get($app_id, 'update_counter_timestamp', 0))> 600 )
		|| is_null($update_counter = $model->get($app_id, 'update_counter', null))) {
			$license = $model->get('webasyst', 'license', false);
			$apps = new waInstallerApps($license, null, 600, $force);
			$app_list = $apps->getApplicationsList(false);
			$update_counter=waInstallerApps::getUpdateCount($app_list);
			$model->ping();
			$model->set($app_id, 'update_counter', $update_counter);
			$model->set($app_id, 'update_counter_timestamp', time());
		} elseif (is_null($update_counter)) {
			$update_counter = $model->get($app_id, 'update_counter');
		}
		return $update_counter?$update_counter:null;
	}
	
	public function setCount($n = null)
	{
		wa()->getStorage()->open();
		$model = new waAppSettingsModel();
		$app_id = $this->getApplication();
		$model->set($app_id, 'update_counter', $n);
		$model->set($app_id, 'update_counter_timestamp', ($n === false)?0:time());
		return parent::setCount($n);
	}
}
//EOF