<?php

class webasystLoginLayout extends waLayout
{
    public function execute()
    {
        $app_settings_model = new waAppSettingsModel();
        $background = $app_settings_model->get('webasyst', 'auth_form_background', 'stock:bokeh_vivid.jpg');
        $stretch = (strpos($background, 'stock:') === 0) ? true : $app_settings_model->get('webasyst', 'auth_form_background_stretch', true);
        if ($background) {
            if (strpos($background, 'stock:') === 0) {
                $background = 'wa-content/img/backgrounds/'.str_replace('stock:', '', $background);
            } else {
                $background = 'wa-data/public/webasyst/'.$background;
            }
        }
        $this->view->assign('stretch', $stretch);
        $this->view->assign('background', $background);

        $this->view->assign('env', wa()->getEnv());

        $template_file = wa()->getDataPath('templates/layouts/Login.html', false, 'webasyst');
        if (file_exists($template_file)) {
            $this->template = 'file:' . $template_file;
        } else {
            $this->template = wa()->getAppPath('templates/layouts/Login.html', 'webasyst');
        }
    }
}
