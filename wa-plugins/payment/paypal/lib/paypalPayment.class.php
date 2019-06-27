<?php

/**
 *
 * @author Webasyst
 * @name PayPal
 * @description PayPal Payments Standard Integration
 * @link https://cms.paypal.com/cms_content/US/en_US/files/developer/PP_WebsitePaymentsStandard_IntegrationGuide.pdf
 *
 * Plugin settings parameters must be specified in file lib/config/settings.php
 * @property-read string $email Merchant email
 * @property-read bool $sandbox Sandbox mode flag
 * @property-read bool[] $currency Array with currency codes as keys and their settings values
 */
class paypalPayment extends waPayment implements waIPayment
{
    /**
     * @var string
     */
    private $order_id;

    /**
     * Returns array of ISO3 codes of enabled currencies (from settings) supported by payment gateway.
     *
     * @return string[]
     */
    public function allowedCurrency()
    {
        return array_keys(array_filter($this->currency));
    }

    /**
     * Returns array of transaction operations supported by payment gateway.
     *
     * See available list of operation types as OPERATION_*** constants of waPayment.
     * @return array
     */
    public function supportedOperations()
    {
        return array(
            self::OPERATION_AUTH_CAPTURE,
        );
    }

    /**
     * Returns array or currency selection options for plugin settings.
     *
     * @return array
     * @see waPayment::settingCurrencySelect
     */
    public static function settingCurrencySelect()
    {
        $options = parent::settingCurrencySelect();
        /**
         * Currencies supported by PayPal
         * @see https://www.paypal.com/cgi-bin/webscr?cmd=p/sell/mc/mc_intro-outside
         */
        $allowed = array(
            'CAD', //Canadian Dollar
            'EUR', //Euro
            'GBP', //British Pound
            'USD', //U.S. Dollar
            'JPY', //Japanese Yen
            'AUD', //Australian Dollar
            'NZD', //New Zealand Dollar
            'CHF', //Swiss Franc
            'HKD', //Hong Kong Dollar
            'SGD', //Singapore Dollar
            'SEK', //Swedish Krona
            'DKK', //Danish Krone
            'PLN', //Polish Zloty
            'NOK', //Norwegian Krone
            'HUF', //Hungarian Forint
            'CZK', //Czech Koruna
            'ILS', //Israeli New Shekel
            'MXN', //Mexican Peso
            'BRL', //Brazilian Real (only for Brazilian members)
            'MYR', //Malaysian Ringgit (only for Malaysian members)
            'PHP', //Philippine Peso
            'TWD', //New Taiwan Dollar
            'THB', //Thai Baht
            'TRY', //Turkish Lira (only for Turkish members)
            'RUB', //Russian Ruble
        );

        /**
         * Filtering available currencies to leave only those supported by payment gateway
         */
        foreach ($options as $code => $option) {
            if (!in_array($code, $allowed)) {
                unset($options[$code]);
            }
        }
        return $options;

    }

    /**
     * Generates payment form HTML code.
     *
     * Payment form can be displayed during checkout or on order-viewing page.
     * Form "action" URL can be that of the payment gateway or of the current page (empty URL).
     * In the latter case, submitted data are passed again to this method for processing, if needed;
     * e.g., verification, saving, forwarding to payment gateway, etc.
     * @param array $payment_form_data Array of POST request data received from payment form
     * (if no "action" URL is specified for the form)
     * @param waOrder $order_data Object containing all available order-related information
     * @param bool $auto_submit Whether payment form data must be automatically submitted (useful during checkout)
     * @return string Payment form HTML
     * @throws waException
     */
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        // using order wrapper class to ensure use of correct data object
        $order = waOrder::factory($order_data);

        // verifying order currency support
        if (!in_array($order->currency, $this->allowedCurrency())) {
            throw new waException('Unsupported currency');
        }

