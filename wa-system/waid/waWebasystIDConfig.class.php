<?php

class waWebasystIDConfig
{
    const ENDPOINTS_SYNC_TIME_KEY = 'waid_endpoints_sync_time';

    protected $config = [];
    protected $config_path;

    protected $sync_endpoints_timeout = 86400;    // 24 hours

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
        $app_settings_model = new waAppSettingsModel();
        $time = $app_settings_model->get('webasyst', self::ENDPOINTS_SYNC_TIME_KEY, '');
        if (wa_is_int($time) && $time > 0) {
            return $time;
        }
        return 0;
    }

    protected function updateMTime()
    {
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->set('webasyst', self::ENDPOINTS_SYNC_TIME_KEY, time());
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
        return isset($this->config['endpoints']) && is_array($this->config['endpoints']) ? $this->config['endpoints'] : [];
    }

    /**
     * Update (actualize) endpoints when timeout passed
     */
    public function keepEndpointsSynchronized()
    {
        if (!isset($this->config['endpoints']) || time() - $this->getMTime() > $this->sync_endpoints_timeout) {
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
}
