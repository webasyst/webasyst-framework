<?php

/**
 *
 * @author WebAsyst Team
 * @name SagePay
 * @description SagePay payment module
 * @property-read string $test_mode
 * @property-read string $vendor_name
 * @property-read string $crypt_password
 * @property-read string $currency
 */

class sagepayPayment extends waPayment implements waIPayment
{
    protected $version = '3.00';
    protected $order_id;
    protected $request;

    public function allowedCurrency()
    {
        return $this->currency;
    }

    public function getSettingsHTML($params = array())
    {
        $currencies = waCurrency::getAll();
        foreach ($currencies as $k => $v) {
            $currencies[$k] = $v . ' (' . $k . ')';
        }
        $params['options']['currency'] = $currencies;
        return parent::getSettingsHTML($params);
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

    /**
     * @return string
     */
    protected function getUrl()
    {
        if ($this->test_mode) {
            return 'https://test.sagepay.com/gateway/service/vspform-register.vsp';
        } else {
            return 'https://live.sagepay.com/gateway/service/vspform-register.vsp';
        }
    }

    /**
     * @param array $significant_ids
     * @return string
     */
    protected function packTransactionCode(array $significant_ids)
    {
        return implode('_', $significant_ids);
    }

    /**
     * @param string $significant_ids_string
     * @return array
     */
    protected function unpackTransactionCode($significant_ids_string)
    {
        return explode('_', $significant_ids_string);
    }

    /**
     * @param waOrder $order
     * @return array
     */
    protected function getFields(waOrder $order)
    {
        $fields = array();

        $fields['VPSProtocol'] = $this->version;
        $fields['TxType'] = 'PAYMENT';
        $fields['Vendor'] = $this->vendor_name;

        $crypt_fields = array();

        $transaction_code = $this->packTransactionCode(array(
            $this->app_id,
            $this->merchant_id,
            $order->id,
            $order->contact_id,
            $order->currency
        ));
        $crypt_fields['VendorTxCode'] = $transaction_code;

        $crypt_fields['Amount'] = number_format($order->total, 2);
        $crypt_fields['Currency'] = $order->currency;
        $crypt_fields['Description'] = $order->description;
        $crypt_fields['SuccessURL'] = $this->getRelayUrl().'?result=success&tx_code=' . $transaction_code;
        $crypt_fields['FailureURL'] = $this->getRelayUrl().'?result=fail&tx_code=' . $transaction_code;

        $billing_address = $this->getBillingAddress($order);

        // for include in email message
        $crypt_fields['CustomerName'] = $billing_address['name'];

        if ($email = $order->contact_email) {
            $crypt_fields['CustomerEMail'] = $email;
        }

        $phone = $order->contact_phone;

        $crypt_fields['BillingFirstnames'] = $billing_address['firstname'];
        $crypt_fields['BillingSurname']    = $billing_address['lastname'];
        $crypt_fields['BillingAddress1']   = $billing_address['street'];
        $crypt_fields['BillingCity']       = $billing_address['city'];
        $crypt_fields['BillingPostCode']   = $billing_address['zip'];
        $crypt_fields['BillingCountry']    = $this->getCountryISO2Code($billing_address['country']);

        if ($phone) {
            $crypt_fields['BillingPhone'] = $phone;
        }

        $shipping_address = $this->getShippingAddress($order);

        $crypt_fields['DeliverySurname']    = $shipping_address['firstname'];
        $crypt_fields['DeliveryFirstnames'] = $shipping_address['lastname'];
        $crypt_fields['DeliveryAddress1']   = $shipping_address['street'];
        $crypt_fields['DeliveryCity']       = $shipping_address['city'];
        $crypt_fields['DeliveryCountry']    = $this->getCountryISO2Code($shipping_address['country']);
        $crypt_fields['DeliveryPostCode']   = $shipping_address['zip'];
        if ($shipping_address['country'] == 'usa') {
            $crypt_fields['DeliveryState'] = $shipping_address['region'];
        }

        if ($phone) {
            $crypt_fields['DeliveryPhone'] = $phone;
        }

        $fields['Crypt'] = $this->cryptFields($crypt_fields, $this->crypt_password);

        return $fields;
    }

    /**
     * @param waOrder $order
     * @return array
     */
    protected function getBillingAddress(waOrder $order)
    {
        $billing_address = $order->billing_address;

        $billing_address['firstname'] = !empty($billing_address['firstname']) ?
            $billing_address['firstname'] : $order->contact_firstname;
        $billing_address['lastname']  = !empty($billing_address['lastname']) ?
            $billing_address['lastname']  : $order->contact_lastname;

        $billing_address['name'] = !empty($billing_address['name']) ?
            $billing_address['name'] : $order->contact_name;

        return $billing_address;
    }

    /**
     * @param waOrder $order
     * @return array
     */
    protected function getShippingAddress(waOrder $order)
    {
        $shipping_address = $order->shipping_address;

        $shipping_address['firstname'] = !empty($shipping_address['firstname']) ?
            $shipping_address['firstname'] : $order->contact_firstname;
        $shipping_address['lastname']  = !empty($shipping_address['lastname']) ?
            $shipping_address['lastname'] : $order->contact_lastname;

        $shipping_address['name'] = !empty($shipping_address['name']) ?
            $shipping_address['name'] : $order->contact_name;

        return $shipping_address;
    }

    /**
     * @param array $request
     * @return waPayment
     */
    public function callbackInit($request)
    {
        $this->request = $request;

        if (!empty($request['tx_code'])) {
            $unpack = $this->unpackTransactionCode($request['tx_code']);
            $this->app_id = $unpack[0];
            $this->merchant_id = $unpack[1];
            $this->order_id = $unpack[2];
        }

        return parent::callbackInit($request);
    }

    /**
     * @param $fields
     * @param $password
     * @return string
     */
    protected function cryptFields($fields, $password)
    {
        $fields_str = '';
        foreach ($fields as $k => $v) {
            $fields_str .= "&$k=$v";
        }
        $fields_str = substr($fields_str, 1);
        return $this->encrypt($fields_str, $password);
    }

    /**
     * @param $fields
     * @param $password
     * @return array
     */
    protected function encryptFields($fields, $password)
    {
        $crypt_str = $this->decrypt($fields, $password);

        $encrypt = array();
        foreach (explode('&', $crypt_str) as $token) {
            list($k, $v) = explode('=', $token);
            $encrypt[$k] = $v;
        }

        return $encrypt;
    }

    protected function callbackHandler($request)
    {
        $encrypt = $this->encryptFields($request['crypt'], $this->crypt_password);
        $request += $encrypt;
        $this->request = $request;

        $transaction_data = $this->formalizeData($this->request);
        if (!$this->order_id || !$this->app_id || !$this->merchant_id) {
            throw new waPaymentException('invalid invoice number');
        }

        $result = array(
            'p' => $this
        );

        if ($transaction_data['type'] == waPayment::OPERATION_AUTH_CAPTURE) {
            $app_payment_method = self::CALLBACK_CONFIRMATION;
            $back_url = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
        } else {
            $app_payment_method = self::CALLBACK_CANCEL;
            $back_url = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);
        }

        if ($transaction_data['type']) {
            $tm = new waTransactionModel();
            $fields = array(
                'native_id' => $transaction_data['native_id'],
                'plugin'    => $this->id,
                'type'      => $app_payment_method,
            );

            if (!$tm->getByFields($fields)) {
                $transaction_data = $this->saveTransaction($transaction_data, $this->request);
                if ($app_payment_method) {
                    $result += $this->execAppCallback($app_payment_method, $transaction_data);
                    self::addTransactionData($transaction_data['id'], $result);
                }
            }
        }

        $result['back_url'] = $back_url;
        $result['template'] = wa()->getConfig()->getRootPath().'/wa-plugins/payment/'.$this->id.'/templates/callback.html';

        return $result;
    }

