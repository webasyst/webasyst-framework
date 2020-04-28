<?php

class teamWaAppSettingsModel extends waAppSettingsModel
{
    protected $app_id = 'team';

    protected $map_adapters = array('yandex', 'google', 'disabled');

    /**
     * @var teamConfig
     */
    protected $config;

    /**
     * @return teamConfig
     */
    protected function getConfig()
    {
        if (!$this->config) {
            $this->config = wa($this->app_id)->getConfig();
        }
        return $this->config;
    }

    public function setSetting($name, $value)
    {
        return parent::set($this->app_id, $name, $value);
    }

    public function getSetting($name, $default = '')
    {
        return parent::get($this->app_id, $name, $default);
    }

    public function saveUserNameDisplayFormat($value)
    {
        $formats = $this->getConfig()->getUsernameFormats();
        $found = false;
        foreach ($formats as $format) {
            if ($format['format'] === $value) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            return;
        }
        // app_id is webasyst - special situation
        return $this->set('webasyst', 'user_name_display', $value);
    }

    public function getMapAdapter()
    {
        $map_adapter = $this->typecastMapAdapter($this->get('webasyst', 'map_adapter'));
        if (!in_array($map_adapter, $this->map_adapters)) {
            $map_adapter = $this->map_adapters[0];
        }
        return $map_adapter;
    }

    public function getMapInfo()
    {
        $adapter = $this->getMapAdapter();
        $settings = $this->get('webasyst', 'map_adapter_' . $adapter);
        $settings = $settings ? json_decode($settings, true) : null;
        $settings = (array) $settings;
        return array(
            'adapter' => $adapter,
            'settings' => $settings
        );
    }

    public function setMapInfo($map_adapter, $settings = array())
    {
        $map_adapter = $this->typecastMapAdapter($map_adapter);
        $this->set('webasyst', 'map_adapter', $map_adapter);
        $old_settings = $this->get('webasyst', 'map_adapter_' . $map_adapter);
        $old_settings = $old_settings ? json_decode($old_settings, true) : null;
        $old_settings = (array) $old_settings;
        $settings = array_merge($old_settings, $settings);
        $this->set('webasyst', 'map_adapter_' . $map_adapter, json_encode($settings));
    }

    public function getUserNameDisplayFormat()
    {
        // app_id is webasyst - special situation
        return $this->get('webasyst', 'user_name_display', 'name');
    }

    private function typecastMapAdapter($map_adapter)
    {
        if (!in_array($map_adapter, $this->map_adapters)) {
            $map_adapter = $this->map_adapters[0];
        }
        return $map_adapter;
    }
}
