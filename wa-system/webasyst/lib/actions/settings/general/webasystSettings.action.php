<?php

/**
 * WA System settings :: General settings
 */
class webasystSettingsAction extends webasystSettingsViewAction
{
    const STABLE_PHP_VERSION_FOR_FRAMEWORK = '5.6';

    public function execute()
    {
        $model = new waAppSettingsModel();
        $settings = array(
            'name'   => 'Webasyst',
            'url'    => wa()->getRootUrl(true),
            'locale' => 'ru_RU',
        );
        foreach ($settings as $setting => &$value) {
            $value = $model->get('webasyst', $setting, $value);
        }
        unset($value);

        // Locales
        $locales = waSystem::getInstance()->getConfig()->getLocales('name');

        // Locale adapters
        $locale_adapters_list = array(
            'gettext' => _w('Gettext (recommended)'),
            'php'     => _w('PHP'),
        );

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' || !function_exists('gettext')) {
            $locale_adapter = false;
        } else {
            $locale_adapter = get_class(waLocale::$adapter) == 'waLocalePHPAdapter' ? 'php' : 'gettext';
        }

        // Parse wa-config/config.php
        $config_path = waSystem::getInstance()->getConfigPath().'/config.php';
        $config = file_exists($config_path) ? include($config_path) : array();
        if (!is_array($config)) {
            $config = array();
        }

        // PHP Version
        $php_version = PHP_VERSION;
        $is_good_php_version = version_compare($php_version, self::STABLE_PHP_VERSION_FOR_FRAMEWORK, '>=');

        $this->view->assign(array(
            'settings'             => $settings,
            'locales'              => $locales,
            'locale_adapters_list' => $locale_adapters_list,
            'locale_adapter'       => $locale_adapter,
            'config'               => $config,
            'framework_version'    => wa()->getVersion('webasyst'),
            'php_version'          => $php_version,
            'is_good_php_version'  => $is_good_php_version,
        ));
    }
}