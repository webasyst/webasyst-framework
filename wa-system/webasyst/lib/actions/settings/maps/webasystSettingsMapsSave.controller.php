<?php

class webasystSettingsMapsSaveController extends webasystSettingsJsonController
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $map_adapters = wa()->getMapAdapters();

        $adapter = waRequest::post('map_adapter', 'google', waRequest::TYPE_STRING_TRIM);
        $settings = waRequest::post('map_settings', array(), waRequest::TYPE_ARRAY);
        $model->set('webasyst', 'map_adapter', $adapter);
        if (isset($map_adapters[$adapter])) {
            $map_adapters[$adapter]->setEnvironment(waMapAdapter::FRONTEND_ENVIRONMENT);
            $map_adapters[$adapter]->saveSettings(ifset($settings[$adapter], array()));
        }

        $backend_adapter = waRequest::post('backend_map_adapter', 'google', waRequest::TYPE_STRING_TRIM);
        $backend_settings = waRequest::post('backend_map_settings', array(), waRequest::TYPE_ARRAY);
        $model->set('webasyst', 'backend_map_adapter', $backend_adapter);
        if (isset($map_adapters[$backend_adapter])) {
            $map_adapters[$backend_adapter]->setEnvironment(waMapAdapter::BACKEND_ENVIRONMENT);
            $map_adapters[$backend_adapter]->saveSettings(ifset($backend_settings[$backend_adapter], array()));
        }
    }
}