<?php

class photosPluginsSaveController extends waJsonController
{
    public function execute()
    {
        $plugin_id = waRequest::get('id');
        if (!$plugin_id) {
            throw new waException(_ws("Can't save plugin settings: unknown plugin id"));
        }
        $namespace = 'photos_'.$plugin_id;
        /**
         * @var photosPlugin $plugin
         */
        $plugin = waSystem::getInstance()->getPlugin($plugin_id);
        $settings = (array)$this->getRequest()->post($namespace);
        $files = waRequest::file($namespace);
        $settings_defenitions = $plugin->getSettings();
        foreach ($files as $name => $file) {
            if (isset($settings_defenitions[$name])) {
                $settings[$name] = $file;
            }
        }
        try {
            $plugin->saveSettings($settings);
        } catch (Exception $e) {
            $this->errors = $e->getMessage();
        }
    }
}