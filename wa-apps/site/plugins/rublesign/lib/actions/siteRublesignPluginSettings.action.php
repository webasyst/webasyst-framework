<?php

class siteRublesignPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $generated_ruble = wa()->getAppStaticUrl('site') . $this->getPluginRoot() . 'img/generated_ruble.png';

        $currency_config = null;
        $app_settings = new waAppSettingsModel();
        $currency_sign = $app_settings->get('site.rublesign', 'sign', 'ruble');
        $status = $app_settings->get('site.rublesign', 'status', 0);
        $is_foreign_config = $currency_config !== null && !empty($app_settings->get('site.rublesign', 'is_foreign_config', 1));

        $this->view->assign([
            'currency_sign' => $currency_sign,
            'generated_ruble' => $generated_ruble,
            'status' => $status,
            'is_foreign_config' => $is_foreign_config,
        ]);
    }
}