    protected function formalizeData($transaction_raw_data)
    {
        $unpack = $this->unpackTransactionCode($transaction_raw_data['VendorTxCode']);
        list ($contact_id, $currency) = array_slice($unpack, 3);
        $contact = new waContact($contact_id);

        $view_data = implode(' ', array(
            'Name: ' . $contact->getName(),
            'Phone: '. $contact->get('phone', 'default'),
            'Email: '. $contact->get('email', 'default')
        ));

        $status = $transaction_raw_data['Status'];

        if ($status == 'OK') {
            $type  = waPayment::OPERATION_AUTH_CAPTURE;
            $state = waPayment::STATE_AUTH;
        } else {
            $type = waPayment::OPERATION_CANCEL;
            $state = waPayment::STATE_CANCELED;
        }

        $transaction_data = parent::formalizeData($transaction_raw_data);
        $transaction_data =  array_merge($transaction_data, array(
            'type'        => $type,
            'native_id'   => ifset($transaction_raw_data['VPSTxId']),
            'amount'      => ifset($transaction_raw_data['Amount']),
            'currency_id' => $currency,
            'customer_id' => $contact_id,
            'result'      => 1,
            'order_id'    => $this->order_id,
            'view_data'   => $view_data,
            'state'       => $state
        ));

        return $transaction_data;
    }

    /**
     * @param $input
     * @param $password
     * @return string
     */
    protected function encrypt($input, $password)
    {
        $iv = $password;
        return '@' . bin2hex(mcrypt_encrypt(
            MCRYPT_RIJNDAEL_128,
            $password,
            $this->addPKCS5Padding($input),
            MCRYPT_MODE_CBC,
            $iv
        ));
    }

    /**
     * @param $input
     * @param $password
     * @return string
     */
    protected function decrypt($input, $password)
    {
        $iv = $password;
        $input = substr($input, 1);
        $input = pack('H*', $input);
        return $this->removePKCS5Padding(
            mcrypt_decrypt(
                MCRYPT_RIJNDAEL_128,
                $password,
                $input,
                MCRYPT_MODE_CBC,
                $iv
            )
        );
    }

    /**
     * PHP's mcrypt does not have built in PKCS5 Padding, so we use this
     *
     * @param $input
     * @return string
     */
    protected function addPKCS5Padding($input)
    {
        $block_size = 16;
        $padding = "";

        // Pad input to an even block size boundary
        $pad_length = $block_size - (strlen($input) % $block_size);
        for($i = 1; $i <= $pad_length; $i++) {
            $padding .= chr($pad_length);
        }

        return $input . $padding;
    }

    /**
     *
     * Need to remove padding bytes from end of decoded string
     *
     * @param $input
     * @return string
     */
    function removePKCS5Padding($input) {
        $char = ord($input[strlen($input) - 1]);
        return substr($input, 0, -$char);
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
}
