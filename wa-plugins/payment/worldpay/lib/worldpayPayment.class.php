<?php

/**
 *
 * @author Webasyst
 * @name WorldPay
 * @description WorldPay payment module
 * @property-read string $test_mode
 * @property-read string $tm_response_type
 * @property-read string $instid
 * @property-read string $md5_secret
 * @property-read string $response_password
 */

class worldpayPayment extends waPayment implements waIPayment
{
    /**
     * transaction status
     */
    const STATUS_SUCCESS = 'Y';

    /**
     * transaction status
     */
    const STATUS_CANCELLED = 'C';

    /**
     * @var numeric
     */
    protected $order_id;
    /**
     * @var array
     */
    protected $request;

    /**
     * @var string|array
     */
    protected $protected_parameters =
        'instId:cartId:currency:amount:M_app_id:M_merchant_id:M_order_id:M_contact_id';

    public function getSettingsHTML($params = array())
    {
        $html = parent::getSettingsHTML($params);
        $values = $this->getSettings();
        if (!empty($params['value'])) {
            $values = array_merge($values, $params['value']);
        }

        return $this->fetch(array(
            'namespace' => $this->getNamespace($params),
            'values' => $values
        ), '/templates/settings.html') . $html;
    }

    protected function getNamespace($params)
    {
        $namespace = '';
        if (!empty($params['namespace'])) {
            if (is_array($params['namespace'])) {
                $namespace = array_shift($params['namespace']);
                while (($namespace_chunk = array_shift($params['namespace'])) !== null) {
                    $namespace .= "[{$namespace_chunk}]";
                }
            } else {
                $namespace = $params['namespace'];
            }
        }
        return $namespace;
    }

    public function allowedCurrency()
    {
        return true;
    }

