<?php

/**
 *
 * @property string $shop_id
 * @property string $shop_account
 * @property string $secret_key
 */
class rbkmoneyPayment extends waPayment implements waIPayment
{
    private $order_id;
    private $pattern = '/^(\w[\w\d]+)_([\w\d]+)_(.+)$/';
    private $template = '%s_%s_%s';

    public function allowedCurrency()
    {
        return array('UAH', 'RUB', 'RUR', 'USD', 'EUR');
    }

    /**
     * @see waIPayment::payment()
     */
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);

        $hidden_fields = array();

        $hidden_fields['eshopId'] = $this->shop_id;
        $hidden_fields['orderId'] = sprintf($this->template, $this->app_id, $this->merchant_id, $order->id);
        $hidden_fields['serviceName'] = mb_substr($order->description, 0, 255, "UTF-8");
        $hidden_fields['recipientAmount'] = number_format($order->total, 2, ',', '');

        $currency = $order->currency;
        if ($currency == 'RUB') {
            $currency = 'RUR';
        }

        $hidden_fields['recipientCurrency'] = $currency;
        $hidden_fields['user_email'] = $order->getContact()->get('email', 'default');
        $hidden_fields['language'] = substr($order->getContact()->getLocale(), 0, 2);

        $transaction_data = array(
            'order_id' => $order->id,
        );

        $hidden_fields['successUrl'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
        $hidden_fields['failUrl'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);

        $hidden_fields['hash'] = $this->getSign($hidden_fields);

        $view = wa()->getView();
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('hidden_fields', $hidden_fields);
        $view->assign('auto_submit', $auto_submit);
        return $view->fetch($this->path.'/templates/payment.html');
    }

    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data = parent::formalizeData($transaction_raw_data);

        $fields = array(
            'userName'  => 'Имя Пользователя в Системе RBK Money',
            'userEmail' => 'Email Пользователя в Системе RBK Money'
        );
        $view_data = array();
        foreach ($fields as $field => $description) {
            if (ifset($transaction_raw_data[$field])) {
                $view_data[] = $description.': '.$transaction_raw_data[$field];
            }
        }

        $transaction_data = array_merge($transaction_data, array(
            'type'        => null,
            'native_id'   => ifset($transaction_raw_data['paymentId']),
            'amount'      => ifset($transaction_raw_data['recipientAmount']),
            'currency_id' => ifset($transaction_raw_data['recipientCurrency']),
            'result'      => 1,
            'order_id'    => $this->order_id,
            'view_data'   => implode("\n", $view_data),
        ));

        switch (ifset($transaction_raw_data['paymentStatus'])) {
            case 3:
                $transaction_data['state'] = self::STATE_AUTH;
                $transaction_data['type'] = self::OPERATION_AUTH_ONLY;
                break;
            case 5:
                $transaction_data['state'] = self::STATE_CAPTURED;
                $transaction_data['type'] = self::OPERATION_CAPTURE;
                break;
        }
        return $transaction_data;
    }

    private function getEndpointUrl()
    {
        return 'https://rbkmoney.ru/acceptpurchase.aspx';
    }

    private function getSign(&$data)
    {
        $fields = array(
            'eshopId',
            'recipientAmount',
            'recipientCurrency',
            'user_email',
            'serviceName',
            'orderId',
            'userFields'
        );

        $hash = array();
        foreach ($fields as $field) {
            $hash[] = !empty($data[$field]) ? $data[$field] : '';
        }
        $hash[] = $this->secret_key;
        $hash = md5(implode('::', $hash));
        return $hash;
    }

    private function getRequestSign($data)
    {
        $fields = array(
            'eshopId',
            'orderId',
            'serviceName',
            'eshopAccount',
            'recipientAmount',
            'recipientCurrency',
            'paymentStatus',
            'userName',
            'userEmail',
            'paymentData'
        );
        $hash = array();
        foreach ($fields as $field) {
            $hash[] = !empty($data[$field]) ? $data[$field] : '';
        }
        $hash[] = $this->secret_key;
        $hash = md5(implode('::', $hash));
        return $hash;
    }

    protected function callbackInit($request)
    {
        if (preg_match($this->pattern, ifset($request['orderId']), $matches)) {
            $this->app_id = $matches[1];
            $this->merchant_id = $matches[2];
            $this->order_id = $matches[3];
        }
        return parent::callbackInit($request);
    }

    protected function callbackHandler($request)
    {
        $request_fields = array(
            'eshopId'           => '',
            'paymentId'         => 0,
            'orderId'           => '',
            'eshopAccount'      => '',
            'serviceName'       => '',
            'recipientAmount'   => 0.0,
            'recipientCurrency' => '', // USD, RUR, EUR, UAH
            'paymentStatus'     => 0, // 3|5
            'userName'          => '',
            'userEmail'         => '',
            'paymentData'       => '',
            'secretKey'         => '',
            'hash'              => ''
        );
        $request = array_merge($request_fields, $request);

        if (empty($request['eshopId']) || ($this->shop_id != $request['eshopId'])) {
            throw new waException('Invalid shop id');
        }
        if (empty($request['eshopAccount']) || ($this->shop_account != $request['eshopAccount'])) {
            throw new waException('Invalid shop account');
        }
        if (empty($request['hash']) || ($request['hash'] != $this->getRequestSign($request))) {
            throw new waException('Invalid request sign');
        }

        $transaction_data = $this->formalizeData($request);

        $callback_method = null;
        switch (ifset($transaction_data['state'])) {
            case self::STATE_CAPTURED:
                $callback_method = self::CALLBACK_PAYMENT;
                break;
        }

        if ($callback_method) {
            $transaction_data = $this->saveTransaction($transaction_data, $request);
            $this->execAppCallback($callback_method, $transaction_data);
        }
    }
}
