<?php

class installerServicesAction extends waViewAction
{
    public function execute()
    {
        $this->setLayout(new installerBackendStoreLayout());

        try {
            $wa_service_api = new installerServicesApi();
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

        $res = $wa_service_api->getBalance([installerServicesApi::EMAIL_MESSAGE_SERVICE, installerServicesApi::SMS_SERVICE]);
        $status = ifset($res, 'status', null);
        if (empty($status) || $status >= 400) {
            $errors[] = ifset($res, 'response', 'error_description', ifset($res, 'response', 'error', ''));
        } else {
            $balance_amount = ifset($res, 'response', 'amount', 0);
            $currency_id = ifset($res, 'response', 'currency_id', '');
            $balance = $this->formatAmount($balance_amount, $currency_id);

            // emails
            $email_price_value = ifset($res, 'response', 'services', installerServicesApi::EMAIL_MESSAGE_SERVICE, 'price', 0);
            if ($balance_amount > 0 && $email_price_value > 0) {
                $messages_count = intval(floor($balance_amount / $email_price_value));
            }

            $email_packs = ifset($res, 'response', 'services', installerServicesApi::EMAIL_MESSAGE_SERVICE, 'packs', []);
            $email_min_price_pack = array_reduce($email_packs, function($res, $pack) {
                if (empty($res) || $pack['call_price'] < $res['call_price']) {
                    return $pack;
                }
                return $res;
            });
            $email_price_str = ifset($res, 'response', 'services', installerServicesApi::EMAIL_MESSAGE_SERVICE, 'price_str', '');
            if (!empty($email_min_price_pack['call_price']) && $email_price_value > $email_min_price_pack['call_price']) {
                $email_price_value = $email_min_price_pack['call_price'];
                $email_price_str = $email_min_price_pack['call_price_str'];
            }

            $email_free_limits = ifempty($res, 'response', 'services', installerServicesApi::EMAIL_MESSAGE_SERVICE, 'free_limits', []);
            $remaining_free_email = ifempty($res, 'response', 'services', installerServicesApi::EMAIL_MESSAGE_SERVICE, 'remaining_free_calls', []);
            $remaining_pack_email = ifset($remaining_free_email, 'pack', 0);
            unset($remaining_free_email['pack']);

            // sms
            $sms_price_value = ifset($res, 'response', 'services', installerServicesApi::SMS_SERVICE, 'price', 0);
            if ($balance_amount > 0 && $sms_price_value > 0) {
                $sms_count = intval(floor($balance_amount / $sms_price_value));
            }

            $sms_packs = ifset($res, 'response', 'services', installerServicesApi::SMS_SERVICE, 'packs', []);
            $sms_min_price_pack = array_reduce($sms_packs, function($res, $pack) {
                if (empty($res) || $pack['call_price'] < $res['call_price']) {
                    return $pack;
                }
                return $res;
            });
            $sms_price_str = ifset($res, 'response', 'services', installerServicesApi::SMS_SERVICE, 'price_str', '');
            if (!empty($sms_min_price_pack['call_price']) && $sms_price_value > $sms_min_price_pack['call_price']) {
                $sms_price_value = $sms_min_price_pack['call_price'];
                $sms_price_str = $sms_min_price_pack['call_price_str'];
            }

            $sms_free_limits = ifempty($res, 'response', 'services', installerServicesApi::SMS_SERVICE, 'free_limits', []);
            $remaining_free_sms = ifempty($res, 'response', 'services', installerServicesApi::SMS_SERVICE, 'remaining_free_calls', []);
            $remaining_pack_sms = ifset($remaining_free_sms, 'pack', 0);
            unset($remaining_free_sms['pack']);
        }

        $wamail = new waMail();
        $main_configs = array('default' => array());
        $main_configs = array_merge($main_configs, $wamail->readConfigFile());
        $is_email_connected = !empty(array_filter($main_configs, function($config) {
            return ifset($config['type']) == 'wasender';
        }));

        $is_wasms_installed = $this->isWebasystSMSPluginInstalled();
        $sms_config = wa()->getConfig()->getConfigFile('sms');
        $is_wasms_connected = $is_wasms_installed && !empty(array_filter($sms_config, function($config) {
            return ifset($config['adapter']) == 'webasystsms';
        }));

        $this->view->assign([
            'wa_api_errors'     => $errors,
            'wa_is_positive_balance' => $balance_amount > 0,
            'wa_balance'        => ifset($balance, '—'),
            'wa_total_emails'   => ifset($messages_count, 0)
                                    + ifset($remaining_free_email, 'total', 0) // min(array_values($remaining_free_email) ?: [0]) 
                                    + ifset($remaining_pack_email, 0),
            'email_free_limits' => ifset($email_free_limits),
            'email_remaining_free' => ifset($remaining_free_email),
            'wa_total_sms'      => ifset($sms_count, 0)
                                    + ifset($remaining_free_sms, 'total', 0) // min(array_values($remaining_free_sms) ?: [0]) 
                                    + ifset($remaining_pack_sms, 0),
            'sms_free_limits' => ifset($sms_free_limits),
            'sms_remaining_free' => ifset($remaining_free_sms),
            'currency_id'       => ifset($currency_id),
            'sms_price'         => $this->formatAmount(ifset($sms_price_value, 0), ifset($currency_id)),
            'email_price'       => $this->formatAmount(ifset($email_price_value, 0), ifset($currency_id)),
            'sms_price_value'   => ifset($sms_price_value),
            'sms_price_str'     => ifset($sms_price_str),
            'email_price_value' => ifset($email_price_value),
            'email_price_str'   => ifset($email_price_str),
            'remaining_pack_sms' => ifset($remaining_pack_sms),
            'remaining_pack_email' => ifset($remaining_pack_email),
            'is_email_connected' => $is_email_connected,
            'is_wasms_installed' => $is_wasms_installed,
            'is_wasms_connected' => $is_wasms_connected,
            'install_wa_sms_link' => wa()->getAppUrl('installer').'store/plugin/sms/webasystsms/',
        ]);
    }

    private function formatAmount($amount, $currency_id)
    {
        $precision = strpos(strrev(strval($amount)), '.');
        $format = ($precision > 1) ? '%'.$precision : '%0';
        $amount_str = waCurrency::format($format, $amount, $currency_id);
        return $currency_id === 'RUB' ? $amount_str . ' <span class="ruble">₽</span>' : '$' . $amount_str;
    }

    protected function isWebasystSMSPluginInstalled()
    {
        $path = $this->getConfig()->getPath('plugins').'/sms/webasystsms/lib/webasystsmsSMS.class.php';
        return file_exists($path);
   }
}