<?php

class webasystLoginLayout extends waLayout
{
    public function execute()
    {
        list($background, $stretch) = self::getBackground();
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

    public static function getBackground()
    {
        $app_settings_model = new waAppSettingsModel();
        $background = $app_settings_model->get('webasyst', 'auth_form_background', 'stock:bokeh_vivid.jpg');
        if (!$background) {
            return array('', false);
        }

        $is_stock = 'stock:' === substr($background, 0, 6);
        $stretch = $is_stock || $app_settings_model->get('webasyst', 'auth_form_background_stretch', true);
        if ($is_stock) {
            $background = wa_url().'wa-content/img/backgrounds/'.substr($background, 6);
        } else {
            $background = wa()->getDataUrl(null, true, 'webasyst').'/'.$background;
        }
        return array($background, $stretch);
    }
}
