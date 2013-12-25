<?php
/**
 * @version 2:0.9.5
 * @property-read string $shop_id Store ID
 * @property-read string $secret_key Secret key
 * @property-read array $currency transaction currency
 * @property-read bool $test is testing mode
 */
class interkassaPayment extends waPayment
{
    private $pattern = '/^(\w[\w\d]+)_([\w\d]+)_(.+)$/';

    /**
     * @var string %app_id%_%merchant_id%_%order_id%
     */
    private $template = '%s_%s_%s';

    public function allowedCurrency()
    {
        return $this->test ? true : (is_string($this->currency) ? $this->currency : array_keys(array_filter(array_map('intval', $this->currency))));
    }

    public static function availableCurrency()
    {
        $allowed = array(
            'EUR',
            'USD',
            'UAH',
            'RUB',
            'BYR',
            'XAU', //Золото (одна тройская унция)
        );
        $available = array();
        $app_config = wa()->getConfig();
        if (method_exists($app_config, 'getCurrencies')) {
            $currencies = $app_config->getCurrencies();
            foreach ($currencies as $code => $c) {
                if (in_array($code, $allowed)) {
                    $available[] = array(
                        'value'       => $code,
                        'title'       => sprintf('%s %s', $c['code'], $c['title']),
                        'description' => $c['sign'],
                    );
                }
            }
        }
        return $available;
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);

        $hidden_fields = array();

        $hidden_fields['ik_am'] = str_replace(',', '.', sprintf('%0.2f', $order->total));
        $hidden_fields['ik_pm_no'] = sprintf($this->template, $this->app_id, $this->merchant_id, $order->id);
        $hidden_fields['ik_desc'] = mb_substr($order->description, 0, 255, "UTF-8");

        $hidden_fields['ik_cur'] = $order->currency;
        if ($this->test) {
            $hidden_fields['ik_pw_via'] = 'test_interkassa_test_xts';
        }

        $transaction_data = $this->formalizeData($hidden_fields);

        $hidden_fields['ik_suc_u'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
        $hidden_fields['ik_fal_u'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);
        $hidden_fields['ik_pnd_u'] = $hidden_fields['ik_suc_u'];
        $hidden_fields['ik_ia_u'] = $this->getRelayUrl();

        $this->getSign($hidden_fields);

        $view = wa()->getView();
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('hidden_fields', $hidden_fields);
        $view->assign('auto_submit', $auto_submit);
        return $view->fetch($this->path.'/templates/payment.html');
    }

    private function getEndpointUrl()
    {
        return 'https://sci.interkassa.com';
    }

    protected function callbackInit($request)
    {
        if (preg_match($this->pattern, ifset($request['ik_pm_no']), $matches)) {
            $this->app_id = $matches[1];
            $this->merchant_id = $matches[2];
        }

        return parent::callbackInit($request);
    }

    protected function callbackHandler($request)
    {
        if (empty($request['ik_co_id']) || ($this->shop_id != $request['ik_co_id'])) {
            throw new waException('Invalid shop id');
        }

        $result = array();
        $transaction_data = $this->formalizeData($request);

        $sign = ifempty($request['ik_sign']);
        if (!$sign || ($sign != $this->getSign($request, true))) {
            throw new waException('Invalid request sign');
        }

        $callback_method = null;
        switch (ifset($transaction_data['state'])) {
            case self::STATE_CAPTURED:
                $callback_method = self::CALLBACK_PAYMENT;
                break;
            case self::STATE_DECLINED:
                $callback_method = self::CALLBACK_DECLINE;
                break;
        }

        if ($callback_method) {
            $transaction_data = $this->saveTransaction($transaction_data, $request);
            $this->execAppCallback($callback_method, $transaction_data);
        }
        return $result;
    }

    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data = parent::formalizeData($transaction_raw_data);
        $view_data = array();
        $order_id = null;

        if (preg_match($this->pattern, ifset($transaction_raw_data['ik_pm_no']), $matches)) {
            $order_id = $matches[3];
        }

        $fields = array(
            'ik_fees_payer' => 'Плательщик комиссии',
            'ik_pw_via'     => 'Способ оплаты',
            'ik_inv_id'     => 'Идентификатор',
            'ik_inv_crt'    => 'Время создания платежа',
            'ik_inv_prc'    => 'Время проведения',

        );

