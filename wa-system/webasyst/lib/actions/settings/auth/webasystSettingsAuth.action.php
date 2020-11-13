<?php

class webasystSettingsAuthAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $model = new waAppSettingsModel();
        $settings = array(
            'auth_form_background'         => 'stock:bokeh_vivid.jpg',
            'auth_form_background_stretch' => 1,
        );
        foreach ($settings as $setting => &$value) {
            $value = $model->get('webasyst', $setting, $value);
        }
        unset($value);

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

        // Auth config
        $auth_config = waBackendAuthConfig::getInstance();
        $verification_channels = $auth_config->getAvailableVerificationChannels();
        $email_channels = $sms_channels = array();
        foreach ($verification_channels as $id => $channel) {
            if ($channel['type'] == waVerificationChannelModel::TYPE_EMAIL) {
                $email_channels[$id] = $channel;
            } else {
                $sms_channels[$id] = $channel;
            }
        }

        $this->extendByDiagnosticInfo($email_channels);

        $used_auth_methods = $auth_config->getUsedAuthMethods();


        $this->view->assign(array(
            'auth_config'                => $auth_config->getData(),
            'auth_types'                 => $auth_config->getAuthTypes(),
            'onetime_password_timeout'   => $auth_config->getOnetimePasswordTimeout(),
            'used_auth_methods'          => $used_auth_methods,
            'email_channels'             => $email_channels,
            'sms_channels'               => $sms_channels,
            'login_captcha_variants'     => $auth_config->getLoginCaptchaVariants(),
            'verification_channel_types' => $auth_config->getVerificationChannelTypes(),
            'verification_channels'      => $auth_config->getAvailableVerificationChannels(),
            'settings'                   => $settings,
            'backgrounds'                => $backgrounds,
            'images'                     => $images,
            'images_url'                 => $images_url,
            'images_path'                => $images_path,
            'custom_image'               => $custom_image,
            'demo_captcha'               => wa()->getCaptcha(),
            'is_waid_connected'          => $this->isWaidConnected(),
            'is_backend_auth_forced'     => $this->isWaidForced(),
            'is_user_bound_to_webasyst_id' => (bool)wa()->getUser()->getWebasystContactId(),
            'waid_settings_link' => wa()->getAppUrl('webasyst') . 'webasyst/settings/waid/'
        ));
    }

    private function isWaidConnected()
    {
        $cm = new waWebasystIDClientManager();
        return $cm->isConnected();
    }

    private function isWaidForced()
    {
        $cm = new waWebasystIDClientManager();
        return $cm->isBackendAuthForced();
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

    /**
     * ONLY FOR EMAIL CHANNELS
     * @param array & $email_channels
     */
    protected function extendByDiagnosticInfo(&$email_channels)
    {
        foreach ($email_channels as $index => &$email_channel) {

            /**
             * @var waVerificationChannelEmail $channel
             */
            $channel = waVerificationChannel::factory($email_channel['id']);

            if (!($channel instanceof waVerificationChannelEmail)) {
                // being paranoid
                unset($email_channels[$index]);
                continue;
            }

            $diagnostic = $channel->getAddressDiagnostic();
            $email_channel['diagnostic'] = $diagnostic;
        }
        unset($email_channel);
    }
}