        // adding all necessary form fields as required by PayPal
        $hidden_fields = array(
            'cmd'           => '_xclick',
            'business'      => $this->email,
            'item_name'     => str_replace(array('“', '”', '«', '»'), '"', $order->description),
            // packing order number with other auxiliary information for unique identification of current payment method
            'item_number'   => $this->app_id.'_'.$this->merchant_id.'_'.$order->id,
            'no_shipping'   => 1,
            'amount'        => number_format($order->total, 2, '.', ''),
            'currency_code' => $order->currency,
            // adding service URLs:

            // customer return URL to be used upon successful payment
            'return'        => $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $order),
            // return URL to be used when payment is cancelled
            'cancel_return' => $this->getAdapter()->getBackUrl(waAppPayment::URL_DECLINE),
            // URL to be used by payment gateway for sending notifications
            'notify_url'    => $this->getRelayUrl(),
            'charset'       => 'utf-8',
        );

        $view = wa()->getView();

        $view->assign(
            array(
                'url'           => wa()->getRootUrl(),
                'hidden_fields' => $hidden_fields,
                'form_url'      => $this->getEndpointUrl(),
                'auto_submit'   => $auto_submit,
                'plugin'        => $this,
            )
        );

        // using plugin's own template file to display payment form
        return $view->fetch($this->path.'/templates/payment.html');
    }

    /**
     * Plugin initialization for processing callbacks received from payment gateway.
     *
     * To process callback URLs of the form /payments.php/paypal/*,
     * corresponding app and id must be determined for correct initialization of plugin settings.
     * @param array $request Request data array ($_REQUEST)
     * @return waPayment
     * @throws waPaymentException
     */
    protected function callbackInit($request)
    {
        // parsing data to obtain order id as well as ids of corresponding app and plugin setup instance responsible
        // for callback processing
        if (isset($request['item_number']) && preg_match('/^(.+)_(.+)_(.+)$/', $request['item_number'], $match)) {
            $this->app_id = $match[1];
            $this->merchant_id = $match[2];
            $this->order_id = $match[3];
        } else {
            throw new waPaymentException('Invalid invoice number');
        }
        // calling parent's method to continue plugin initialization
        return parent::callbackInit($request);
    }

    /**
     * Actual processing of callbacks from payment gateway.
     *
     * Request parameters are checked and app's callback handler is called, if necessary.
     * Plugin settings are already initialized and available.
     * IPN (Instant Payment Notification)
     * @param array $request Request data array ($_REQUEST) received from gateway
     * @return array Associative array of optional callback processing result parameters:
     *     'redirect' => URL to redirect user upon callback processing
     *     'template' => path to template to be used for generation of HTML page displaying callback processing results;
     *                   false if direct output is used
     *                   if not specified, default template displaying message 'OK' is used
     *     'header'   => associative array of HTTP headers ('header name' => 'header value') to be sent to user's
     *                   browser upon callback processing, useful for cases when charset and/or content type are
     *                   different from UTF-8 and text/html
     *
     *     If a template is used, returned result is accessible in template source code via $result variable,
     *     and method's parameters via $params variable
     * @throws waPaymentException
     * @throws waException
     */
    protected function callbackHandler($request)
    {
        $this->validateRequest($request);

        // sending additional request to payment gateway to receive current transaction status
        $state = $this->notifyValidate($request);
        /**
         * IMPORTANT! If response to status request does not contain correct signature and there is no way to verify
         * payment, then order status must not be changed.
         * In this case app's callback handler for self::CALLBACK_NOTIFY status can be called to add a note to order
         * processing history.
         */

        // if response to additional request to payment gateway identifies transaction as paid, then continue processing
        if ($state == 'VERIFIED') {
            // accept transaction
            $transaction_data = $this->formalizeData($request);

            // checking transaction unique id
            $is_duplicate = $this->isDuplicate($transaction_data);

            if (!$is_duplicate) { // making sure there are no duplicates of this transactions
                $callback = null;
                $transaction_data['order_id'] = $this->order_id;
                $transaction_data['plugin'] = $this->id;

                $this->prepareData($request, $transaction_data,$callback);

                $transaction_data = $this->saveTransaction($transaction_data, $request);
                // calling app's payment handler method for paid order
                $result = $this->execAppCallback($callback, $transaction_data);
                if (!empty($result['error'])) {
                    throw new waPaymentException('Forbidden (validate error): '.$result['error']);
                }
                /**
                 * Because request for transaction status sent to payment gateway is initiated by the plugin,
                 * extra verification is not needed.
                 * In other cases, before calling a payment handler, you must ensure that a request has been
                 * received from a reliable source by verifying request checksum, signature, etc.
                 */
            }
            echo 'ok';
        } else {
            echo 'Transaction result: '.$state;
        }

        return array(
            'template' => false, // this plugin generates response without using a template
        );
    }

    /**
     * @param $request
     * @throws waPaymentException
     */
    protected function validateRequest($request)
    {
        // verifying that order id was received within callback
        if (!$this->order_id) {
            throw new waPaymentException('Invalid invoice number');
        }

        // verifying that plugin's essential settings values have been read and plugin has been correctly initialized
        if (!$this->email) {
            throw new waPaymentException('Empty merchant data');
        }

        // checking plugin settings 'email' field
        if (empty($request['receiver_email']) || !($this->email) || $this->email != $request['receiver_email']) {
            throw new waPaymentException('Invalid receiver email: '.ifempty($request['receiver_email']));
        }
    }

    /**
     * @param $transaction_data
     * @return bool
     */
    protected function isDuplicate($transaction_data)
    {
        $transaction_model = new waTransactionModel();

        $res = $transaction_model->getByFields(
            array(
                'plugin'      => $this->id,
                'app_id'      => $this->app_id,
                'merchant_id' => $this->merchant_id,
                'native_id'   => $transaction_data['native_id']
            )
        );

        return (bool)$res;
    }

    /**
     * Prepares information for statuses
     *
     * For all statuses except "paid" displays an error in the order log
     *
     * @param $request
     * @param $transaction_data
     * @param $callback
     */
    protected function prepareData($request, &$transaction_data, &$callback)
    {
        if (in_array($transaction_data['type'], $this->supportedOperations())) {
            $transaction_data['state'] = self::STATE_CAPTURED;
            $callback = self::CALLBACK_PAYMENT;
        } else {
            $transaction_data['view_data'] = (sprintf(_wp('Transaction type: %s'), ifset($request, 'payment_status', _wp('unknown'))));
            $callback = self::CALLBACK_NOTIFY;
        }
    }

    /**
     * Converts raw transaction data received from payment gateway to acceptable format.
     *
     * @param array $request Raw transaction data
     * @return array $transaction_data Formalized data
     */
    protected function formalizeData($request)
    {
        // obtaining basic request information
        $transaction_data = parent::formalizeData(null);

        // adding various data:
        // transaction id assigned by payment gateway
        $transaction_data['native_id'] = ifset($request['txn_id']);
        // amount
        $transaction_data['amount'] = ifset($request['mc_gross']);
        // currency code
        $transaction_data['currency_id'] = ifset($request['mc_currency']);

        $types = array(
            'cart',
            'express_checkout',
            'masspay',
            'send_money',
            'recurring_payment',
            'virtual_terminal',
            'web_accept',
        );

        $transaction_data['type'] = 'N/A';
        if (in_array(ifset($request['txn_type']), $types)
            && (strtolower(ifset($request['payment_status'])) == 'completed')
        ) {
            $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
        }

        $view_data = array();

        /**
         * data fields to be included in 'view_data' which are available in payment gateway's notification on
         * payment completion
         */
        $view_fields = array(
            'payer_email',
            'first_name',
            'last_name',
            'address_street',
            'address_city',
            'address_state',
            'address_zip',
            'address_country',
        );

        foreach ($view_fields as $field) {
            if (!empty($request[$field])) {
                $view_data[] = trim($request[$field]);
            }
        }

        /**
         * adding formalized data to auxiliary field 'view_data' to be displayed to administrator for reference and
         * general visual transaction control
         */

        if ($view_data = preg_replace('@\s+@', ' ', implode(', ', $view_data))) {
            $transaction_data['view_data'] = $view_data;
        }

        return $transaction_data;
    }

    /**
     * @return string Payment gateway's callback URL
     */
    private function getEndpointUrl()
    {
        return 'https://www.'.($this->sandbox ? 'sandbox.' : '').'paypal.com/cgi-bin/webscr';
    }

    /**
     * Requests current transaction status from payment gateway.
     *
     * @param array $data Transaction data
     * @return string Response received from payment gateway
     * @throws waException
     */
    private function notifyValidate($data)
    {
        $data = array_merge(array('cmd' => '_notify-validate'), $data);
        unset($data['result']);
        $app_error = $response = null;

        //check available PHP extension
        if (!extension_loaded('curl') || !function_exists('curl_init')) {
            throw new waException('PHP extension cURL not available');
        }

        //try to init cUrl
        if (!($ch = curl_init())) {
            throw new waException('curl init error');
        }


        if (curl_errno($ch) != 0) {
            throw new waException('curl init error: '.curl_errno($ch));
        }

        $url = $this->getEndpointUrl();

        $host = parse_url($url, PHP_URL_HOST);

        $headers = array(
            'Connection: close',
            'Host: '.$host,
        );

        @curl_setopt($ch, CURLOPT_URL, $url);
        @curl_setopt($ch, CURLOPT_POST, 1);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        @curl_setopt($ch, CURLOPT_USERAGENT, sprintf('Webasyst %s plugin (%s)', $this->id, $host));
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

}
