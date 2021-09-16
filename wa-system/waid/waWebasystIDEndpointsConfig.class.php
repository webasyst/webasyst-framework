<?php

class waWebasystIDEndpointsConfig
{
    /**
     * @var waInstallerApps
     */
    protected $installer_apps;

    public function getUrl()
    {
        $installer_apps = $this->getInstallerApps();
        if (!$installer_apps) {
            return '';
        }
        return $installer_apps->getEndpointsUrl() . '?app=waid';
    }

    protected function getInstallerApps()
    {
        if (!$this->installer_apps) {
            if (!class_exists('waInstallerApps')) {
                $autoload = waAutoload::getInstance();
                $autoload->add('waInstallerApps', 'wa-installer/lib/classes/wainstallerapps.class.php');
            }
            if (!class_exists('waInstallerApps')) {
                return null;
            }
            $this->installer_apps = new waInstallerApps();
        }
        return $this->installer_apps;
    }

    public function getEndpoints()
    {
        $result = $this->requestEndpoints();

        $endpoints = [];
        if ($result['status']) {
            $endpoints = $result['details']['endpoints'];
        }

        return $this->typecastEndpoints($endpoints);
    }

    protected function requestEndpoints()
    {
        $url = $this->getUrl();
        if (!$url) {
            return $this->packFailResult("invalid_url", "Invalid url");
        }

        $options = [
            'timeout' => 30,
            'format' => waNet::FORMAT_JSON
        ];

        $net = new waNet($options);
        $response = null;
        try {
            $response = $net->query($url);
        } catch (Exception $e) {
            $this->logException($e);
            $this->logError([
                'url' => $url,
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);
            return $this->packFailResult("fail_" . $e->getCode(), $e->getMessage());
        }

        // No response from API
        if (!$response) {
            return $this->packFailResult("unknown", "Unknown error");
        }

        // Error from API
        if (!isset($response['status']) || $response['status'] === 'fail') {
            $errors = isset($response['errors']) && is_array($response['errors']) ? $response['errors'] : [];
            $error_code = "unknown";
            $error_message = "Unknown api error";
            if ($errors) {
                $error_code = key($errors);
                $error_message = $errors[$error_code];
            }
            return $this->packFailResult($error_code, $error_message);
        }

        // Expected response from API
        $correct_response = isset($response['data']['endpoints']) && is_array($response['data']['endpoints']);

        $endpoints = $correct_response ? $response['data']['endpoints'] : [];

        if (!$correct_response) {
            // Unexpected response
            $this->logError([
                'url' => $url,
                'method' => __METHOD__,
                'debug' => $net->getResponseDebugInfo()
            ]);
        }

        return $this->packOkResult([
            'endpoints' => $endpoints
        ]);
    }

    protected function typecastEndpoints(array $endpoints)
    {
        $default_endpoints = null;
        foreach ($endpoints as $idx => $endpoint_set) {
            if (empty($endpoint_set['oauth2']) && empty($endpoint_set['api'])) {
                unset($endpoints[$idx]);
                continue;
            }
            if (empty($endpoint_set['oauth2']) || empty($endpoint_set['api'])) {
                if ($default_endpoints === null) {
                    $default_endpoints = $this->getDefaultEndpoints();
                }
                $endpoints[$idx] = array_merge($endpoint_set, $default_endpoints);
            }
        }

        if (!$endpoints) {
            if ($default_endpoints === null) {
                $default_endpoints = $this->getDefaultEndpoints();
            }
            $endpoints = [$default_endpoints];
        }
        return $endpoints;
    }

    /**
     * @return array
     * @throws Exception
     */
    protected function getDefaultEndpoints()
    {
        $installer_sources_path = waConfig::get('wa_path_root') . '/wa-installer/lib/config/sources.php';
        if (file_exists($installer_sources_path)) {
            $sources = include($installer_sources_path);
            $auth_center_url = $this->getAuthCenterUrlByInstallerSources($sources);
            if ($auth_center_url) {
                $api_url = str_replace('oauth2/', 'api/', $auth_center_url);
                return [
                    'oauth2' => $auth_center_url,
                    'api' => $api_url
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

            return join('', $build_url);
        }

        return null;
    }

    protected function packFailResult($error_code, $error_message)
    {
        return [
            'status' => false,
            'details' => [
                'error_code' => $error_code,
                'error_message' => $error_message
            ]
        ];
    }

    protected function packOkResult($details = [])
    {
        return [
            'status' => true,
            'details' => $details
        ];
    }

    protected function logException(Exception $e)
    {
        $message = join(PHP_EOL, [$e->getCode(), $e->getMessage(), $e->getTraceAsString()]);
        waLog::log($message, 'webasyst/' . get_class($this) . '.log');
    }

    protected function logError($e)
    {
        if (!is_scalar($e)) {
            $e = var_export($e, true);
        }
        waLog::log($e, 'webasyst/' . get_class($this) . '.log');
    }
}
