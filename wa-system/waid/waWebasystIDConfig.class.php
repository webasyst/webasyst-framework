<?php

class waWebasystIDConfig
{
    protected $config = [];
    protected $config_path;

    /**
     * waWebasystIDConfig constructor.
     */
    public function __construct()
    {
        $this->config_path = waConfig::get('wa_path_config') . '/waid.php';
        if (file_exists($this->config_path)) {
            $this->config = include($this->config_path);
        }

        if (!$this->config) {
            $this->config = $this->generateDefaultConfig();
            if ($this->config) {
                waUtils::varExportToFile($this->config, $this->config_path);
            }
        }

        $this->config = $this->typecastConfig($this->config);
    }

    /**
     * @param string $controller_url [optional] - controller url of auth center
     * @param array $params [optional] - get params for url
     * @return string
     */
    public function getAuthCenterUrl($controller_url = null, $params = [])
    {
        $auth_center_url = rtrim($this->config['auth_center_url'], '/') . '';
        if ($controller_url) {
            $auth_center_url .= '/' . $controller_url;
        }
        if ($params) {
            $auth_center_url .= '?' . http_build_query($params);
        }
        return $auth_center_url;
    }

    /**
     * @param string $controller_url - api method (controller)
     * @param array $params - get parameters of api method
     *      - string $params['version'] [optional] - Additional parameter - version of API, default is v1
     * @return mixed|string
     */
    public function getApiUrl($controller_url, $params = [])
    {
        $api_url = rtrim($this->config['api_url'], '/');

        $api_v = 1;
        if ($params && isset($params['version'])) {
            $api_v = $params['version'];
            unset($params['version']);
        }

        // default is /v1/
        $api_url .= '/v' . $api_v . '/';

        // api controller itself
        $api_url .= trim($controller_url, '/');

        if ($params) {
            $api_url .= '?' . http_build_query($params);
        }

        return $api_url;
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
        $installer_sources_path = waConfig::get('wa_path_root') . '/wa-installer/lib/config/sources.php';
        if (file_exists($installer_sources_path)) {
            $sources = include($installer_sources_path);
            $auth_center_url = $this->getAuthCenterUrlByInstallerSources($sources);
            if ($auth_center_url) {
                $api_url = str_replace('oauth2/', 'api/', $auth_center_url);
                return [
                    'auth_center_url' => $auth_center_url,
                    'api_url' => $api_url
                ];
            }
        }
        return [];
    }

    /**
     * Get auth center by installer sources
     * @param array $sources
     * @return string|null
     */
    protected function getAuthCenterUrlByInstallerSources(array $sources)
    {
        if (isset($sources['webasyst'])) {
            $sources = $sources['webasyst'];
        }

        if (!is_array($sources)) {
            return null;
        }

        foreach ($sources as $type => $url) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $parsed = parse_url($url);

            if (!isset($parsed['host'])) {
                continue;
            }

            $build_url = [
                'https://'
            ];
            
            if (isset($build_url['user']) && isset($build_url['pass'])) {
                $build_url[] = $build_url['user'] . ':' . $build_url['pass'] . '@';
            }

            $build_url[] = $parsed['host'];
            $build_url[] = '/id/oauth2/';

            $build_url = join('', $build_url);

            return $build_url;
        }

        return null;
    }

    /**
     * @param mixed $config
     * @return array
     */
    protected function typecastConfig($config)
    {
        $config = is_array($config) ? $config : [];
        if (!isset($config['auth_center_url']) || !is_scalar($config['auth_center_url'])) {
            $config['auth_center_url'] = '';
        }
        if (!isset($config['api_url']) || !is_scalar($config['api_url'])) {
            $config['api_url'] = '';
        }
        return $config;
    }
}
