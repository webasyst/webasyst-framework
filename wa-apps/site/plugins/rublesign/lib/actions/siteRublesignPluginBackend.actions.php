<?php

class siteRublesignPluginBackendActions extends waJsonActions
{
    public function saveAction()
    {
        $status = waRequest::post('status', null, waRequest::TYPE_INT);
        if ($status !== null) {
            $app_settings = new waAppSettingsModel();
            $app_settings->set('site.rublesign', 'status', $status);
            if ($status) {
                $app_settings->set('site.rublesign', 'is_foreign_config', 0);
                $currency_sign = waRequest::post('currency_sign', 'ruble', waRequest::TYPE_STRING);
                $app_settings->set('site.rublesign', 'sign', $currency_sign);
                if ($currency_sign == 'ruble') {
                    $sign_html =  '<span class="ruble">Р</span>';
                } else {
                    $sign_html = $currency_sign == 'r' ? 'Р' : 'руб.';
                }
                $custom = array(
                    'RUB' => array(
                        'sign' => $currency_sign == 'ruble' ? 'руб.' : $sign_html,
                        'sign_html' => $sign_html,
                    )
                );
                waUtils::varExportToFile($custom, wa()->getConfig()->getPath('config', 'currency'));
            } else {
                $path = wa()->getConfig()->getPath('config', 'currency');
                waFiles::Delete($path, true);
            }
        }
    }
}