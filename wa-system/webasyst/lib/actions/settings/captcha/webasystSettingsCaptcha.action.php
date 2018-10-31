<?php

class webasystSettingsCaptchaAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $captcha_config_path = wa()->getConfig()->getConfigPath('config.php', true, 'webasyst');
        // Include captcha settings
        if (file_exists($captcha_config_path)) {
            $captcha = include($captcha_config_path);
            $this->view->assign(array(
                'captcha'         => ifset($captcha, 'captcha', 0, null),
                'captcha_options' => ifset($captcha, 'captcha', 1, null),
            ));
        }
    }
}