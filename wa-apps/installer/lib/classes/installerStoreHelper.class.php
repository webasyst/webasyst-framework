<?php

class installerStoreHelper
{
    public static function getStoreUrl()
    {
        if (defined('WA_CUSTOM_INSTALLER_STORE_URL')) {
            return WA_CUSTOM_INSTALLER_STORE_URL;
        }
        try {
            $init_data = self::getInstallerConfig()->getInitData();

            if (empty($init_data['root_url']) || !is_string($init_data['root_url'])) {
                throw new waException('Failed to load store iframe');
            }

            return $init_data['root_url'];

        } catch (Exception $e) {
            return false;
        }
    }

    public static function getStorePath()
    {
        $installer_store_uri = wa()->getAppUrl(null, false);
        $request_uri = waRequest::server('REQUEST_URI', '', waRequest::TYPE_STRING_TRIM);

        // Delete path before route Store
        $store_path = preg_replace('~^('.$installer_store_uri.'(store/)?)~ui', '', $request_uri);

        // Remove the msg Installer system parameter
        $store_path = preg_replace('~(\?|\&)msg\=\d{0,}(\&|$)~ui', '${1}', $store_path);

        // Delete the extra separator at the end of the path
        $store_path = preg_replace('~(\?|\&)$~ui', '', $store_path);

        return $store_path;
    }

    public static function getSidebarType()
    {
        $store_path = self::getStorePath();
        $sidebar_type = 'APPS';

        if (preg_match('~^(theme(s)?\/)~ui', $store_path)) {
            $sidebar_type = 'THEMES';
        }

        if (preg_match('~^(featured\/)~ui', $store_path)) {
            $sidebar_type = 'FEATURED';
        }
        return $sidebar_type;
    }

    public static function getFilters()
    {
        $filters = waRequest::request('filters', array(), waRequest::TYPE_ARRAY_TRIM);
        ksort($filters);
        return $filters;
    }

    /**
     * @return installerConfig
     */
    public static function getInstallerConfig()
    {
        return wa('installer')->getConfig();
    }
}
