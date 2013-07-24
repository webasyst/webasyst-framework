<?php
/**
 * @version 1.2
 * @property string $shop_id Store ID
 * @property string $secret_key Secret key
 * @property string $paysystem_alias Payment method
 * @property string $currency transaction currency
 */
class interkassaPayment extends waPayment
{
    private $pattern = '/^(\w[\w\d]+)_([\w\d]+)_(.+)$/';
    private $template = '%s_%s_%s';

    public function allowedCurrency()
    {
        return $this->currency;
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);

        $hidden_fields = array();
        $hidden_fields['ik_payment_amount'] = sprintf('%0.2f', $order->total);
        $hidden_fields['ik_payment_id'] = sprintf($this->template, $this->app_id, $this->merchant_id, $order->id);
        $hidden_fields['ik_baggage_fields'] = ''; //Optional field
        $hidden_fields['ik_payment_desc'] = mb_substr($order->description, 0, 255, "UTF-8");

        $transaction_data = $this->formalizeData($hidden_fields);

        $hidden_fields['ik_success_url'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
        $hidden_fields['ik_fail_url'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);
        $hidden_fields['ik_status_url'] = $this->getRelayUrl();

        $this->getSign($hidden_fields);

        $view = wa()->getView();
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('hidden_fields', $hidden_fields);
        $view->assign('auto_submit', $auto_submit);
        return $view->fetch($this->path.'/templates/payment.html');
    }

    private function getEndpointUrl()
    {
        return 'http://www.interkassa.com/lib/payment.php';
    }

    public function getSettingsHTML($params = array())
    {
        $url = 'http://www.interkassa.com/lib/paysystems.currencies.export.php?format=xml';
        $options = array();
        if (($response = $this->request($url)) && ($xml = @simplexml_load_string($response))) {

            foreach ($xml->children() as $alias) {
                if ((int) ifset($alias['state'])) {
                    $options[] = array(
                        'value' => (string) ifset($alias['alias']),
                        'title' => trim((string) ifset($alias['currencyName'])),
                        'group' => trim((string) $alias),
                    );
                }
            }
            usort($options, create_function('$a,$b', 'return strcmp(ifset($a["group"]), ifset($b["group"]));'));

        }
        array_unshift($options, array(
            'value' => '',
            'title' => _wp('Customer choice'),
            'group' => _wp('Customer choice'),
        ));

        $params['options']['paysystem_alias'] = $options;
        $params['options']['currency'] = waCurrency::getAll();
        return parent::getSettingsHTML($params);
    }

    protected function callbackInit($request)
    {

        if (preg_match($this->pattern, ifset($request['ik_payment_id']), $matches)) {
            $this->app_id = $matches[1];
            $this->merchant_id = $matches[2];
            $order_id = $matches[3];
        }

        return parent::callbackInit($request);
    }

