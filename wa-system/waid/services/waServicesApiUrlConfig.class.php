<?php

class waServicesApiUrlConfig extends waWebasystIDConfig
{

    const SERVICES_ENDPOINTS_SYNC_TIME_KEY = 'services_endpoints_sync_time';

	protected function getConfigPath()
    {
        return waConfig::get('wa_path_config') . '/services.php';
    }

    protected function generateDefaultConfig()
    {
        $endpoints = (new waServicesEndpointsConfig())->getEndpoints();
        $this->updateMTime();
        return [
            'endpoints' => $endpoints
        ];
    }

    public function keepEndpointsSynchronized($force_renew = false)
    {
        if ($force_renew || !isset($this->config['endpoints']) || time() - $this->getMTime() > $this->sync_endpoints_timeout) {
            $endpoints = (new waServicesEndpointsConfig())->getEndpoints();
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

    protected function getSettingsKey()
    {
        return self::SERVICES_ENDPOINTS_SYNC_TIME_KEY;
    }

}
