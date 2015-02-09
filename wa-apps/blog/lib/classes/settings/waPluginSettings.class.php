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
 * @subpackage settings
 */
class waPluginSettings extends waSettings
{
    static protected $instances = array();
    /**
     *
     * @var waAppSettingsModel
     */
    private static $model;

    /**
     * @internal param string $app_id
     * @internal param string $plugin_id
     * @return waPluginSettings
     */
    public static function getInstance()
    {
        $args = func_get_args();
        $app_id = array_shift($args);
        $plugin_id = array_shift($args);
        $name = $app_id.($plugin_id?'.'.$plugin_id:'');
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new self($name);
        }
        if (!isset(self::$model)) {
            self::$model = new waAppSettingsModel();
        }
        return self::$instances[$name];
    }

    protected function load()
    {
        $this->settings = array();
        if ($this->name) {//load settings from database
            $settings = self::$model->get($this->name);
            foreach ($settings as $field => $value) {
                if($field != 'update_time') {
                    $this->loadSetting($field, $value);
                }
            }
        }
        return $this;
    }


    protected function saveSetting($name, $value)
    {
        self::$model->set($this->name, $name, $value);
    }

    protected function onDelete($name = null)
    {
        self::$model->del($this->name,$name);
    }

    public function flush()
    {
        //do nothing - waAppSettingsModel clean cache automaticaly
    }

    public function reset()
    {
        $this->settings = null;
        self::$model->del($this->name);
        $this->load();
    }

}
