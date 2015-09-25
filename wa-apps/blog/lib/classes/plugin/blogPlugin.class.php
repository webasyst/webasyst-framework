<?php

class blogPlugin extends waPlugin
{

    /**
     * Get plugin setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSettingValue($key, $default = null)
    {
        $settings = $this->getSettings();
        return isset($settings[$key]) ? $settings[$key] : $default;
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
        $this->getSettings();
        return $this->settings[$key] = $value;
    }

    protected function getSettingsConfig()
    {
        if (is_null($this->settings_config)) {
            $path = $this->path.'/lib/config/settings.php';
            if (file_exists($path)) {
                $settings_config = include($path);
                if (!is_array($settings_config)) {
                    $settings_config = array();
                }
            } elseif (!empty($this->info['settings'])) {
                $settings_config = $this->info['settings'];
                foreach ($settings_config as &$row) {
                    $row['control_type'] = $row['settings_html_function'];
                }
                unset($row);
            } else {
                $settings_config = array();
            }
            $this->settings_config = array_merge($this->common_settings_config, $settings_config);
        }
        return $this->settings_config;
    }

}