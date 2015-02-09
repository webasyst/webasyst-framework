<?php

class blogPlugin extends waPlugin
{
    /**
     *
     * @var waPluginSettings
     */
    protected $settings = null;
    /**
     *
     * @var array()
     */
    protected $settings_fields = array();

    private function loadSettings()
    {
        if ($this->settings === null) {
            if (isset($this->info['settings']) && $this->info['settings'] && is_array($this->info['settings'])) {
                $this->settings_fields = $this->info['settings'];
                $this->settings = waPluginSettings::getInstance($this->app_id, $this->id);
                $this->settings->initSettingsParams($this->settings_fields);
            } else {
                $this->settings = array();
            }
        }
    }

    /**
     * Get plugin settings
     * @return array
     */
    function getSettings()
    {
        if ($this->settings === null) {
            $this->loadSettings();
        }
        foreach ($this->settings as $setting) {
            if (!isset($setting['name'])) {
                //TODO check it
                //$this->settings[$param]['name'] = $param;
            }
        }
        return $this->settings;
    }

    /**
     * Get plugin setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSettingValue($key, $default = null)
    {
        $this->loadSettings();
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    /**
     * Set plugin setting value
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function setSettingValue($key, $value = null)
    {
        $this->loadSettings();
        return $this->settings[$key] = $value;
    }

    /**
     * Setup and store plugin settings
     *
     * @param array $params array('setting_name'=>%setting_value%,...)
     * @return blogPlugin
     */
    public function setup($params)
    {
        $this->loadSettings();
        foreach ($this->settings_fields as $field => &$settings) {
            if (isset($params[$field])) {
                $this->settings[$field] = $params[$field];
                $settings['settings_value'] = $params[$field];
            } elseif ($settings['settings_html_function'] == 'checkbox') {
                $settings['settings_value'] = false;
                $this->settings[$field] = false;
            }
        }
        return $this;
    }

    /**
     * Store plugin settings at database
     * @return self
     */
    public function saveSettings()
    {
        $this->loadSettings();
        if ($this->settings_fields) {
            $this->settings->save();
        }
        return $this;
    }

    /**
     * Return html code for specified control
     * @param $field
     * @param $params
     * @return string
     */
    public function getControl($field, $params = array())
    {
        $this->loadSettings();
        if (isset($this->settings_fields[$field])) {
            $params['id'] = $this->app_id.'_'.$this->id;
            return $this->settings->getControl($this->settings_fields[$field]['settings_html_function'], $field, $params);
        } else {
            waLog::log(sprintf('The settings of "%s" has no "%s" setting.', get_class($this), $field));
        }

    }

    /**
     * Return array of properties and html code for plugin controls
     * @param array $params
     * @return array
     */
    public function getControls($params = array())
    {
        $this->loadSettings();
        $controls = array();
        $params['id'] = $this->app_id.'_'.$this->id;
        foreach ($this->settings_fields as $field => $properties) {
            $properties['control'] = $this->settings->getControl($properties['settings_html_function'], $field, $params);
            $controls[$field] = $properties;
        }
        return $controls;
    }
}