<?php

class teamWaAppSettingsModel extends waAppSettingsModel
{
    protected $app_id = 'team';

    protected $map_providers = array('yandex', 'google');

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

    public function setMap($map_provider, $google_map_key = '')
    {
        $map_provider = $this->typecastMapProvider($map_provider);
        $this->set('webasyst', 'map_provider', $map_provider);
        if ($map_provider === 'google') {
            if ($google_map_key) {
                $this->set('webasyst', 'google_map_key', $google_map_key);
            } else {
                $this->del('webasyst', 'google_map_key');
            }
        } else {
            $this->del('webasyst', 'google_map_key');
        }
    }

    public function getMapProvider()
    {
        $map_provider = $this->typecastMapProvider($this->get('webasyst', 'map_provider'));
        if (!in_array($map_provider, $this->map_providers)) {
            $map_provider = $this->map_providers[0];
        }
        return $map_provider;
    }

    public function getGoogleMapKey()
    {
        return (string) $this->get('webasyst', 'google_map_key');
    }

    public function getUserNameDisplayFormat()
    {
        // app_id is webasyst - special situation
        return $this->get('webasyst', 'user_name_display', 'name');
    }

    private function typecastMapProvider($map_provider)
    {
        if (!in_array($map_provider, $this->map_providers)) {
            $map_provider = $this->map_providers[0];
        }
        return $map_provider;
    }
}
