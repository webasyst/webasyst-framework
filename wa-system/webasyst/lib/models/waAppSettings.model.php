<?php
/**
 * @version SVN: $Id$
 */
class waAppSettingsModel extends waModel
{
	protected static $cache = array();
	protected $table = 'wa_app_settings';
	
	public function get($app_id, $name, $default = '')
	{
		if (!isset(self::$cache[$app_id])) {
			$sql = "SELECT name, value 
					FROM ".$this->table." 
					WHERE app_id = ?";
			$this->setCache($this->getCache($app_id));
			self::$cache[$app_id] = $this->query($sql, array($app_id))->fetchAll('name', true);			 
		}
		return  isset(self::$cache[$app_id][$name]) ? self::$cache[$app_id][$name] : $default;
	}
	
	protected function getCache($app_id)
	{
		// cache one day
		return new waSerializeCache('app_settings/'.$app_id, 86400, 'webasyst');
	}
	
	public function set($app_id, $name, $value)
	{
		$this->addCacheCleaner($this->getCache($app_id));
		if ($r = $this->replace(array(
			'app_id' => $app_id,
			'name' => $name,
			'value' => $value
		))) {
			self::$cache[$app_id][$name] = $value;
		}
		return $r;
	}
	
	public function del($app_id, $name)
	{
		if (isset(self::$cache[$app_id][$name])) {
			unset(self::$cache[$app_id][$name]);
		}
		$this->addCacheCleaner($this->getCache($app_id));
		return $this->deleteByField(array('app_id' => $app_id, 'name' => $name));
	}
}