        $map = array(
            'ik_pw_via' => array(
                'privatterm_liqpay_merchant_uah'       => 'Терминалы Приватбанка - LiqPay - Мерчант',
                'anelik_w1_merchant_rub'               => 'Anelik - Единый кошелек - Мерчант',
                'beeline_w1_merchant_rub'              => 'Билайн - Единый кошелек - Мерчант',
                'contact_w1_merchant_rub'              => 'CONTACT - Единый кошелек - Мерчант',
                'lider_w1_merchant_rub'                => 'ЛИДЕР - Единый кошелек - Мерчант',
                'megafon_w1_merchant_rub'              => 'Мегафон - Единый кошелек - Мерчант',
                'mobileretails_w1_merchant_rub'        => 'Салоны связи - Единый кошелек - Мерчант',
                'mts_w1_merchant_rub'                  => 'МТС - Единый кошелек - Мер чант',
                'qiwiwallet_w1_merchant_rub'           => 'Qiwi Кошелек - Единый кошелек - Мерчант',
                'ruspost_w1_merchant_rub'              => 'Почта Росси - Единый кошелек - Мерчант',
                'rusterminal_w1_merchant_rub'          => 'Терминалы Росси - Единый кошелек - Мерчант',
                'unistream_w1_merchant_rub'            => 'Юнистрим - Единый кошелек - Мерчант',
                'webmoney_merchant_wmb'                => 'WebMoney - Мерчант',
                'webmoney_merchant_wme'                => 'WebMoney - Мерчант',
                'webmoney_merchant_wmg'                => 'WebMoney - Мерчант',
                'webmoney_merchant_wmr'                => 'WebMoney - Мерчант',
                'webmoney_merchant_wmu'                => 'WebMoney - Мерчант',
                'webmoney_merchant_wmz'                => 'WebMoney - Мерчант',
                'nsmep_smartpay_invoice_uah'           => 'НСМЕП - SmartPay - Выставление счета',
                'yandexmoney_merchant_rub'             => 'Yandex.Money - Мерчант',
                'zpayment_merchant_rub'                => 'Z-payment - Мерчант',
                'sbrf_rusbank_receipt_rub'             => 'Сбербанк РФ - Российский банк - Квитанция',
                'webmoney_invoice_wmz'                 => 'WebMoney - Выставление счета',
                'webmoney_invoice_wmu'                 => 'WebMoney - Выставление счета',
                'webmoney_invoice_wmr'                 => 'WebMoney - Выставление счета',
                'webmoney_invoice_wmg'                 => 'WebMoney - Выставление счета',
                'webmoney_invoice_wme'                 => 'WebMoney - Выставление счета',
                'webmoney_invoice_wmb'                 => 'WebMoney - Выставление счета',
                'webcreds_merchant_rub'                => 'WebCreds - Мерчант',
                'w1_merchant_usdw1_w1_merchant_usd'    => 'Единый кошелек - Мерчант',
                'w1_merchant_uahw1_w1_merchant_uah'    => 'Единый кошелек - Мерчант',
                'w1_merchant_rubw1_w1_merchant_rub'    => 'Единый кошелек - Мерчант',
                'w1_merchant_eurw1_w1_merchant_eur'    => 'Единый кошелек - Мерчант',
                'visa_liqpay_merchant_usd'             => 'Visa - LiqPay - Мерчант',
                'visa_liqpay_merchant_rub'             => 'Visa - LiqPay - Мерчант',
                'visa_liqpay_merchant_eur'             => 'Visa - LiqPay - Мерчант',
                'ukrbank_receipt_uah'                  => 'Украинский банк - Квитанция',
                'ukash_w1_merchant_usd'                => 'Ukash - Единый кошелек - Мерчант',
                'rusbank_receipt_rub'                  => 'Российский банк - Квитанция',
                'rbkmoney_merchant_rub'                => 'RBK Money - Мерчант',
                'privat24_merchant_usd'                => 'Privat24 - Мерчант',
                'privat24_merchant_uah'                => 'Privat24 - Мерчант',
                'privat24_merchant_eur'                => 'Privat24 - Мерчант',
                'perfectmoney_merchant_usd'            => 'PerfectMoney - Мерчант',
                'perfectmoney_merchant_eur'            => 'PerfectMoney - Мерчант',
                'moneymail_merchant_usd'               => 'MoneyMail - Мерчант',
                'moneymail_merchant_rub'               => 'MoneyMail - Мерчант',
                'moneymail_merchant_eur'               => 'MoneyMail - Мерчант',
                'monexy_merchant_usd'                  => 'MoneXy - Мерчант',
                'monexy_merchant_uah'                  => 'MoneXy - Мерчант',
                'monexy_merchant_rub'                  => 'MoneXy - Мерчант',
                'monexy_merchant_eur'                  => 'MoneXy - Мерчант',
                'mastercard_liqpay_merchant_usd'       => 'Mastercard - LiqPay - Мерчант',
                'mastercard_liqpay_merchant_rub'       => 'Mastercard - LiqPay - Мерчант',
                'mastercard_liqpay_merchant_eur'       => 'Mastercard - LiqPay - Мерчант',
                'liqpay_merchant_usd'                  => 'LiqPay - Мерчант',
                'liqpay_merchant_uah'                  => 'LiqPay - Мерчант',
                'liqpay_merchant_rub'                  => 'LiqPay - Мерчант',
                'liqpay_merchant_eur'                  => 'LiqPay - Мерчант',
                'libertyreserve_merchant_usd'          => 'Liberty Reserve - Мерчант',
                'libertyreserve_merchant_eur'          => 'Liberty Reserve - Мерчант',
                'eurobank_receipt_usd'                 => 'Wire Transfer - Квитанция',
                'paypal_merchant_usd'                  => 'Paypal - Мерчант',
                'alfaclick_w1_merchant_rub'            => 'Альфаклик (Альфабанк) - Единый кошелек - Мерчант',
                'interkassa_voucher_usd'               => 'Интеркасса - Ваучер',
                'visa_liqpay_merchant_uah'             => 'Visa - LiqPay - Мерчант',
                'mastercard_liqpay_merchant_uah'       => 'Mastercard - LiqPay - Мерчант',
                'rbkmoney_merchantx_rub'               => 'RBK Money - Мерчант',
                'telemoney_merchant_rub'               => 'Telemoney - Мерчант',
                'ukrterminal_webmoneyuga_terminal_uah' => 'Терминалы Украины - Webmoney UGA - Терминал',
                'test_interkassa_test_xts'             => 'Тестовая платежная система - Интеркасса - Тест',
            ),
        );
        foreach ($fields as $field => $description) {
            if (ifset($transaction_raw_data[$field])) {
                if (isset($map[$field][$transaction_raw_data[$field]])) {
                    $view_data[] = $description.': '.$map[$field][$transaction_raw_data[$field]];
                } else {
                    $view_data[] = $description.': '.$transaction_raw_data[$field];
                }
            }
        }


