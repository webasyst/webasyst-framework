<?php

class webasystSettingsAuthSaveController extends webasystSettingsJsonController
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $settings = array(
            'auth_form_background'         => 'stock:bokeh_vivid.jpg',
            'auth_form_background_stretch' => 0,
            'rememberme'                   => 0,
        );
        foreach ($settings as $setting => $value) {
            $model->set('webasyst', $setting, waRequest::post($setting, $value, waRequest::TYPE_STRING_TRIM));
        }

        $config = waBackendAuthConfig::getInstance();
        $data = $this->getData();
        $config->setData($data);
        if (!$config->commit()) {
            $this->errors = sprintf(_ws('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-config/');
        }
    }

    protected function getData()
    {
        $data = $this->getRequest()->post();
        $data['used_auth_methods'] = (!empty($data['used_auth_methods'])) ? array_keys($data['used_auth_methods']) : array();
        return is_array($data) ? $data : array();
    }
}
