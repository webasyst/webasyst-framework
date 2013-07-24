<?php
/**
 *
 * @author WebAsyst Team
 * @name PayPal
 * @description PayPal Payments Standard Integration
 * @link https://cms.paypal.com/cms_content/US/en_US/files/developer/PP_WebsitePaymentsStandard_IntegrationGuide.pdf
 *
 * @property-read string $email
 * @property-read string $sandbox
 */
class paypalPayment extends waPayment implements waIPayment, waIPaymentCapture, waIPaymentRefund, waIPaymentCancel
{
    private $order_id;

    public function allowedCurrency()
    {
        return 'USD';
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        if ($order_data['currency_id'] != 'USD') {
            return array(
                'type' => 'error',
                'data' => _w('Order total amount needs to be converted to USD in order to make payment on PayPal'),
            );
        }
        if (empty($order_data['description_en'])) {
            $order_data['description_en'] = 'Order '.$order_data['order_id'];
        }
        $order_data['description_en'] = str_replace(array('“', '”', '«', '»'), '"', $order_data['description_en']);
        $hidden_fields = array(
            'cmd'           => '_xclick',
            'business'      => $this->email,
            'item_name'     => $order_data['description_en'],
            'item_number'   => $this->app_id.'_'.$this->merchant_id.'_'.$order_data['order_id'],
            'no_shipping'   => 1,
            'amount'        => number_format($order_data['amount'], 2, '.', ''),
            'currency_code' => $order_data['currency_id'],
            'return'        => '', //TODO
            'cancel_return' => '', //TODO
            'notify_url'    => $this->getRelayUrl(),
        );
        $view = wa()->getView();

        $view->assign('url', wa()->getRootUrl());
        $view->assign('hidden_fields', $hidden_fields);
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('auto_submit', $auto_submit);

        return $view->fetch($this->path.'/templates/payment.html');
    }

    /**
     * @see waIPaymentCapture::capture()
     */
    public function capture($transaction_raw_data)
    {

    }

    /**
     * @see waIPaymentRefund::refund()
     */
    public function refund($transaction_raw_data)
    {

    }

    /**
     *
     * @see waIPaymentRefund::refund()
     */
    public function cancel($transaction_raw_data)
    {

    }

    /**
     * @return string callback gateway url
     */
    protected function getEndpointUrl()
    {
        return $this->sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
    }

    protected function callbackInit($request)
    {
        if (isset($request['item_number']) && preg_match('/^(.+)_(.+)_(.+)$/', $request['item_number'], $match)) {
            $this->app_id = $match[1];
            $this->merchant_id = $match[2];
            $this->order_id = $match[3];
        } else {
            throw new waPaymentException('Invalid invoice number');
        }
        return parent::callbackInit($request);
    }

    /**
     * IPN (Instant Payment Notification)
     * @param $data - get from gateway
     * @return array
     */
    protected function callbackHandler($data)
    {
        $post_data = array_merge(array('cmd' => '_notify-validate'), $data);
        unset($post_data['result']);

        if (!$this->order_id) {
            throw new waPaymentException('Invalid invoice number');
        }

        if (!$this->email) {
            throw new waPaymentException('Empty merchant data');
        }

        $state = $this->sendData($post_data);
        
        if ($state == self::STATE_VERIFIED) {
            // accept transaction
            $transaction_data = $this->formalizeData($data);

            $tm = new waTransactionModel();
            $res = $tm->getByFields(array('native_id' => $transaction_data['native_id'], 'plugin' => $this->id));

            if (!$res) { // exclude transactions duplicates

                $transaction_data['order_id'] = $this->order_id;
                $transaction_data['plugin'] = $this->id;

                $supported_operations = $this->supportedOperations();

                // check transaction type
                if (!in_array($transaction_data['type'], $supported_operations)) {
                    throw new waPaymentException('Unsupported payment operation');
                }
                // check module email
                if (empty($data['receiver_email']) || !($this->email) || $this->email != $data['receiver_email']) {
                    throw new waPaymentException('Invalid receiver email: '.(!empty($data['receiver_email']) ? $data['receiver_email'] : ''));
                }
                // check transaction native id
                if ($this->getUniqTransaction($this->id, $this->app_id, $this->merchant_id, $transaction_data)) {
                    throw new waPaymentException('Duplicate transaction');
                }
                $transaction_data['state'] = self::STATE_CAPTURED;
                $transaction_data = $this->saveTransaction($transaction_data, $data);

                $result = $this->execAppCallback('payment', $transaction_data);

                self::addTransactionData($transaction_data['id'], $result);

                if (!empty($result['error'])) {
                    throw new waPaymentException('Forbidden (validate error): '.$result['error']);
                }
            }
            echo 'ok';
        } else {
            echo 'Transaction result: '.$state;
        }
        return array(
            'template' => false,
        );
    }