    /**
     * @param array $payment_form_data POST form data
     * @param waOrder $order_data formalized order data
     * @param $transaction_type
     * @return string HTML payment form
     */
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);

        return $this->fetch(array(
            'url' => $this->getUrl(),
            'fields' => $this->getFields($order),
            'auto_submit' => $auto_submit
        ), '/templates/payment.html');
    }

    protected function getFields(waOrder $order)
    {
        $fields = array();

        // mandatory fields
        $fields['instId']   = $this->instid;
        $fields['cartId']   = $order->id;
        $fields['currency'] = $order->currency;
        $fields['amount']   = number_format($order->total, 2, '.', '');;

        // optional fields
        $fields['desc']     = $order->description;
        $fields['country']  = $this->getCountryISO2Code($order->billing_address['country']);
        $fields['postcode'] = $order->billing_address['zip'];
        $fields['address1']  = $order->billing_address['street'];
        $fields['town'] = $order->billing_address['city'];

        $name = $order->billing_address['name'];
        if (!$name) {
            $name = $order->contact_name;
        }
        if ($name) {
            $fields['name'] = $name;
        }

        if ($tel = $order->contact_phone) {
            $fields['tel'] = $tel;
        }

        if ($email = $order->contact_email) {
            $fields['email'] = $email;
        }

        $fields['testMode'] = 0;
        if ($this->test_mode) {
            $fields['testMode'] = 100;
            if ($this->tm_response_type != 'AUTHORISED' || !$fields['name']) {
                $fields['name'] = $this->tm_response_type;
            }
        }

        // custom fields
        $fields['M_app_id'] = $this->app_id;
        $fields['M_merchant_id'] = $this->merchant_id;
        $fields['M_order_id'] = $order->id;
        $fields['M_contact_id'] = $order->contact_id;

        //signature
        if ($this->md5_secret) {
            $signature = array($this->md5_secret);
            foreach ($this->getProtectedParameters() as $name) {
                $signature[] = $fields[$name];
            }
            $fields['signature'] = md5(implode(':', $signature));
        }

        return $fields;
    }

    protected function callbackInit($request)
    {
        $this->request = $request;
        $this->app_id = $request['M_app_id'];
        $this->merchant_id = $request['M_merchant_id'];
        $this->order_id = $request['M_order_id'];
        return parent::callbackInit($request);
    }

    /**
     * @param array $request
     * @throws waPaymentException
     * @return array|string|void
     */
    protected function callbackHandler($request)
    {
        $transaction_data = $this->formalizeData($request);

        if (!$this->order_id || !$this->app_id || !$this->merchant_id) {
            throw new waPaymentException('invalid invoice number');
        }

        $response_password = !empty($request['callbackPW']) ? $request['callbackPW'] : '';

        $result = array(
            'p' => $this
        );

        if ($response_password != $this->response_password) {

            $result['rp_not_equal'] = true;
            $result['template'] = wa()->getConfig()->getRootPath().'/wa-plugins/payment/'.$this->id.'/templates/callback.html';
            $result['back_url'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);

            waLog::log("Payment Response password in plugin settings doesn't equal the same setting in the Merchant Interface\n".
                "Client IP:" . waRequest::getIp(),
                'worldpayPament.log'
            );

            return $result;
        }

        if ($transaction_data['type'] == waPayment::OPERATION_AUTH_CAPTURE) {
            $app_payment_method = self::CALLBACK_CONFIRMATION;
            $back_url = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
        } else {
            $app_payment_method = self::CALLBACK_CANCEL;
            $back_url = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);
        }

        $tm = new waTransactionModel();
        $fields = array(
            'native_id' => $transaction_data['native_id'],
            'plugin'    => $this->id,
            'type'      => $app_payment_method,
        );

        if (!$tm->getByFields($fields)) {
            $transaction_data = $this->saveTransaction($transaction_data, $request);
            $result += $this->execAppCallback($app_payment_method, $transaction_data);
        }

        $result['back_url'] = $back_url;
        $result['template'] = wa()->getConfig()->getRootPath().'/wa-plugins/payment/'.$this->id.'/templates/callback.html';

        return $result;
    }

    protected function getProtectedParameters()
    {
        if (!is_array($this->protected_parameters)) {
            $this->protected_parameters = explode(
                ':',
                $this->protected_parameters
            );
        }
        return $this->protected_parameters;
    }

    protected function getUrl()
    {
        if ($this->test_mode) {
            return 'https://secure-test.worldpay.com/wcc/purchase';
        } else {
            return 'https://secure.worldpay.com/wcc/purchase';
        }
    }

    /**
     * @param array $assign
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

    protected function formalizeData($transaction_raw_data)
    {
        $view_data = array();
        if ($transaction_raw_data['name']) {
            $view_data[] = 'Name: ' . $transaction_raw_data['name'];
        }
        if ($transaction_raw_data['tel']) {
            $view_data[] = 'Phone: ' . $transaction_raw_data['tel'];
        }
        if ($transaction_raw_data['email']) {
            $view_data[] = 'Email: ' . $transaction_raw_data['email'];
        }

        $view_data = implode(' ', $view_data);

        if ($transaction_raw_data['transStatus'] == self::STATUS_SUCCESS) {
            $type  = waPayment::OPERATION_AUTH_CAPTURE;
            $state = waPayment::STATE_AUTH;
        } else {
            $type  = waPayment::OPERATION_CANCEL;
            $state = waPayment::STATE_CANCELED;
        }

        $transaction_data = parent::formalizeData($transaction_raw_data);
        $transaction_data =  array_merge($transaction_data, array(
            'type'        => $type,
            'native_id'   => ifset($transaction_raw_data['transId']),
            'amount'      => ifset($transaction_raw_data['authAmount']),
            'currency_id' => ifset($transaction_raw_data['authCurrency']),
            'customer_id' => ifset($transaction_raw_data['M_contact_id']),
            'result'      => 1,
            'order_id'    => ifset($transaction_raw_data['cartId']),
            'view_data'   => $view_data,
            'state'       => $state
        ));

        return $transaction_data;
    }

    public function __get($name)
    {
        $value = $this->getSettings($name);
        if ($name == 'tm_response_type') {
            return $value ? $value : 'AUTHORISED';
        }
        return $value;
    }
}
