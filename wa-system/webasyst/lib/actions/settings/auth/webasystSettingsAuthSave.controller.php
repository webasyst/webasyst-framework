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

        $errors = $this->validate($data);
        if ($errors) {
            $this->errors = $errors;
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

        $data['used_auth_methods'] = isset($data['used_auth_methods']) && is_array($data['used_auth_methods']) ? $data['used_auth_methods'] : [];
        $data['used_auth_methods'] = array_filter($data['used_auth_methods'], function ($v) { return !empty($v); });
        $data['used_auth_methods'] = array_keys($data['used_auth_methods']);

        // Always must be set
        $data['used_auth_methods'][] = waAuthConfig::AUTH_METHOD_EMAIL;
        return is_array($data) ? $data : array();
    }

    protected function validate($data)
    {
        $data = is_array($data) ? $data : array();
        if (empty($data['used_auth_methods'])) {
            throw new waException(_ws('Saving error'));
        }

        $errors = $this->validateEmailChannel($data);
        if (!$errors) {

            if (in_array('sms', $data['used_auth_methods'])) {
                $errors = $this->validateSmsChannel($data);
                if (!$errors) {
                    $errors = $this->validatePhoneTransformPrefixes($data);
                }
            }

        }
        return $errors;
    }

    protected function validatePhoneTransformPrefixes($data)
    {
        $data = is_array($data) ? $data : array();

        $errors = array();

        $phone_transform_prefix = isset($data['phone_transform_prefix']) && is_array($data['phone_transform_prefix']) ? $data['phone_transform_prefix'] : array();

        $input_code = isset($phone_transform_prefix['input_code']) && is_scalar($phone_transform_prefix['input_code']) ? (string)$phone_transform_prefix['input_code'] : '';
        $output_code = isset($phone_transform_prefix['output_code']) && is_scalar($phone_transform_prefix['output_code']) ? (string)$phone_transform_prefix['output_code'] : '';

        $input_code_filled = strlen($input_code) > 0;
        $output_code_filled = strlen($output_code) > 0;

        $filled_only_one_code = $input_code_filled && !$output_code_filled || !$input_code_filled && $output_code_filled;
        if ($filled_only_one_code) {
            if (!$input_code_filled) {
                $errors["phone_transform_prefix[input_code]"] = _ws('Required');
            }
            if (!$output_code_filled) {
                $errors["phone_transform_prefix[output_code]"] = _ws('Required');
            }
        }

        if ($input_code_filled && !wa_is_int($input_code)) {
            $errors["phone_transform_prefix[input_code]"] = _ws('Enter digits only');
        }
        if ($output_code_filled && !wa_is_int($output_code)) {
            $errors["phone_transform_prefix[output_code]"] = _ws('Enter digits only');
        }

        return $errors;
    }

    protected function validateEmailChannel($data)
    {
        return $this->validateChannel($data, waVerificationChannelModel::TYPE_EMAIL);
    }

    protected function validateSmsChannel($data)
    {
        return $this->validateChannel($data, waVerificationChannelModel::TYPE_SMS);
    }

    protected function validateChannel($data, $type)
    {
        $channel_ids = $data['verification_channel_ids'];
        $vcm = new waVerificationChannelModel();
        $channels = $vcm->getChannels($channel_ids);

        $found_channel = null;
        foreach ($channels as $channel) {
            if ($channel['type'] === $type) {
                $found_channel = $channel;
                break;
            }
        }

        if (!$found_channel) {
            return array(
                $type => array(
                    'required' => $this->getErrorMessageForChannelType($type, 'required')
                )
            );
        }

        $found_channel = waVerificationChannel::factory($found_channel['id']);

        if ($found_channel->getType() != $type) {
            // being paranoid
            return array(
                $type => array(
                    'required' => $this->getErrorMessageForChannelType($type, 'required')
                )
            );
        }

        // for sms channel there is not address diagnostic (for this moment at least)
        $diagnostic = null;
        if ($found_channel instanceof waVerificationChannelEmail) {
            $diagnostic = $found_channel->getAddressDiagnostic();
        }

        if ($diagnostic) {
            return array(
                $type => array(
                    'diagnostic' => array($found_channel->getId() => $diagnostic)
                )
            );
        }

        return array();
    }

    protected function getErrorMessageForChannelType($type, $error_type)
    {
        if ($type === waVerificationChannelModel::TYPE_EMAIL) {
            if ($error_type === 'required') {
                return _ws('Email notifications must be enabled.');
            }
        } elseif ($type === waVerificationChannelModel::TYPE_SMS) {
            if ($error_type === 'required') {
                return _ws('No SMS notifications template group is selected.');
            }
        }
        return _ws('Unknown error');
    }


}
