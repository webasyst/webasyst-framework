<?php

class webasystSettingsMapsSaveController extends webasystSettingsJsonController
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $map_adapters = wa()->getMapAdapters();

        $adapter = waRequest::post('map_adapter', 'google', waRequest::TYPE_STRING_TRIM);
        $settings = waRequest::post('map_settings', array(), waRequest::TYPE_ARRAY);

        // Save default map adapter
        $model->set('webasyst', 'map_adapter', $adapter);

        // Save map adapter settings
        if (isset($map_adapters[$adapter])) {
            $map_adapters[$adapter]->saveSettings(ifset($settings[$adapter], array()));
        }
    }
}