<?php

/**
 *
 * @author WebAsyst Team
 * @name chasepaymentechPayment
 * @description CHASE Paymenttech payment module
 * @property-read string $test_mode
 * @property-read string $hosted_secure_id
 * @property-read string $merchantid
 * @property-read string $messagetype
 * @property-read string $tzcode
 * @property-read string $curriso
 * @property-read string $currexp
 * @property-read string $orderstatus
 * @property-read string $platform
 */

class chasepaymentechPayment extends waPayment implements waIPayment
{

    /**
     * @var double
     */
    protected $order_id;
    /**
     * @var array
     */
    protected $request;

    protected $allowed_credit_card_types = array(
        'American Express',
        'Diners Club',
        'Discover',
        'JCB',
        'MasterCard',
        'Visa'
    );

    /**
     * @return bool|string|string[]
     */
    public function allowedCurrency()
    {
        return true;
    }
    
    /**
     * @param array $payment_form_data POST form data
     * @param waOrder $order_data formalized order data
     * @param bool $auto_submit
     * @return string HTML payment form
     */
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);

        return $this->fetch(array(
            'url' => $this->getUrl(),
            'params' => $this->getRequestParams($order, true)
        ), '/templates/payment.html');
    }

    /**
     * @param array $significant_ids
     * @return string
     */
    protected function packSessionId(array $significant_ids)
    {
        return implode('_', $significant_ids);
    }

    /**
     * @param $session_id
     * @return array
     */
    protected function unpackSessionId($session_id)
    {
        return explode('_', $session_id);
    }

    protected function getRequestParams(waOrder $order, $str = false)
    {
        $params = array();
        $params['hostedSecureID'] = $this->hosted_secure_id;
        $params['action'] = 'buildForm';
        $params['sessionId'] = $this->packSessionId(array(
            $this->app_id,
            $this->merchant_id,
            $order->id,
            $order->contact_id,
            $order->currency
        ));
        $params['payment_type'] = 'Credit_Card';
        $params['formType'] = 1;
        $params['allowed_types'] = implode('|', $this->allowed_credit_card_types);
        $params['trans_type'] = 'auth_capture';
        $params['collectAddress'] = 2;
        $params['required'] = 'all';
        $params['orderId'] = $order->id;
        $params['currency_code'] = $order->currency;
        $params['address'] = $order->billing_address['address'];
        $params['city'] = $order->billing_address['city'];
        $params['state'] = $order->billing_address['region'];
        if ($order->billing_address['zip']) {
            $params['zip'] = $order->billing_address['zip'];
        }

        $name = $order->billing_address['name'];
        if (!$name) {
            $name = $order->contact_name;
        }
        if ($name) {
            $params['name'] = $name;
        }

        if ($str) {
            $result = array();
            foreach ($params as $k => $v) {
                $v = urlencode($v);
                $result[] = "$k=$v";
            }
            return implode('&', $result);
        }

        return $params;
    }

    /**
     * @param array $request
     * @return waPayment
     */
    public function callbackInit($request)
    {
        $this->request = $request;
        if (!empty($request['sessionId'])) {
            $unpack = $this->unpackSessionId($request['sessionId']);
            $this->app_id = $unpack[0];
            $this->merchant_id = $unpack[1];
            $this->order_id = $unpack[2];
        }
        return parent::callbackInit($request);
    }

    /**
     * @param array $request
     * @return array|string|void
     * @throws waPaymentException
     */
    public function callbackHandler($request)
    {
        $transaction_data = $this->formalizeData($request);

        if (!$this->order_id || !$this->app_id || !$this->merchant_id) {
            throw new waPaymentException('invalid invoice number');
        }

        if ($transaction_data['type'] == waPayment::OPERATION_AUTH_CAPTURE) {
            $app_payment_method = self::CALLBACK_CONFIRMATION;
        }

        $tm = new waTransactionModel();
        $fields = array(
            'native_id' => $transaction_data['native_id'],
            'plugin'    => $this->id,
            'type'      => $app_payment_method,
        );
        $result = '';
        if (!$tm->getByFields($fields)) {
            $transaction_data = $this->saveTransaction($transaction_data, $request);
            $result = $this->execAppCallback($app_payment_method, $transaction_data);
            self::addTransactionData($transaction_data['id'], $result);
        }

        return $result;
    }

    protected function formalizeData($transaction_raw_data)
    {
        $view_data = implode(' ', array(
            'Name: ' . $transaction_raw_data['name'],
            'Card: ' . $transaction_raw_data['cardType']. ' '. $transaction_raw_data['cardNumber'],
            'Transaction time: ' .
                $transaction_raw_data['transactionStart'] . ' - ' .
                $transaction_raw_data['transactionEnd'],
        ));

        if ($transaction_raw_data['status'] == '000') {
            $type  = waPayment::OPERATION_AUTH_CAPTURE;
            $state = waPayment::STATE_AUTH;
        } else {
            $type  = waPayment::OPERATION_CANCEL;
            $state = waPayment::STATE_CANCELED;
        }

        $unpack = $this->unpackSessionId($transaction_raw_data['sessionId']);
        list ($contact_id, $currency) = array_slice($unpack, 3);


        $transaction_data = parent::formalizeData($transaction_raw_data);
        $transaction_data =  array_merge($transaction_data, array(
            'type'        => $type,
            'native_id'   => ifset($transaction_raw_data['transId']),
            'amount'      => ifset($transaction_raw_data['amount']),
            'currency_id' => $currency,
            'customer_id' => $contact_id,
            'result'      => 1,
            'order_id'    => $this->order_id,
            'view_data'   => $view_data,
            'state'       => $state
        ));

        return $transaction_data;
    }

    protected function getUrl()
    {
        return $this->test_mode ?
            'https://www.chasepaymentechhostedpay-var.com/hpf/1_1/' :
            'https://www.chasepaymentechhostedpay.com/hpf/1_1/';
    }

    /**
     * @param $xml
     */
    protected static function dumpXml($xml)
    {
        $doc = new DomDocument('1.0', 'UTF-8');
        $doc->loadXML($xml);
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = true;
        echo $doc->saveXML();
    }

    /**
     * @param $assign
     * @param string $template
     * @return string
     */
    protected function fetch($assign, $template)
    {
        $view = wa()->getView();
        $assign['p'] = $this;
        $view->assign($assign);
        return $view->fetch($this->path . $template);
    }
}
