<?php

class webasystSettingsPushSaveController extends webasystSettingsJsonController
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $push_adapters = wa()->getPushAdapters();

        $adapter = waRequest::post('push_adapter', null, waRequest::TYPE_STRING_TRIM);
        $settings = waRequest::post('push_settings', array(), waRequest::TYPE_ARRAY);

        // Save push adapter settings
        if (!empty($adapter) && isset($push_adapters[$adapter])) {
            $model->set('webasyst', 'push_adapter', $adapter);
            $push_adapters[$adapter]->saveSettings(ifset($settings, $adapter, array()));
            $res = $push_adapters[$adapter]->setup();
        } else {
            $model->del('webasyst', 'push_adapter');
        }

        if (!empty($res['errors'])) {
            return $this->errors = $res['errors'];
        }

        if (!empty($res['reload'])) {
            $this->response = array('reload' => true);
        }
    }
}