    /**
     * Convert transaction raw data to formatted data
     * @param array $data - transaction raw data
     * @return array $transaction_data
     */
    protected function formalizeData($data)
    {
        $fields = array(
            'txn_type',
            'ipn_track_id',
            'mc_gross',
            'mc_currency',
            'payment_status',
            'receiver_email',
        );
        foreach ($fields as $f) {
            if (!isset($data[$f])) {
                $data[$f] = null;
            }
        }
        $types = array('cart', 'express_checkout', 'masspay', 'send_money', 'recurring_payment', 'virtual_terminal', 'web_accept');

        $type = 'N/A';
        if (in_array($data['txn_type'], $types) && strtolower($data['payment_status']) == 'completed') {
            $type = self::OPERATION_AUTH_CAPTURE;
        }
        $view_data = '';
        if ($data['payer_email'])
            $view_data .= $data['payer_email'].', ';
        if ($data['first_name'] || $data['last_name'])
            $view_data .= trim($data['first_name'].' '.$data['last_name']).', ';
        if (!empty($data['address_street']) || !empty($data['address_city']) || !empty($data['address_state']) ||
            !empty($data['address_zip']) || !empty($data['address_country']))
            $view_data .= $data['address_street'].' '.$data['address_city'].' '.$data['address_state'].' '.$data['address_zip'].' '.$data['address_country'].', ';
        $view_data = substr($view_data, 0, -2);
        $view_data = preg_replace('/ +/', ' ', $view_data);

        $transaction_data = parent::formalizeData(null);
        $transaction_data['type'] = $type;
        $transaction_data['native_id'] = $data['txn_id'];
        $transaction_data['amount'] = $data['mc_gross'];
        $transaction_data['currency_id'] = $data['mc_currency'];
        $transaction_data['view_data'] = $view_data;

        return $transaction_data;
    }

    /**
     * Payment transport
     *
     * @return string state result
     */
    private function sendData($data)
    {
        $app_error = $response = null;
        /*
         if (extension_loaded('curl') || !function_exists('curl_init')) {
         throw new waException('PHP extension cURL not available');
         }
         */
        if (!($ch = curl_init())) {
            throw new waException('curl init error');
        }
        if (curl_errno($ch) != 0) {
            throw new waException('curl init error: '.curl_errno($ch));
        }

        @curl_setopt($ch, CURLOPT_URL, $this->getEndpointUrl());
        @curl_setopt($ch, CURLOPT_POST, 1);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        @curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        @curl_setopt($ch, CURLE_OPERATION_TIMEOUTED, 120);

        $response = @curl_exec($ch);
        if (curl_errno($ch) != 0) {
            $app_error = 'curl error: '.curl_errno($ch);
        }
        curl_close($ch);
        if ($app_error) {
            throw new waException($app_error);
        }
        if (empty($response)) {
            throw new waException('Empty server response');
        }
        return $response;
    }

    private function getUniqTransaction($transaction_data)
    {
        $transaction_model = new waTransactionModel();
        return $transaction_model->getByFields(array(
            'plugin' => $this->id,
            'app_id'   => $this->app_id,
            'merchant_id'      => $this->merchant_id,
            'native_id'        => $transaction_data['native_id']
        ));
    }

    public function supportedOperations()
    {
        return array(
            self::OPERATION_AUTH_CAPTURE,
        );
    }
}
