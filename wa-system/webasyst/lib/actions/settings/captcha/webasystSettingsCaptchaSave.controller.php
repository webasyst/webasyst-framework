<?php

class webasystSettingsCaptchaSaveController extends webasystSettingsJsonController
{
    public function execute()
    {
        $captcha_config = array();
        $captcha_config_path = wa()->getConfig()->getConfigPath('config.php', true, 'webasyst');
        if (file_exists($captcha_config_path)) {
            $captcha_config = include ($captcha_config_path);
        }

        $captcha_config['captcha'][0] = waRequest::post('captcha', 'waPHPCaptcha', waRequest::TYPE_STRING);
        $captcha_config['captcha'][1] = waRequest::post('captcha_options', array(), waRequest::TYPE_ARRAY);

        waUtils::varExportToFile($captcha_config, $captcha_config_path);
    }
}