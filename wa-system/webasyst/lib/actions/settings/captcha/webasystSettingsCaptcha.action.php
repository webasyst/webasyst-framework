<?php

class webasystSettingsCaptchaAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $captcha = $this->getCaptchaConfig();
        $this->view->assign(array(
            'captcha'         => ifset($captcha, 'captcha', 0, null),
            'captcha_options' => ifset($captcha, 'captcha', 1, null),
        ));
    }

    private function getCaptchaConfig()
    {
        $captcha = [];

        $captcha_config_path = wa()->getConfig()->getConfigPath('config.php', true, 'webasyst');
        // Include captcha settings
        if (file_exists($captcha_config_path)) {
            $captcha = include($captcha_config_path);
        }

        return $captcha;
    }
}
