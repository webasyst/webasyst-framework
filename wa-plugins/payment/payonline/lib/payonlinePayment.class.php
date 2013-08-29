<?php
/**
 *
 * @property-read int $payonline_id
 * @property-read string $secret_key
 * @property-read array $currency
 * @property-read string $gateway
 * @property-read int $valid_until
 * @property-read string $customer_lang
 * @version 1.6
 * @link http://www.payonlinesystem.ru/
 */
class payonlinePayment extends waPayment implements waIPayment, waIPaymentRefund
{

    private $url = 'https://secure.payonlinesystem.com/%s/payment/%s';
    private $order_id;

    public function allowedCurrency()
    {
        $default = array(
            'RUB',
            'USD',
            'EUR',
        );
        return $this->currency ? array_intersect($default, array_keys($this->currency)) : $default;
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $allowed = (array) $this->allowedCurrency();
        if (!in_array($order_data['currency_id'], $allowed)) {
            return array(
                'type' => 'error',
                'data' => _w(''),
            );
        }

        $form_fields = array(
            'MerchantId' => $this->payonline_id,
            'OrderId'    => $this->app_id.'_'.$order_data['order_id'],
            'Amount'     => number_format($order_data['amount'], 2, '.', ''),
            'Currency'   => $order_data['currency_id'],
        );
        if ($this->valid_until) {
            $order_time = empty($order_data['order_time']) ? time() : strtotime($order_data['order_time']);
            $form_fields['ValidUntil'] = date('Y-m-d H:i:s', $order_time + $this->valid_until * 3600);
        }

        $hash = '';
        foreach ($form_fields as $field => $value) {
            $hash .= "{$field}={$value}&";
        }

        $form_fields['SecurityKey'] = md5($hash.'PrivateSecurityKey='.$this->secret_key);
        $form_fields['ReturnURL'] = $this->getRelayUrl().'?app_id='.$this->app_id;
        $form_fields['FailURL'] = $this->getRelayUrl().'?transaction_result=failure&app_id='.$this->app_id;
        $form_fields['wa_merchant_id'] = $this->merchant_id;

        $view = wa()->getView();

        $view->assign('form_fields', $form_fields);
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('auto_submit', $auto_submit);

        return $view->fetch($this->path.'/templates/payment.html');

    }

    public function callbackInit($request)
    {
        $pattern = '/^([a-z]+)_(.+)$/';
        if (!empty($request['OrderId']) && preg_match($pattern, $request['OrderId'], $match)) {
            $this->app_id = $match[1];
            $this->merchant_id = ifempty($request['wa_merchant_id'], '');
            $this->order_id = $match[2];
        } elseif (!empty($request['app_id'])) {
            $this->app_id = $request['app_id'];
        }
        return parent::callbackInit($request);
    }

    public function callbackHandler($request)
    {
        $transaction_data = $this->formalizeData($request);
        $transaction_result = ifempty($request['transaction_result'], 'success');
        $post = waRequest::post();
        if (empty($post)) {
            if (!empty($request['app_id'])) {
                return array(
                    'redirect' => $this->getAdapter()->getBackUrl($transaction_result == 'success' ? waAppPayment::URL_SUCCESS : waAppPayment::URL_FAIL, $transaction_data),
                );
            }
        }
        $this->verifySign($request);
        $message = null;
        switch (ifempty($request['ErrorCode'])) {
            case 1:
                $message = 'Возникла техническая ошибка, попробуйте повторить попытку оплаты спустя некоторое время.';
                $transaction_result = 'failure';
                $transaction_data['state'] = self::STATE_DECLINED;
                break;
            case 2:
                $message = 'Оплата банковской картой недоступна. Попробуйте воспользоваться другим способом оплаты.';
                $transaction_result = 'failure';
                $transaction_data['state'] = self::STATE_DECLINED;
                break;
            case 3:
                $message = 'Платеж отклонен банком-эмитентом карты. Обратитесь в банк, выясните причину отказа и повторите попытку оплаты.';
                $transaction_result = 'failure';
                $transaction_data['state'] = self::STATE_DECLINED;
                break;
            default:
                break;
        }

        $url = null;

        if (!empty($post)) {
            $app_payment_method = null;
            switch ($transaction_result) {
                case 'success':
                    $app_payment_method = self::CALLBACK_PAYMENT;
                    $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
                    break;
                case 'failure':
                default:
                    $app_payment_method = self::CALLBACK_DECLINE;
                    $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);
                    break;
            }

            $transaction_data = $this->saveTransaction($transaction_data, $request);
            if ($app_payment_method) {
                $result = $this->execAppCallback($app_payment_method, $transaction_data);
                self::addTransactionData($transaction_data['id'], $result);
            }
        }