    protected function callbackHandler($request)
    {
        $request_fields = array(
            'ik_payment_id'        => 0,
            'ik_payment_amount'    => 0,
            'ik_sign_hash'         => '',
            'ik_shop_id'           => '',
            'ik_payment_state'     => 'unknown',
            'ik_trans_id'          => false,
            'ik_payment_timestamp' => 0,
            'ik_paysystem_alias'   => '',
            'ik_currency_exch'     => 1,
            'ik_fees_payer'        => false,
        );
        $request = array_merge($request_fields, $request);

        if (empty($request['ik_shop_id']) || ($this->shop_id != $request['ik_shop_id'])) {
            throw new waException('Invalid shop id');
        }

        $result = array();
        $transaction_data = $this->formalizeData($request);

        switch (ifset($request['result'])) {
            case 'success':
                $result['redirect'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
                break;
            case 'fail':
                $result['redirect'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);
                break;
            default:

                if (empty($request['ik_sign_hash']) || ($request['ik_sign_hash'] != $this->getRequestSign($request))) {
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
                    $callback = $this->execAppCallback($callback_method, $transaction_data);
                    self::addTransactionData($transaction_data['id'], $callback);
                }
                break;
        }
        return $result;
    }

    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data = parent::formalizeData($transaction_raw_data);

        $view_data = array();

        $order_id = null;

        if (preg_match($this->pattern, ifset($transaction_raw_data['ik_payment_id']), $matches)) {
            $order_id = $matches[3];
        }

        $fields = array(
            'ik_fees_payer'      => 'Плательщик комиссии',
            'ik_currency_exch'   => 'Курс валюты',
            'ik_paysystem_alias' => 'Способ оплаты',

        );
        foreach ($fields as $field => $description) {
            if (ifset($transaction_raw_data[$field])) {
                $view_data[] = $description.': '.$transaction_raw_data[$field];
            }
        }

        $transaction_data = array_merge($transaction_data, array(
            'type'        => null,
            'native_id'   => ifset($transaction_raw_data['ik_trans_id']),
            'amount'      => ifset($transaction_raw_data['ik_payment_amount']),
            'currency_id' => $this->currency,
            'result'      => 1,
            'order_id'    => $order_id,
            'view_data'   => implode("\n", $view_data),
        ));

        switch (ifset($transaction_raw_data['ik_payment_state'])) {
            case 'success':
                $transaction_data['state'] = self::STATE_CAPTURED;
                $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
                break;
            case 'failure':
                $transaction_data['state'] = self::STATE_DECLINED;
                $transaction_data['type'] = self::OPERATION_CANCEL;
                break;
        }
        return $transaction_data;
    }

    private function getSign(&$data)
    {
        $fields = array(
            'ik_shop_id',
            'ik_payment_amount',
            'ik_payment_id',
            'ik_paysystem_alias',
            'ik_baggage_fields',
        );

        $data['ik_shop_id'] = ifempty($data['ik_shop_id'], $this->shop_id);

        $data['ik_paysystem_alias'] = ifempty($data['ik_paysystem_alias'], $this->paysystem_alias);
        $data['ik_sign_hash'] = '';
        foreach ($fields as $field) {
            $data['ik_sign_hash'] .= isset($data[$field]) ? $data[$field] : '';
            $data['ik_sign_hash'] .= ':';
        }
        $data['ik_sign_hash'] = strtoupper(md5($data['ik_sign_hash'].$this->secret_key));
        return $data['ik_sign_hash'];
    }

    private function getRequestSign(&$data)
    {
        $fields = array(
            'ik_shop_id',
            'ik_payment_amount',
            'ik_payment_id',
            'ik_paysystem_alias',
            'ik_baggage_fields',
            'ik_payment_state',
            'ik_trans_id',
            'ik_currency_exch',
            'ik_fees_payer',
        );

        $hash = '';
        foreach ($fields as $field) {
            $hash .= isset($data[$field]) ? $data[$field] : '';
            $hash .= ':';
        }
        return strtoupper(md5($hash.$this->secret_key));
    }

    private function request($url)
    {
        $response = null;
        $hint = '';
        if (extension_loaded('curl') && function_exists('curl_init')) {
            $curl_error = null;
            if (!($ch = curl_init())) {
                $hint .= 'curl init error;';
            }
            if (curl_errno($ch) != 0) {
                $hint .= 'curl init error: '.curl_errno($ch);
            }
            if (!$curl_error) {
                @curl_setopt($ch, CURLOPT_URL, $url);
                @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                $response = @curl_exec($ch);
                if (curl_errno($ch) != 0) {
                    $hint .= 'curl error: '.curl_errno($ch);
                }
                curl_close($ch);
            }
        } else {
            $hint .= " PHP extension curl are not loaded;";
            if (!ini_get('allow_url_fopen')) {
                $hint .= " PHP ini option 'allow_url_fopen' are disabled;";
            } else {
                $response = file_get_contents($url);
            }
        }
        if (!$response && $hint) {
            self::log($this->id, __FUNCTION__.':'.$hint);
        }
        return $response;
    }
}
