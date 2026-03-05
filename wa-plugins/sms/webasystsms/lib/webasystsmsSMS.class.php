<?php

class webasystsmsSMS extends waSMSAdapter
{
    public function isConfigured()
    {
        try {
            return (new waServicesApi)->isConnected();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function send($to, $text, $from = null)
    {
        $res = $this->sendSms($to, $text, $from);
        if ($res['status'] == 200) {
            return true;
        }

        $error = ifset($res, 'response', 'error', '');
        $error_description = ifset($res, 'response', 'error_description', '');
        $this->log($to, $text, 'Response status: ' . $res['status'] . ', error: ' . $error_description.' ('.$error.')');
        return false;
    }

    public function getControlsHtml()
    {
        try {
            $wa_service_api = new waServicesApi();
            $waid_is_connected = $wa_service_api->isConnected();
            if ($wa_service_api->isBrokenConnection()) {
                return '<p class="state-caution-hint"><i class="fas fa-exclamation-circle"></i> '.
                    _ws('Connection to Webasyst ID server is broken. Please re-connect your account to continue using Webasyst SMS service.') . ' ' .
                    sprintf_wp(
                        'To do so, open the <a href="%s">Webasyst ID settings</a>, disable sign-in with Webasyst ID and enable it again.',
                        wa()->getConfig()->getBackendUrl(true) . 'webasyst/settings/waid/'
                    ) . '</p>';
            }
        } catch (Throwable $e) {
            $waid_is_connected = false;
        }
        if (!$waid_is_connected) {
            return '<p class="small">'.
            sprintf_wp(
                '<a href="%s">Connect to Webasyst ID</a> to use the Webasyst SMS.',
                wa()->getConfig()->getBackendUrl(true) . 'webasyst/settings/waid/'
            ) . '</p>';
        }
        $res = $wa_service_api->getBalance(waServicesApi::SMS_SERVICE);
        if ($res['status'] != 200) {
            return '<p class="state-caution-hint"><i class="fas fa-exclamation-circle"></i> '.
            _ws('Something went wrong... Connection to Webasyst ID server may be broken. Perhaps you need to re-connect your account to use the Webasyst SMS service.') . ' ' .
            sprintf_wp(
                'To do so, open the <a href="%s">Webasyst ID settings</a>, disable sign-in with Webasyst ID and enable it again.',
                wa()->getConfig()->getBackendUrl(true) . 'webasyst/settings/waid/'
            ) . '</p>';
        }

        $balance_amount = ifset($res, 'response', 'amount', 0);
        $price_value = ifset($res, 'response', 'price', 0);
        $currency_id = ifset($res, 'response', 'currency_id', wa()->getLocale() === 'ru_RU' ? 'RUB' : 'USD');
        $balance = wa_currency_html($balance_amount, $currency_id);
        $price = wa_currency_html($price_value, $currency_id);
        $free_limits = ifset($res, 'response', 'free_limits', []);
        $remaining_free_calls = ifempty($res, 'response', 'remaining_free_calls', []);
        $remaining_pack = ifset($remaining_free_calls, 'pack', 0);
        unset($remaining_free_calls['pack']);
        if ($balance_amount > 0 && $price_value > 0) {
            $messages_count = intval(floor($balance_amount / $price_value));
        }

        $res = $wa_service_api->getIpWhiteList();
        $white_list = ifset($res, 'response', 'list', []);
        $is_allowed_ip = ifset($res, 'response', 'is_allowed_ip', true);
        $current_ip = ifset($res, 'response', 'your_ip', '');

        $view = wa()->getView();
        $view->assign([
            'wa_balance'        => ifset($balance, '—'),
            'wa_price'          => ifset($price, '—'),
            'wa_free_limits'    => ifset($free_limits, []),
            'wa_white_list'     => ifset($white_list, []),
            'wa_is_allowed_ip'  => ifset($is_allowed_ip, true),
            'wa_current_ip'     => ifset($current_ip, ''),
            'wa_remaining_free_calls' => ifset($remaining_free_calls, []),
            'wa_total'          => ifset($messages_count, 0)
                                    + ifset($remaining_free_calls, 'total', 0) // min(array_values($remaining_free_calls) ?: [0])
                                    + ifset($remaining_pack, 0),
            'service'           => waServicesApi::SMS_SERVICE,
            'waid_balance_show_sms_notice' => empty($this->options) && wa()->getConfig()->getConfigFile('sms'),
        ]);
         $template_path = wa()->getConfig()->getAppsPath('webasyst').'/templates/actions/services/balance.html';
        return $view->fetch($template_path);
    }

    protected function sendSms($to, $text, $from = null)
    {
        $app_id = wa()->getApp();
        return (new waServicesApi())->serviceCall(waServicesApi::SMS_SERVICE, [
            'to' => $to,
            'text' => $text,
            'from' => $from,
            'app_id' => $app_id,
            'domain' => wa()->getRouting()->getDomain(),
        ], waNet::METHOD_POST, ['request_format' => waNet::FORMAT_JSON]);
    }
}