        return array(
            'template' => $this->path.'/templates/callback.html',
            'back_url' => $url,
            'message'  => $message,
        );
    }

    /**
     * @todo
     * (non-PHPdoc)
     * @see waIPaymentRefund::refund()
     */
    public function refund($transaction_raw_data)
    {
        //MerchantId={MerchantId}&TransactionId={TransactionId}&Amount={Amount}&PrivateSecurityKey={PrivateSecurityKey}
        ;
    }

    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data = parent::formalizeData($transaction_raw_data);
        $transaction_data['native_id'] = ifempty($transaction_raw_data['TransactionID']);

        $transaction_data['order_id'] = $this->order_id;
        $transaction_data['amount'] = $transaction_raw_data['Amount'];
        $transaction_data['currency_id'] = $transaction_raw_data['PaymentCurrency'];

        $details = '';
        $fields = array();
        $fields['TransactionID'] = 'Уникальный идентификатор транзакции или счета QIWI/WebMoney/Яндекс.Деньги';
        switch ($provider = ifempty($transaction_raw_data['Provider'])) {
            case 'Card':
                $fields['CardHolder'] = 'Имя держателя карты';
                $fields['CardNumber'] = 'Номер карты';
                $fields['Country'] = 'Страна';
                $fields['BinCountry'] = 'Код страны, определенный по BIN эмитента карты';
                $fields['City'] = 'Город';
                $fields['Address'] = 'Адрес';
                break;
            case 'Qiwi':
                $fields['Phone'] = 'Номер телефона';
                break;
            case 'WebMoney':

                $fields['WmTranId'] = 'Служебный номер счета в системе учета WebMoney';
                $fields['WmInvId'] = 'Уникальный номер счета в системе учета WebMoney';
                $fields['WmId'] = 'WMID плательщика';
                $fields['WmPurse'] = 'WM-кошелек плательщика';
                break;
            default:
                $details .= "Unknown payment provider {$provider}";
                break;

        }

        $fields['IpAddress'] = 'IP-адрес';
        $fields['IpCountry'] = 'Код страны, определенный по IP-адресу';
        foreach ($fields as $field => $description) {
            if (!empty($transaction_raw_data[$field])) {
                $details .= "\n{$description}: {$transaction_raw_data[$field]}";
            }
        }

        $transaction_data['view_data'] = $details;

        return $transaction_data;
    }

    private function verifySign($request)
    {
        if ($this->secret_key) {
            $fields = array(
                'DateTime',
                'TransactionID',
                'OrderId',
                'Amount',
                'Currency',
            );
            $string = '';
            foreach ($fields as $field) {
                $string .= $field.'='.ifempty($request[$field]).'&';
            }

            $signature = strtolower(md5($string.'PrivateSecurityKey='.$this->secret_key));
            $server_signature = strtolower(ifempty($request['SecurityKey']));
            if (!$server_signature || ($server_signature != $signature)) {
                throw new waPaymentException('invalid post data sign');
            }
        }
    }

    private function getEndpointUrl()
    {
        return sprintf($this->url, 'ru', $this->gateway);
    }
}
