<?php

class waWebasystIDConfig
{
    const ENDPOINTS_SYNC_TIME_KEY = 'waid_endpoints_sync_time';
    const ENDPOINTS_SYNC_TIMEOUT = 14400; // 4 hours

    protected $config = [];
    protected $config_path;
    protected $app_settings_model;

    /**
     * waWebasystIDConfig constructor.
     */
    public function __construct()
    {
        $this->config_path = $this->getConfigPath();
        $this->config = $this->readData($this->config_path);

        $change = false;
        if (!$this->config) {
            $this->config = $this->generateDefaultConfig();
            $change = true;
        }

        if (empty($this->config['endpoints'])) {
            $default_config = $this->generateDefaultConfig();
            $this->config['endpoints'] = $default_config['endpoints'];
            $change = true;
        }

        if ($change) {
            $this->commit();
        }
    }

    /**
     * @return int
     */
    protected function getMTime()
    {
        $time = $this->getAppSettingsModel()->get('webasyst', $this->getSettingsKey(), '');
        if (wa_is_int($time) && $time > 0) {
            return $time;
        }
        return 0;
    }

    protected function updateMTime()
    {
        $this->getAppSettingsModel()->set('webasyst', $this->getSettingsKey(), time());
    }

    protected function getSettingsKey()
    {
        return self::ENDPOINTS_SYNC_TIME_KEY;
    }

    protected function getConfigPath()
    {
        return waConfig::get('wa_path_config') . '/waid.php';
    }

    protected function readData($config_path)
    {
        if (file_exists($config_path)) {
            return include($config_path);
        }
        return [];
    }

    /**
     * @return array
     *      [
     *          ['oauth2' => <oauth2_endpoint>, 'api' => <api_endpoint>],
     *          ...
     *      ]
     */
    public function getEndpoints()
    {
        if (isset($this->config['custom_endpoints']) && is_array($this->config['custom_endpoints'])) {
            return $this->config['custom_endpoints'];
        }

        if (!empty($this->config['endpoints']['zones'])) {
            $endpoints_zone = $this->getEndpointsZone();
            if (!empty($endpoints_zone) && isset($this->config['endpoints']['zones'][$endpoints_zone]) && is_array($this->config['endpoints']['zones'][$endpoints_zone])) {
                return $this->config['endpoints']['zones'][$endpoints_zone];
            }
        }

        return isset($this->config['endpoints']) && is_array($this->config['endpoints']) ? $this->config['endpoints'] : [];
    }

    protected function getEndpointsZone()
    {
        $config_path = waSystem::getInstance()->getConfigPath().'/config.php';
        $config = file_exists($config_path) ? include($config_path) : [];
        if (!is_array($config)) {
            $config = [];
        }
        $zone_jail = isset($config['zone_jail']) ? $config['zone_jail'] : null;
        if (!empty($zone_jail) && $zone_jail !== 'auto') {
            return $zone_jail;
        }

        if (!class_exists('waInstallerApps')) {
            $autoload = waAutoload::getInstance();
            $autoload->add('waInstallerApps', 'wa-installer/lib/classes/wainstallerapps.class.php');
        }
        if (!class_exists('waInstallerApps')) {
            return null;
        }
        return $this->getAppSettingsModel()->get('webasyst', waInstallerApps::ENDPOINTS_ZONE_KEY);
    }

    /**
     * Update (actualize) endpoints when timeout passed
     */
    public function keepEndpointsSynchronized($force_renew = false)
    {
        if ($force_renew || !isset($this->config['endpoints']) || time() - $this->getMTime() > self::ENDPOINTS_SYNC_TIMEOUT) {
            $endpoints = (new waWebasystIDEndpointsConfig())->getEndpoints();
            if ($endpoints) {
                $changed = !isset($this->config['endpoints']) || (isset($this->config['endpoints']) && $this->config['endpoints'] != $endpoints);
                if ($changed) {
                    $this->config['endpoints'] = $endpoints;
                    $this->commit();
                }
            }
            $this->updateMTime();
        }
    }

    public function reset()
    {
        if (file_exists($this->config_path)) {
            unlink($this->config_path);
        }
    }

    /**
     * Is backend auth forced to webasyst ID oauth2 only
     * @return bool
     */
    public function isBackendAuthForced()
    {
        return !empty($this->config['backend_auth_forced']);
    }

    /**
     * @return int
     */
    public function getEndpointMaxTries()
    {
        $threshold = 3; // default
        if (isset($this->config['endpoint_max_tries']) && wa_is_int($this->config['endpoint_max_tries']) && $this->config['endpoint_max_tries'] > 0) {
            $threshold = intval($this->config['endpoint_max_tries']);
        }
        return $threshold;
    }

    /**
     * Set/unset backend auth forced mode
     * Save only in runtime memory, to flush changes into file, call commit()
     * @see commit()
     * @param bool $on
     * @return waWebasystIDConfig
     */
    public function setBackendAuthForced($on = true)
    {
        if ($on) {
            $this->config['backend_auth_forced'] = $on;
        } else {
            unset($this->config['backend_auth_forced']);
        }
        return $this;
    }

    public function commit()
    {
        waUtils::varExportToFile($this->config, $this->config_path);
    }

    /**
     * Generate default config if could, called in constructor in case when config doesn't exist yet
     * @return array
     * @throws Exception
     */
    protected function generateDefaultConfig()
    {
        $endpoints = (new waWebasystIDEndpointsConfig())->getEndpoints();
        $this->updateMTime();
        return [
            'endpoints' => $endpoints
        ];
    }

    protected function getAppSettingsModel()
    {
        if (!empty($this->app_settings_model)) {
            return $this->app_settings_model;
        }
        return $this->app_settings_model = new waAppSettingsModel();
    }
}
