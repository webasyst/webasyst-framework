<?php

/**
 * Class sbPayment
 *
 * All amounts must be in the minimum currency units
 * @property-read $userName
 * @property-read $TESTMODE
 * @property-read $currency_id
 * @property-read $sessionTimeoutSecs
 * @property-read $password
 * @property-read $two_step
 * @property-read $tax_system
 * @property-read $fiscalization
 * @property-read $cancel  Need to activate in Sberbank
 */
class sbPayment extends waPayment implements waIPaymentCapture, waIPaymentCancel
{
    /**
     * The parent transaction in the order
     * @array
     */
    protected $parent_transaction;

    /**
     * The last transaction in the order
     * @array
     */
    protected $last_transaction;

    /**
     * The first transaction with Check status. Get OrderID
     * @array
     */
    protected $last_transaction_check = null;

    /**
     * Get all transactions with Check status.
     * @array
     */
    protected $count_transactions_check;

    /**
     * @int
     */
    protected $order_id;

    /**
     * Transfer of all payments into rubles.
     * @return string
     */
    public function allowedCurrency()
    {
        return 'RUB';
    }

    /**
     * @param array $payment_form_data
     * @param waOrder $order_data
     * @param bool $auto_submit
     * @return string
     * @throws waException
     * @throws waPaymentException
     */
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        if ($auto_submit && $this->TESTMODE) {
            $auto_submit = false;
            self::log($this->id, var_export($order_data, true));
        }
        $this->getTransactionsForOrder($order_data['id']);
        $status = $this->getGatewayOrderStatus();

        //If settings error
        if ($status && $status['orderStatus'] == 3 && $status['orderStatus'] == 6) {
            self::log($this->id, $status['errorCode'].': '.$status['errorMessage']);
            throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
        }

        if ($status && ifset($status, 'orderStatus', null) == 0) {
            // It's an existing order
            $redirect_url = $this->last_transaction_check['raw_data']['formUrl'];
        } elseif ($status && ifset($status, 'orderStatus', null) == 1) {
            return 'Деньги заблокированы.';
        } else {
            // It's a new order, we need to create new transaction
            $registered_order = $this->registerOrder($order_data);
            $redirect_url = $registered_order['formUrl'];
        }

