<?php

class waServicesEndpointsConfig extends waWebasystIDEndpointsConfig
{

    public function getUrl()
    {
        $installer_apps = $this->getInstallerApps();
        if (!$installer_apps) {
            return '';
        }
        return $installer_apps->getEndpointsUrl() . '?app=balance';
    }

    protected function typecastEndpoints(array $endpoints)
    {
        if (!empty($endpoints)) {
            return $endpoints;
        }

        return $this->getDefaultEndpoints();
    }

    protected function getDefaultEndpoints()
    {
        $installer_sources_path = waConfig::get('wa_path_root') . '/wa-installer/lib/config/sources.php';
        if (file_exists($installer_sources_path)) {
            $sources = include($installer_sources_path);
            $api_url = $this->getServiceUrlByInstallerSources($sources);
            if ($api_url) {
                return [
                    'api' => $api_url
                ];
            }
        }
        return [];
    }

    protected function getServiceUrlByInstallerSources(array $sources)
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
            $build_url[] = '/billing/';

            return join('', $build_url);
        }

        return null;
    }

}