        $transaction_data = array_merge($transaction_data, array(
            'type'        => null,
            'native_id'   => ifset($transaction_raw_data['ik_trn_id']),
            'amount'      => ifset($transaction_raw_data['ik_am']),
            'currency_id' => ifset($transaction_raw_data['ik_cur']),
            'result'      => 1,
            'order_id'    => $order_id,
            'view_data'   => implode("\n", $view_data),
        ));

        switch (ifset($transaction_raw_data['ik_inv_st'])) {
            case 'success':
                $transaction_data['state'] = self::STATE_CAPTURED;
                $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
                break;
            case 'fail':
                $transaction_data['state'] = self::STATE_DECLINED;
                $transaction_data['type'] = self::OPERATION_CANCEL;
                break;
        }
        return $transaction_data;
    }

    private function getSign(&$data)
    {
        if (isset($data['ik_sign'])) {
            $callback = true;
            unset($data['ik_sign']);
        }
        $data['ik_co_id'] = ifempty($data['ik_co_id'], $this->shop_id);
        $fields = array_filter(array_keys($data), create_function('$k', 'return strpos(strtolower($k),"ik_")===0;'));
        sort($fields, SORT_STRING);


        $data['ik_sign'] = '';
        foreach ($fields as $field) {

            $data['ik_sign'] .= isset($data[$field]) ? $data[$field] : '';
            $data['ik_sign'] .= ':';
        }
        $key = (($this->test && $callback) ? $this->test_key : $this->secret_key);
        $data['ik_sign'] = base64_encode(md5($data['ik_sign'].$key, true));
        return $data['ik_sign'];
    }
}
