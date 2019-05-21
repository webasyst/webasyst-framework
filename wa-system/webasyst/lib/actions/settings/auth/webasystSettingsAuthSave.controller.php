<?php

class webasystSettingsAuthSaveController extends webasystSettingsJsonController
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $settings = array(
            'auth_form_background'         => 'stock:bokeh_vivid.jpg',
            'auth_form_background_stretch' => 0,
        );
        foreach ($settings as $setting => $value) {
            $model->set('webasyst', $setting, waRequest::post($setting, $value, waRequest::TYPE_STRING_TRIM));
        }

        $config = waBackendAuthConfig::getInstance();
        $data = $this->getData();

        $errors = $this->validateEmailChannel($data);
        if ($errors) {
            $this->errors = array('email' => $errors);
            return;
        }

        $config->setData($data);
        if (!$config->commit()) {
            $this->errors = sprintf(_ws('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-config/');
        }
    }

    protected function getData()
    {
        $data = $this->getRequest()->post();
        $data['used_auth_methods'] = (!empty($data['used_auth_methods'])) ? array_keys($data['used_auth_methods']) : array();
        // Always must be set
        $data['used_auth_methods'][] = waAuthConfig::AUTH_METHOD_EMAIL;
        return is_array($data) ? $data : array();
    }

    protected function validateEmailChannel($data)
    {
        $channel_ids = $data['verification_channel_ids'];
        $vcm = new waVerificationChannelModel();
        $channels = $vcm->getChannels($channel_ids);

        $email_channel = null;
        foreach ($channels as $channel) {
            if ($channel['type'] === waVerificationChannelModel::TYPE_EMAIL) {
                $email_channel = $channel;
                break;
            }
        }

        if (!$email_channel) {
            return array(
                'required' => _ws('Email notifications must be enabled.')
            );
        }

        $email_channel = waVerificationChannel::factory($email_channel['id']);

        if (!($email_channel instanceof waVerificationChannelEmail)) {
            // being paranoid
            return array(
                'required' => _ws('Email notifications must be enabled.')
            );
        }

        $diagnostic = $email_channel->getAddressDiagnostic();
        if ($diagnostic) {
            return array(
                'diagnostic' => array($email_channel->getId() => $diagnostic)
            );
        }

        return array();
    }
}