        // Form HTML
        return $this->viewForm($redirect_url, $auto_submit);

    }

    /**
     * Return HTML form
     * @param $url
     * @param $auto_submit
     * @return string
     */
    protected function viewForm($url, $auto_submit)
    {
        //Explode the url to insert fields into the "Get" form
        $explode_url = explode('?', $url, 2);
        parse_str(ifset($explode_url[1]), $url_params_array);

        $view = wa()->getView();

        $view->assign(array(
            'form_url'         => $explode_url[0],
            'url_params_array' => $url_params_array,
            'auto_submit'      => $auto_submit,
        ));
        return $view->fetch($this->path.'/templates/payment.html');
    }

    /**
     * @param array $request
     * @throws waException
     * @return void
     */
    public function callbackHandler($request)
    {
        $this->getTransactionsForOrder($this->order_id);
        $order_status = $this->getGatewayOrderStatus();
        $transaction_data = $this->formalizeData($order_status);
        $transaction_raw_data = $this->formalizeRawData($order_status);
        $app_payment_method = null;

        switch ($order_status['orderStatus']) {
            //Amount Hold
            case 1:
                $app_payment_method = self::CALLBACK_CAPTURE;
                $transaction_data += array(
                    'result' => 1,
                    'type'   => self::OPERATION_AUTH_ONLY,
                    'state'  => self::STATE_AUTH,
                );

                //Shop App Solution
                if ($this->app_id == 'shop') {
                    $app_payment_method = self::CALLBACK_NOTIFY;
                    $transaction_data['view_data'] = 'Деньги заблокированы';
                };

                break;

            //Order paid
            case 2:
                $app_payment_method = self::CALLBACK_PAYMENT;
                $transaction_data += array(
                    'result' => 1,
                    'type'   => self::OPERATION_AUTH_CAPTURE,
                    'state'  => self::STATE_CAPTURED,
                );

                break;

            //Cancel
            case 3:
                $app_payment_method = self::CALLBACK_CANCEL;
                $transaction_data += array(
                    'result'       => 1,
                    'type'         => self::OPERATION_CANCEL,
                    'state'        => self::STATE_CANCELED,
                    'parent_id'    => $this->last_transaction['parent_id'] ? $this->last_transaction['parent_id'] : $this->last_transaction['id'],
                    'parent_state' => self::STATE_CANCELED,
                );

                break;

            //Refund
            case 4:
                $app_payment_method = self::CALLBACK_REFUND;
                $transaction_data += array(
                    'result'       => 1,
                    'type'         => self::OPERATION_REFUND,
                    'state'        => self::STATE_REFUNDED,
                    'parent_id'    => $this->last_transaction['parent_id'] ? $this->last_transaction['parent_id'] : $this->last_transaction['id'],
                    'parent_state' => self::STATE_REFUNDED,
                );

                break;
        }

        if ($app_payment_method && $transaction_data && !$this->compareLastTransactionType($transaction_data['type'])) {
            $transaction_data = $this->compareTransactionData($transaction_data, $order_status);
            $transaction_data = $this->saveTransaction($transaction_data, $transaction_raw_data);
            $this->execAppCallback($app_payment_method, $transaction_data);
        }

        //Redirect user
        if ($order_status['errorCode'] != '0') {
            wa()->getResponse()->redirect($this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL));
        } else {
            wa()->getResponse()->redirect($this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS));
        };
    }

    /**
     * @param $data
     * @return array|bool|null
     * @throws waPaymentException
     * @throws waException
     */
    public function cancel($data)
    {
        //Need to activate "CANCEL" in Sberbank
        if (!$this->cancel) {
            return null;
        }

        $net = new waNet(array(
            'request_format' => 'default',
            'format'         => waNet::FORMAT_JSON,
            'verify'         => false,
        ));

        $request_cancel = array(
            'userName' => $this->userName,
            'password' => $this->password,
            'orderId'  => $data['transaction']['native_id'],
            'language' => substr(wa()->getLocale(), 0, 2),
        );

        $response = $net->query($this->getURL('URL_PAYMENT_CANCEL'), $request_cancel, waNet::METHOD_POST);

        if ($response['errorCode'] != '0') {
            self::log($this->id, $response['errorCode'].': '.$response['errorMessage']);
            throw new waPaymentException($response['errorMessage']);
        }

        $transaction = array(
            'native_id'    => $data['transaction']['native_id'],
            'type'         => self::OPERATION_CANCEL,
            'result'       => 1,
            'order_id'     => $data['transaction']['order_id'],
            'customer_id'  => $data['transaction']['customer_id'],
            'amount'       => $data['transaction']['amount'],
            'currency_id'  => $data['transaction']['currency_id'],
            'parent_id'    => $data['transaction']['id'],
            'parent_state' => self::STATE_CANCELED,
            'state'        => self::STATE_CANCELED,
        );

        $save = $this->saveTransaction($transaction);

        if ($save) {
            return array('result' => 0, 'description' => '');
        }

        return false;
    }

    /**
     * @param $data
     * @return array|bool
     * @throws waPaymentException
     * @throws waException
     */
    public function capture($data)
    {
        $net = new waNet(array(
            'request_format' => 'default',
            'format'         => waNet::FORMAT_JSON,
            'verify'         => false,
        ));

        $request_completion = array(
            'userName' => $this->userName,
            'password' => $this->password,
            'orderId'  => $data['transaction']['native_id'],
            'amount'   => round($data['transaction']['amount'] * 100), //convert to cent
        );

        $response = $net->query($this->getURL('URL_PAYMENT_COMPLETE'), $request_completion, waNet::METHOD_POST);

        if ($response['errorCode']) {
            self::log($this->id, $response['errorCode'].': '.$response['errorMessage']);
            throw new waPaymentException($response['errorMessage']);
        }

        $transaction = array(
            'native_id'    => $data['transaction']['native_id'],
            'type'         => self::OPERATION_CAPTURE,
            'result'       => 1,
            'order_id'     => $data['transaction']['order_id'],
            'customer_id'  => $data['transaction']['customer_id'],
            'amount'       => $data['transaction']['amount'],
            'currency_id'  => $data['transaction']['currency_id'],
            'parent_id'    => $data['transaction']['id'],
            'parent_state' => self::STATE_CAPTURED,
            'state'        => self::STATE_CAPTURED,
        );

        $save = $this->saveTransaction($transaction);

        if ($save) {
            return array('result' => 0, 'description' => '');
        }

        return false;
    }

    /**
     * @param $response
     * @param $order_data
     * @return bool
     * @throws waPaymentException
     */
    protected function saveCheckTransaction($response, $order_data)
    {
        $transaction_data = array(
            'native_id'   => $response['orderId'],
            'type'        => 'CHECK',
            'order_id'    => $order_data['id'],
            'customer_id' => $order_data['contact_id'],
            'result'      => 1,
            'view_data'   => $this->TESTMODE ? 'Оплата в тестовом режиме'."\n" : null,
            'amount'      => $order_data['total'],
            'currency_id' => $order_data['currency'],
        );
        $transaction_raw_data = array('formUrl' => $response['formUrl']);

        if (!$this->saveTransaction($transaction_data, $transaction_raw_data)) {
            throw new waPaymentException('Ошибка сохранения транзакции');
        }
        return true;
    }

    /**
     * @param $order_data
     * @return array|mixed|SimpleXMLElement|string
     * @throws waException
     * @throws waPaymentException
     */
    protected function registerOrder($order_data)
    {
        $url = $this->getURL('URL_ORDER_REGISTER');

        $net = new waNet(array(
            'request_format' => 'default',
            'format'         => waNet::FORMAT_JSON,
            'verify'         => false,
        ));

        $order_number = $this->app_id.'_'.$this->merchant_id.'_'.$order_data['id'];
        if ($this->count_transactions_check > 0) {
            $order_number .= '_'.$this->count_transactions_check;
        }

        $return_url = $this->getRelayUrl().'?orderNumber='.$order_number;
        $register_fields = array(
            'userName'           => $this->userName,
            'password'           => $this->password,
            'orderNumber'        => $order_number,
            'amount'             => round($order_data['total'] * 100), //convert to cent
            'currency'           => $this->getCurrencyISO4217Code($order_data['currency']),
            'returnUrl'          => $return_url,
            'failUrl'            => $return_url,
            'description'        => $order_data['data']['datetime'],
            'language'           => substr(wa()->getLocale(), 0, 2),
            'clientId'           => $order_data['contact_id'],
            'sessionTimeoutSecs' => ($this->sessionTimeoutSecs ? $this->sessionTimeoutSecs : 24) * 60 * 60, //convert hours to sec
        );

        if ($this->two_step) {
            $url = $this->getURL('URL_ORDER_PRE_REGISTER');

        }
        if ($this->fiscalization) {
            $register_fields['orderBundle'] = $this->getInfoForFiscalization($order_data);
            $register_fields['taxSystem'] = $this->tax_system;
        }

        $response = $net->query($url, $register_fields, waNet::METHOD_POST);
        $response = $this->validateRegisterResponse($response);
        $this->saveCheckTransaction($response, $order_data);

        return $response;
    }

    /**
     * @param $order_data
     * @return string
     * @throws waPaymentException
     * @throws waException
     */
    protected function getInfoForFiscalization($order_data)
    {
        $contact = new waContact($order_data['contact_id']);
        $email = $contact->get('email', 'default');
        $phone = $contact->get('phone', 'default');

        if (!$email && !$phone) {
            $mail = new waMail();
            $email = $mail->getDefaultFrom();
            $email = key($email);
            if (!$email) {
                self::log($this->id, 'Не установлен системный Email.');
                throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
            }

        }

        $order_bundle = array(
            'orderCreationDate' => time() * 1000,
            'customerDetails'   => array(
                'email'   => $email,
                'phone'   => $phone,
                'contact' => $contact->getName(),
            ),
            'cartItems'         => array(
                'items' => $this->getItemsForFiscalization($order_data)
            ),
        );

        $country = $order_data['data']['shipping_address']['country_name'];
        $city = $order_data->data['shipping_address']['city'];
        $post_address = $order_data->data['shipping_address']['street'];

        if ($country && $city && $post_address) {
            $order_bundle['customerDetails']['deliveryInfo'] = array(
                'deliveryType' => $order_data->data['shipping_name'],
                'country'      => $country,
                'city'         => $city,
                'postAddress'  => $post_address,
            );
        }

        return json_encode($order_bundle);
    }

    /**
     * @param null $tax
     * @return int|mixed
     * @throws waPaymentException
     */
    protected function getTaxType($tax = null)
    {
        $sberbank_tax_codes = array(
            0  => 1,
            10 => 2,
            18 => 3,
        );

        if ($tax === null) {
            return 0; //without tax
        }

        $tax = intval($tax);
        if (empty($sberbank_tax_codes[$tax])) {
            self::log(
                $this->id,
                "Unknown VAT rate: {$tax}. The list of available bets: see Sberbank documentation."
            );
            throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
        }

        return $sberbank_tax_codes[$tax];
    }

    /**
     * Calculation of VAT
     * @param $amount
     * @param $tax
     * @return float|int
     */
    protected function getTaxSum($amount, $tax)
    {
        if (!isset($tax)) {
            return 0;
        }
        $vat = ($amount * $tax) / (100 + $tax);
        return $vat;
    }

    /**
     * @param $order_data
     * @return array
     * @throws waException
     * @throws waPaymentException
     */
    protected function getItemsForFiscalization($order_data)
    {
        $items = array();
        if (is_array($order_data['items'])) {
            foreach ($order_data['items'] as $key => $data) {
                if (!$data['tax_included'] && (int)$data['tax_rate'] > 0) {
                    self::log($this->id, sprintf('НДС не включен в цену товара: %s.', var_export($data, true)));
                    throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
                }
                $items[] = $this->formalizeItemData($data, $order_data, $key);
            }
        };

        if (!empty($order_data['shipping'])) {
            if (!$order_data->shipping_tax_included && (int)$order_data->shipping_tax_rate > 0) {
                self::log($this->id, sprintf('НДС не включен в стоимость доставки (%s).', $order_data->shipping_name));
                throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
            }
            $data = array(
                'name'     => $order_data->shipping_name,
                'total'    => $order_data->shipping,
                'price'    => $order_data->shipping,
                'quantity' => 1,
                'tax_rate' => $order_data->shipping_tax_rate,
            );
            $position = count($items);
            $items[] = $this->formalizeItemData($data, $order_data, $position);
        }

        return $items;
    }

    /**
     * @param $data
     * @param $order_data
     * @param $number
     * @return array
     * @throws waException
     * @throws waPaymentException
     */
    public function formalizeItemData($data, $order_data, $number)
    {
        if (!empty($data['total_discount'])) {
            $data['total'] = $data['total'] - $data['total_discount'];
            $data['price'] = round($data['price'], 2) - round(ifset($data['discount'], 0.0), 2); //calculate flexible discounts
        }
        $tax_sum = $this->getTaxSum($data['price'], $data['tax_rate']);
        $item_data = array(
            'positionId'   => $number,
            'name'         => mb_substr($data['name'], 0, 100),
            'quantity'     => array(
                'value'   => $data['quantity'],
                'measure' => 'шт.',
            ),
            'itemAmount'   => round($data['total'] * 100),
            'itemCurrency' => $this->getCurrencyISO4217Code($order_data['currency']),
            'itemCode'     => $this->app_id.'_order_'.$order_data['id'].'_'.$order_data['type'].'_'.rand(1, 100000),
            'tax'          => array(
                'taxType' => $this->getTaxType($data['tax_rate']),
                'taxSum'  => round($tax_sum * 100, 2),
            ),
            'itemPrice'    => round($data['price'] * 100),
        );

        return $item_data;
    }

    /**
     * Checks whether there is a Check Transaction. If so, it requests the status of native_id
     * @return array|null|SimpleXMLElement|string
     */
    protected function getGatewayOrderStatus()
    {
        $response = null;

        if ($this->last_transaction_check) {
            $net = new waNet(array(
                'request_format' => waNet::FORMAT_RAW,
                'format'         => waNet::FORMAT_JSON,
            ));

            $status_fields = array(
                'userName' => $this->userName,
                'password' => $this->password,
                'orderId'  => $this->last_transaction_check['native_id'],
            );

            try {
                $response = $net->query($this->getURL('URL_ORDER_STATUS'), $status_fields, waNet::METHOD_POST);
            } catch (Exception $e) {
                $message = sprintf('%s: %s', $e->getMessage(), var_export(array(
                    'response' => $net->getResponse(true),
                    'headers'  => $net->getResponseHeader(),
                ), true));
                self::log($this->id, $message);
            }
        }

        return $response;
    }

    /**
     * @param $response
     * @return mixed
     * @throws waPaymentException
     */
    protected function validateRegisterResponse($response)
    {
        if (!empty($response['errorCode']) && $response['errorCode'] != '0') {
            self::log($this->id, array(
                'errorMessage' => ifset($response['errorMessage']),
                'errorCode'    => ifset($response['errorCode']),
            ));

            throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
        }

        if (!ifset($response['formUrl'])) {
            self::log($this->id, 'formUrl not received');
            throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
        }

        return $response;
    }

    /**
     * Split OrderNumber into components:
     * 1) app_id
     * 2) merchant_id
     * 3) order_id
     *
     * @param array $request
     * @return waPayment
     * @throws waPaymentException
     */
    public function callbackInit($request)
    {
        if (!empty($request['orderNumber'])) {
            $data = explode('_', $request['orderNumber']);
            $this->app_id = $data[0];
            $this->merchant_id = $data[1];
            $this->order_id = $data[2];
        } else {
            throw new waPaymentException('Empty required field(s)');
        }

        return parent::callbackInit($request);
    }

    /**
     * Get all transactions with order
     * @param $order_id
     */
    protected function getTransactionsForOrder($order_id)
    {
        $transactions = $this->getTransactionsByFields(array(
            'plugin'      => $this->id,
            'order_id'    => $order_id,
            'app_id'      => $this->app_id,
            'merchant_id' => $this->key,
        ));

        if ($transactions) {
            foreach ($transactions as $id => $transaction) {
                if ($transaction['type'] == 'CHECK') {
                    $this->last_transaction_check = $transaction;
                    $this->count_transactions_check += 1;
                }
                if ($transaction['parent_id']) {
                    $this->parent_transaction = $transactions[$id];
                }
                $this->last_transaction = $transaction;
            }
        }
    }

    /**
     * Checks how much the order was paid for and checks the status of the parent transaction
     * @param $data
     * @param $status
     * @return mixed
     */
    protected function compareTransactionData($data, $status)
    {
        if (isset($status['paymentAmountInfo']['depositedAmount']) && $status['paymentAmountInfo']['approvedAmount']) {
            if ($status['paymentAmountInfo']['depositedAmount'] != 0) {
                $status_amount = $status['paymentAmountInfo']['depositedAmount'];
            } else {
                $status_amount = $status['paymentAmountInfo']['approvedAmount'];
            }

            if ($this->last_transaction_check['amount'] * 100 != $status_amount) {
                $data['view_data'] .= '. Заказ оплачен на сумму '.$status['paymentAmountInfo']['depositedAmount'] / 100 .' RUB из';
            }
        }

        if ($this->parent_transaction['type'] == $data['type']) {
            $data['parent_state'] = null;
        }

        return $data;
    }

    /**
     * Compare the last transaction to the transaction being added
     * @param $type
     * @return bool
     */
    private function compareLastTransactionType($type)
    {
        if (ifset($this->last_transaction['type']) == $type) {
            return true;
        }
        return false;
    }

    /**
     * Getting a single-level array for saving in the database
     * @param $order_status
     * @return array
     */
    public function formalizeRawData($order_status)
    {
        $slice = array(
            'cardAuthInfo'      => '',
            'paymentAmountInfo' => '',
            'bankInfo'          => '',
        );

        //Unnecessary data
        $delete = array(
            'orderBundle'       => '',
            'attributes'        => '',
            'errorCode'         => '',
            'errorMessage'      => '',
            'cardAuthInfo'      => '',
            'paymentAmountInfo' => '',
            'bankInfo'          => '',

        );

        foreach ($order_status as $key => $value) {
            if (array_key_exists($key, $slice)) {
                foreach ($value as $name => $param) {
                    $order_status[$key.'.'.$name] = $param;
                }
            }

            //if there are new arrays
            if (is_array($value)) {
                $order_status[$key] = json_encode($value);
            }
        }

        return array_diff_key($order_status, $delete);
    }

    /**
     * @param array $order_status
     * @return array
     */
    public function formalizeData($order_status)
    {
        $view_data = ifset($order_status['cardAuthInfo']['pan']) ? 'Card: '.$order_status['cardAuthInfo']['pan'] : null;
        $view_data .= ifset($order_status['cardAuthInfo']['cardholderName']) ? ', name: '.$order_status['cardAuthInfo']['cardholderName'] : null;

        $transaction_data = array(
            'native_id'   => $this->last_transaction_check['native_id'],
            'order_id'    => $this->last_transaction_check['order_id'],
            'customer_id' => $this->last_transaction_check['customer_id'],
            'view_data'   => $this->last_transaction_check['view_data'].$view_data,
            'amount'      => ifset($order_status['Amount']) ? $order_status['Amount'] / 100 : $this->last_transaction_check['amount'],
            'currency_id' => $this->last_transaction_check['currency_id'],
        );

        return $transaction_data;
    }

    /**
     * Connection coordinates
     * @see https://goo.gl/yBzLJy
     * @param $url
     * @return string
     */
    protected function getURL($url)
    {
        $domain = 'https://securepayments.sberbank.ru';
        
        $urls = array(
            'URL_ORDER_REGISTER'     => '/payment/rest/register.do',
            'URL_ORDER_STATUS'       => '/payment/rest/getOrderStatusExtended.do',
            'URL_ORDER_PRE_REGISTER' => '/payment/rest/registerPreAuth.do',
            'URL_PAYMENT_COMPLETE'   => '/payment/rest/deposit.do',
            'URL_PAYMENT_CANCEL'     => '/payment/rest/reverse.do',
        );

        // If the test mode is enabled, replace the URL
        if ($this->TESTMODE) {
            $domain = 'https://3dsec.sberbank.ru';
        }

        return $domain.$urls[$url];
    }
}
