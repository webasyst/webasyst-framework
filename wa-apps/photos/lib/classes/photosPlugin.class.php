<?php

class photosPlugin extends waPlugin
{
    /**
     * @var waAppSettingsModel
     */
    protected static $app_settings_model;

    protected $settings;

    public function getControls($params = array())
    {
        $controls = array();
        $settings_config = $this->getSettingsConfig();
        foreach ($settings_config as $name => $row) {
            $row = array_merge($row, $params);
            $row['value'] = $this->getSettings($name);
            if (isset($row['control_type'])) {
                $controls[$name] = waHtmlControl::getControl($row['control_type'], $name, $row);
            }
        }
        return $controls;
    }

    public function getSettings($name = null)
    {
        if ($this->settings === null) {
            $this->settings = array();
            $settings_config = $this->getSettingsConfig();
            if ($settings_config) {
                $model = $this->getSettingsModel();
                $this->settings = $model->get(array($this->app_id, $this->id));
                foreach ($settings_config as $key => $row) {
                    if (!isset($this->settings[$key])) {
                        $this->settings[$key] = isset($row['value']) ? $row['value'] : null;
                    }
                }
            }
        }
        if ($name === null) {
            return $this->settings;
        } else {
            return isset($this->settings[$name]) ? $this->settings[$name] : null;
        }
    }

    protected function getSettingsConfig()
    {
        $path = $this->path.'/lib/config/settings.php';
        if (file_exists($path)) {
            return include($path);
        } else {
            return array();
        }
    }


    public function saveSettings($settings = array())
    {
        $settings_config = $this->getSettingsConfig();
        foreach ($settings_config as $name => $row) {
            // remove
            if (!isset($settings[$name])) {
                if (($row['control_type'] == waHtmlControl::CHECKBOX) && !empty($row['value'])) {
                    $settings[$name] = false;
                } else {
                    $this->settings[$name] = isset($row['value']) ? $row['value'] : null;
                    $this->getSettingsModel()->del(array($this->app_id, $this->id), $name);
                }
            }
        }
        foreach ($settings as $name => $value) {
            $this->settings[$name] = $value;
            // save to db
            $this->getSettingsModel()->set(array($this->app_id, $this->id), $name, $value);
        }
    }

    /**
     * @return waAppSettingsModel
     */
    protected function getSettingsModel()
    {
        if (!self::$app_settings_model) {
            self::$app_settings_model = new waAppSettingsModel();
        }
        return self::$app_settings_model;
    }

}