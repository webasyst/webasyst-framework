<?php

class webasystLoginLayout extends waLayout
{
    public function execute()
    {
        $app_settings_model = new waAppSettingsModel();
        $background = $app_settings_model->get('webasyst', 'auth_form_background');
        $stretch = $app_settings_model->get('webasyst', 'auth_form_background_stretch');
        if ($background) {
            $background = 'wa-data/public/webasyst/'.$background;
        }
        $this->view->assign('stretch', $stretch);
        $this->view->assign('background', $background);

        $this->view->assign('env', wa()->getEnv());

        $this->template = wa()->getAppPath('templates/layouts/Login.html', 'webasyst');
    }
}