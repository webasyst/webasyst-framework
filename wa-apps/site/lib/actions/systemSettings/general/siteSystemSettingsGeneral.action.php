<?php

class siteSystemSettingsGeneralAction extends siteSystemSettingsViewAction
{
    public function execute()
    {
        // Webasyst app settings
        $model = new waAppSettingsModel();
        $settings = array(
            'name'                         => 'Webasyst',
            'url'                          => wa()->getRootUrl(true),
            'auth_form_background'         => 'stock:bokeh_vivid.jpg',
            'auth_form_background_stretch' => 1,
            'locale'                       => 'ru_RU',
            'rememberme'                   => 1,
        );
        foreach ($settings as $setting => &$value) {
            $value = $model->get('webasyst', $setting, $value);
        }
        unset($value);

        // Locales
        $locales = waSystem::getInstance()->getConfig()->getLocales('name');

        // Backgrounds
        $backgrounds_path = wa()->getConfig()->getPath('content').'/img/backgrounds/thumbs';
        $backgrounds = $this->getImages($backgrounds_path);
        // Custom backgrounds
        $images_path = wa()->getDataPath(null, true, 'webasyst');
        $images = $this->getImages($images_path);
        $images_url = wa()->getDataUrl(null, true, 'webasyst');
        // Custom used background image
        $name = preg_replace('/\?.*$/', '', $settings['auth_form_background']);
        $path = wa()->getDataPath($name, true, 'webasyst');
        if (strpos($settings['auth_form_background'], 'stock:') === 0) {
            $custom_image = false;
        } elseif ($settings['auth_form_background'] && file_exists($path)) {
            $settings['auth_form_background'] = preg_replace('@\?\d+$@', '', $settings['auth_form_background']);
            $image = new waImage($path);
            $custom_image = get_object_vars($image);
            $custom_image['file_size'] = filesize($path);
            $custom_image['file_mtime'] = filemtime($path);
            $custom_image['file_name'] = basename($path);
            unset($image);
        } elseif ($settings['auth_form_background']) {
            $custom_image = null;
        }
        if (empty($custom_image) && $images && file_exists($images_path.'/'.reset($images))) {
            $image = new waImage($path = $images_path.'/'.reset($images));
            $custom_image = get_object_vars($image);
            $custom_image['file_size'] = filesize($path);
            $custom_image['file_mtime'] = filemtime($path);
            $custom_image['file_name'] = basename($path);
        }

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

        $this->view->assign(array(
            'settings'             => $settings,
            'locales'              => $locales,
            'backgrounds'          => $backgrounds,
            'images'               => $images,
            'images_url'           => $images_url,
            'images_path'          => $images_path,
            'custom_image'         => $custom_image,
            'locale_adapters_list' => $locale_adapters_list,
            'locale_adapter'       => $locale_adapter,
            'config'               => $config,
        ));
    }

    private function getImages($path)
    {
        $files = waFiles::listdir($path);
        foreach ($files as $id => $file) {
            if (!is_file($path.'/'.$file) || !preg_match('@\.(jpe?g|png|gif|bmp)$@', $file)) {
                unset($files[$id]);
            }
        }
        return array_values($files);
    }
}