<?php

class webasystSettingsSidebarAction extends webasystSettingsViewAction
{
    protected $waid_is_connected = null;

    public function execute()
    {
        $this->assignWaTransportBlock();

        $this->view->assign(array(
            'items' => webasystHelper::getSettingsSidebarItems(),
        ));
        $this->setTemplate('settings/sidebar/Sidebar.html', true);
    }

    private function formatAmount($amount, $currency_id)
    {
        $precision = strpos(strrev(strval($amount)), '.');
        $format = ($precision > 1) ? '%'.$precision : '%0';
        $amount_str = waCurrency::format($format, $amount, $currency_id);
        return $currency_id === 'RUB' ? $amount_str . ' <span class="ruble">₽</span>' : '$' . $amount_str;
    }

    private function assignWaTransportBlock()
    {
        try {
            $wa_service_api = new waServicesApi();
            $waid_is_connected = $wa_service_api->isConnected();
        } catch (Throwable $e) {
            $waid_is_connected = false;
        }

        $this->view->assign('waid_is_connected', $waid_is_connected);
        if (!$waid_is_connected) {
            return;
        }

        $errors = [];
        $balance_amount = 0;

        $res = $wa_service_api->getBalance([waServicesApi::EMAIL_MESSAGE_SERVICE, waServicesApi::SMS_SERVICE, 'AI']);

        $status = ifset($res, 'status', null);
        if (empty($status) || $status >= 400) {
            $errors[] = ifset($res, 'response', 'error_description', ifset($res, 'response', 'error', ''));
        } else {
            $balance_amount = ifset($res, 'response', 'amount', 0);
            $currency_id = ifset($res, 'response', 'currency_id', '');
            $balance = $this->formatAmount($balance_amount, $currency_id);

            // emails
            $email_price_value = ifset($res, 'response', 'services', waServicesApi::EMAIL_MESSAGE_SERVICE, 'price', 0);
            if ($balance_amount > 0 && $email_price_value > 0) {
                $messages_count = intval(floor($balance_amount / $email_price_value));
            }
            $remaining_free_email = ifempty($res, 'response', 'services', waServicesApi::EMAIL_MESSAGE_SERVICE, 'remaining_free_calls', []);
            $remaining_pack_email = ifset($remaining_free_email, 'pack', 0);
            unset($remaining_free_email['pack']);

            // sms
            $sms_price_value = ifset($res, 'response', 'services', waServicesApi::SMS_SERVICE, 'price', 0);
            if ($balance_amount > 0 && $sms_price_value > 0) {
                $sms_count = intval(floor($balance_amount / $sms_price_value));
            }
            $remaining_free_sms = ifempty($res, 'response', 'services', waServicesApi::SMS_SERVICE, 'remaining_free_calls', []);
            $remaining_pack_sms = ifset($remaining_free_sms, 'pack', 0);
            unset($remaining_free_sms['pack']);

            // ai
            $ai_price_value = ifset($res, 'response', 'services', 'AI', 'price', 0);
            if ($balance_amount > 0 && $ai_price_value > 0) {
                $ai_count = intval(floor($balance_amount / $ai_price_value));
            }
            $remaining_free_ai = ifempty($res, 'response', 'services', 'AI', 'remaining_free_calls', []);
            $remaining_pack_ai = ifset($remaining_free_ai, 'pack', 0);
            unset($remaining_free_ai['pack']);
        }

        $this->view->assign([
            'wa_api_errors'     => $errors,
            'wa_is_positive_balance' => $balance_amount > 0,
            'wa_balance'        => ifset($balance, '—'),
            'wa_total_emails'   => ifset($messages_count, 0)
                                    + ifset($remaining_free_email, 'total', 0) // min(array_values($remaining_free_email) ?: [0]) 
                                    + ifset($remaining_pack_email, 0),
            'wa_total_sms'      => ifset($sms_count, 0)
                                    + ifset($remaining_free_sms, 'total', 0) // min(array_values($remaining_free_sms) ?: [0]) 
                                    + ifset($remaining_pack_sms, 0),
            'wa_total_ai'       => ifset($ai_count, 0)
                                    + ifset($remaining_free_ai, 'total', 0) // min(array_values($remaining_free_ai) ?: [0]) 
                                    + ifset($remaining_pack_ai, 0),
        ]);
    }
}
