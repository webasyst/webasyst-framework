<?php


/**
 * Class sbPayment
 *
 * All amounts must be in the minimum currency units
 * @property $userName
 * @property $TESTMODE
 * @property $currency_id
 * @property $sessionTimeoutSecs
 * @property $password
 * @property $two_step
 * @property $tax_system
 * @property $fiscalization
 * @property $cancel  Need to activate in Sberbank
 *
 * @property array payment_method
 * @property array payment_subject_product
 * @property array payment_subject_service
 * @property array payment_subject_shipping
 *
 * @property $credit
 * @property $credit_type
 *
 * @link https://developer.sberbank.ru/doc
 * @link https://securepayments.sberbank.ru/wiki/doku.php
 */
class sbPayment extends waPayment implements waIPaymentCapture, waIPaymentCancel, waIPaymentRefund, waIPaymentRecurrent
{
    const SB_ORDER_CREATE = 0;
    const SB_ORDER_HOLD = 1;
    const SB_ORDER_PAID = 2;
    const SB_ORDER_CANCEL = 3;
    const SB_ORDER_REFUND = 4;

    protected $transactions = null;

    /**
     * Transfer of all payments into rubles.
     * @return string
     */
    public function allowedCurrency()
    {
        return 'RUB';
    }

    public function supportedOperations()
    {
        return array(
            self::OPERATION_AUTH_CAPTURE,
            self::OPERATION_AUTH_ONLY,
            self::OPERATION_CAPTURE,
            self::OPERATION_REFUND,
            self::OPERATION_CANCEL,
            self::OPERATION_RECURRENT,
        );
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
        }

        $gateway_info = $this->getGatewayTransactionStatus($order_data['order_id']);
        $error = $this->validate($gateway_info);
        $redirect_url = null;

        if (!$error && $gateway_info) {
            $status = ifset($gateway_info, 'orderStatus', null);
            // It's an existing order
            if ($status === 0) {
                $check = $this->getTransactionByType('CHECK');
                $redirect_url = $check['raw_data']['formUrl'];
            } else {
                // Callback did not come. Need to change the payment status of the order
                $this->setAndFormalizeTransaction($gateway_info, $status);
            }
        } elseif (!$error) {
            // It's a new order, we need to create new transaction
            $registered_order = $this->registerOrder($order_data);
            $redirect_url = $registered_order['formUrl'];
        }

        $result = null;
        if ($redirect_url) {
            $result = $this->viewForm($redirect_url, $auto_submit);
        } elseif ($error) {
            $result = $error;
        }

        return $result;
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

        $request_cancel = array(
            'orderId'  => $data['transaction']['native_id'],
            'language' => $this->getLanguage(),
        );

        $result = $this->sendRequest($this->getURL('URL_PAYMENT_CANCEL'), $request_cancel, false);
        $save = $this->setTransactionFromCli(self::SB_ORDER_CANCEL, $data['transaction']['order_id'], $result);

        return $this->getInterfaceMethodResult($save);
    }

    /**
     * @param $data
     * @return array|bool
     * @throws waException
     * @throws waPaymentException
     */
    public function refund($data)
    {
        $request_cancel = array(
            'orderId'  => $data['transaction']['native_id'],
            'language' => $this->getLanguage(),
            'amount'   => (int)round($data['transaction']['amount'] * 100), //convert to cent
        );

        $result = $this->sendRequest($this->getURL('URL_PAYMENT_REFUND'), $request_cancel, false);
        $save = $this->setTransactionFromCli(self::SB_ORDER_REFUND, $data['transaction']['order_id'], $result);

        return $this->getInterfaceMethodResult($save);
    }

    /**
     * @param $data
     * @return array|bool
     * @throws waPaymentException
     * @throws waException
     */
    public function capture($data)
    {
        $request_completion = array(
            'orderId' => $data['transaction']['native_id'],
            'amount'  => round($data['transaction']['amount'] * 100), //convert to cent
        );

        $request_result = $this->sendRequest($this->getURL('URL_PAYMENT_COMPLETE'), $request_completion, false);
        $save = $this->setTransactionFromCli(self::SB_ORDER_PAID, $data['transaction']['order_id'], $request_result);

        return $this->getInterfaceMethodResult($save);
    }

    /**
     * @param waOrder $order_data
     * @return bool|array
     * @throws waException
     * @throws waPaymentException
     */
    public function recurrent($order_data)
    {
        if (empty($order_data['card_native_id'])) {
            return false;
        }

        // We get the last transaction on the order and look at its status.
        // The last transaction must be a check, otherwise we are not interested and we create a new application
        $last_transaction = $this->getGatewayTransactionStatus($order_data['order_id']);

        // We alid that the money is not blocked and it makes sense to re-register
        $error = $this->validate($last_transaction);

        // Register a new payment for bundles
        if (!$error) {
            if (!$last_transaction) {
                $order_data['is_recurrent'] = true;
                $registered_order = $this->registerOrder($order_data);
                $md_order = ifset($registered_order, 'orderId', null);
            } elseif ($last_transaction['orderStatus'] == self::SB_ORDER_CREATE) {
                // the order is registered but not paid;
                $md_order = ifset($last_transaction, 'mdOrder', null);
            }
        }

        // We carry out payment by bundles
        $save = array();
        if (!empty($md_order)) {
            $user_data = $this->getUserData($order_data['contact_id']);
            $request_data = array(
                'mdOrder'   => $md_order,
                'bindingId' => $order_data['card_native_id'],
                'language'  => $this->getLanguage(),
                'ip'        => waRequest::getIp(),
                'email'     => $user_data['email']
            );

            $request_result = $this->sendRequest($this->getURL('URL_PAYMENT_ORDER_BINDING'), $request_data);
            $save = $this->setTransactionFromCli(self::SB_ORDER_PAID, $order_data['order_id'], $request_result);
        }

        return $this->getInterfaceMethodResult($save);
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
     * @param array $order_info
     * @return array
     * @throws waPaymentException
     */
    public function formalizeData($order_info)
    {
        $transaction = $this->getTransactionByType('PARENT');

        if (!$transaction) {
            $transaction = $this->getTransactionByType('LAST');
        }

        if (!$transaction) {
            throw new waPaymentException('Register transaction not found.');
        }

        $transaction_data = array(
            'native_id'   => $transaction['native_id'],
            'order_id'    => $transaction['order_id'],
            'customer_id' => $transaction['customer_id'],
            'view_data'   => $this->getViewData($order_info),
            'amount'      => ifset($order_info['Amount']) ? $order_info['Amount'] / 100 : $transaction['amount'],
            'currency_id' => $transaction['currency_id'],
            'result'      => 1,
        );

        $binding_id = ifset($order_info, 'bindingInfo', 'bindingId', false);
        if ($binding_id) {
            $transaction_data['card_native_id'] = $binding_id;

            $pan = ifset($order_info, 'cardAuthInfo', 'pan', false);
            if ($pan) {
                $transaction_data['card_view'] = $pan;
            }

            $expiration = ifset($order_info, 'cardAuthInfo', 'expiration', false);
            if ($expiration) {
                $transaction_data['card_expire_date'] = $this->parseExpiration($expiration);
            }
        }

        return $transaction_data;
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
     * @throws waException
     */
    public function callbackInit($request)
    {
        $order_id = null;
        if (!empty($request['orderNumber'])) {
            $request = $this->extendByCallbackRequest($request);
            $this->app_id = ifset($request, 'app_id', null);
            $this->merchant_id = ifset($request, 'merchant_id', null);
        } else {
            throw new waPaymentException('Empty required field(s)');
        }

        return parent::callbackInit($request);
    }

    /**
     * @param string $request
     * @return void
     * @throws waException
     */
    public function callbackHandler($request)
    {
        $parsed_request = $this->extendByCallbackRequest($request);
        $order_id = ifset($parsed_request, 'order_id', null);
        $gateway_info = $this->getGatewayTransactionStatus($order_id);

        if ($gateway_info) {
            $transaction = $this->setAndFormalizeTransaction($gateway_info, $gateway_info['orderStatus']);
        }

        // Need order_id to redirect
        if (empty($transaction)) {
            $transaction = ['order_id' => $order_id];
        }

        //Redirect user
        if (!$gateway_info || $gateway_info['errorCode'] != '0') {
            wa()->getResponse()->redirect($this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction));
        } else {
            wa()->getResponse()->redirect($this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction));
        };
    }

    /**
     * Because the application base processes the result differently
     * TODO Help me! REMOVE THIS!
     * @param $save
     * @return array
     */
    protected function getInterfaceMethodResult($save)
    {
        $app_result = 0;
        if (ifset($save, 'result', 0) == 0) {
            $app_result = 1;
        }

        return array('result' => $app_result, 'description' => '');
    }

    /**
     *
     * @param $request
     * @return array
     */
    protected function extendByCallbackRequest($request)
    {
        $order_number = ifset($request, 'orderNumber', false);

        if ($order_number && is_string($order_number) && is_array($request)) {
            $data = explode('_', $order_number);

            if (count($data) > 2) {
                $request['app_id'] = $data[0];
                $request['merchant_id'] = $data[1];
                $request['order_id'] = $data[2];
            }
        }

        return $request;
    }

    protected function validate($order_info)
    {
        $order_status = ifset($order_info, 'orderStatus', null);
        $error = null;

        // If settings error
        if ($order_info && ($order_status == 3 || $order_status == 6)) {
            $this->logError(ifset($order_info, 'errorCode', '').': '.ifset($order_info, 'errorMessage', ''));
            $error = 'Ошибка платежа. Обратитесь в службу поддержки.';
        } elseif ($order_info && $order_status == 1) {
            $error = 'Деньги заблокированы.';
        }

        return $error;
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
     * @param $response
     * @param $order_data
     * @return bool
     * @throws waPaymentException
     * @throws waException
     */
    protected function saveCheckTransaction($response, $order_data)
    {
        $transaction_data = array(
            'native_id'   => $response['orderId'],
            'order_id'    => $order_data['id'],
            'customer_id' => $order_data['contact_id'],
            'view_data'   => $this->TESTMODE ? 'Оплата в тестовом режиме'."\n" : null,
            'amount'      => $order_data['total'],
            'currency_id' => $order_data['currency'],
            'result'      => 1,
        );
        $transaction_raw_data = array('formUrl' => $response['formUrl']);

        if (!$this->setTransaction(0, $transaction_data, $transaction_raw_data)) {
            throw new waPaymentException('Ошибка сохранения транзакции');
        }

        return true;
    }

    /**
     * @param $status
     * @param $transaction_data
     * @param array $transaction_raw_data
     * @return array
     * @throws waException
     */
    protected function setTransaction($status, $transaction_data, $transaction_raw_data = array())
    {
        $transaction_data = $this->extendTransactionData($status, $transaction_data);
        $transaction = array();

        if ($transaction_data && !$this->isIdenticalToSaved($transaction_data['type'])) {
            $transaction = $this->saveTransactionMock($transaction_data, $transaction_raw_data);
            $this->setAllTransaction($transaction['order_id']);

            $app_payment_method = $this->getAppPaymentMethod($status, ifset($transaction, 'result', false));
            if ($app_payment_method && $transaction) {
                $this->execAppCallback($app_payment_method, $transaction);
            }
        }

        return $transaction;
    }

    /**
     * Need for tests
     * ye   qwe
     * @param $transaction_data
     * @param $transaction_raw_data
     * @return array
     */
    protected function saveTransactionMock($transaction_data, $transaction_raw_data)
    {
        return $this->saveTransaction($transaction_data, $transaction_raw_data);
    }

    /**
     * @param $status
     * @param $order_id
     * @param null $request_result
     * @return array
     * @throws waException
     * @throws waPaymentException
     */
    protected function setTransactionFromCli($status, $order_id, $request_result = null)
    {
        $gateway_info = $this->getGatewayTransactionStatus($order_id);
        $error = ifset($request_result, 'errorCode', 0);

        return $this->setAndFormalizeTransaction($gateway_info, $status, $error);
    }

    /**
     * @param $gateway_info
     * @param $status
     * @param int $error
     * @return array
     * @throws waException
     * @throws waPaymentException
     */
    protected function setAndFormalizeTransaction($gateway_info, $status, $error = 0)
    {
        $transaction = $this->formalizeData($gateway_info);
        $transaction_raw_data = $this->formalizeRawData($gateway_info);

        if ($error) {
            $transaction['result'] = 0;
        }

        return $this->setTransaction($status, $transaction, $transaction_raw_data);
    }

    /**
     * Adds type and state to transaction
     *
     * @param int $status
     * @param array $transaction_data
     * @return array
     */
    protected function extendTransactionData($status, $transaction_data)
    {
        $parent_transaction = $this->getTransactionByType('PARENT');
        $parent_type = ifset($parent_transaction, 'type', null);
        $parent_id = ifset($parent_transaction, 'id', null);

        switch ($status) {
            // Order create but not paid
            case self::SB_ORDER_CREATE:
                $transaction_data += array(
                    'type' => self::OPERATION_CHECK,
                );
                break;

            //Amount Hold
            case self::SB_ORDER_HOLD:
                $transaction_data += array(
                    'type'  => self::OPERATION_AUTH_ONLY,
                    'state' => self::STATE_AUTH,
                );
                break;

            //Order paid
            case self::SB_ORDER_PAID:
                $transaction_data['state'] = self::STATE_CAPTURED;

                if ($parent_type === self::OPERATION_AUTH_ONLY) {
                    $transaction_data += array(
                        'type'         => self::OPERATION_CAPTURE,
                        'parent_id'    => $parent_id,
                        'parent_state' => self::STATE_CAPTURED,
                    );
                } else {
                    $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
                }
                break;

            //Cancel
            case self::SB_ORDER_CANCEL:
                $transaction_data += array(
                    'type'         => self::OPERATION_CANCEL,
                    'state'        => self::STATE_CANCELED,
                    'parent_id'    => $parent_id,
                    'parent_state' => self::STATE_CANCELED,
                );
                break;

            //Refund
            case self::SB_ORDER_REFUND:
                $transaction_data += array(
                    'type'         => self::OPERATION_REFUND,
                    'state'        => self::STATE_REFUNDED,
                    'parent_id'    => $parent_id,
                    'parent_state' => self::STATE_REFUNDED,
                );
                break;
        }

        if (empty($transaction_data['result'])) {
            $transaction_data['state'] = self::STATE_DECLINED;
            unset($transaction_data['parent_state']);
        }

        return $transaction_data;
    }

    /**
     * Returns the type of callback to be called on the application
     *
     * @param int $status
     * @param bool|int $result
     * @return string
     */
    protected function getAppPaymentMethod($status, $result)
    {
        $app_payment_method = '';

        switch ($status) {
            //Amount Hold
            case 1:
                $app_payment_method = self::CALLBACK_CAPTURE;
                //Shop App Solution
                if ($this->app_id == 'shop') {
                    $app_payment_method = self::CALLBACK_NOTIFY;
                };
                break;
            //Order paid
            case 2:
                $app_payment_method = self::CALLBACK_PAYMENT;
                break;
            //Cancel
            case 3:
                $app_payment_method = self::CALLBACK_CANCEL;
                break;

            //Refund
            case 4:
                $app_payment_method = self::CALLBACK_REFUND;
                break;
        }

        if (!$result) {
            $app_payment_method = self::CALLBACK_DECLINE;
        }

        return $app_payment_method;
    }

    /**
     * @param waOrder $wa_order
     * @return array|mixed|SimpleXMLElement|string
     * @throws waException
     * @throws waPaymentException
     */
    protected function registerOrder($wa_order)
    {
        $order_number = $this->getOrderNumber($wa_order->id);
        $return_url = $this->getRelayUrl().'?orderNumber='.$order_number;
        $register_fields = array(
            'userName'           => $this->userName,
            'password'           => $this->password,
            'orderNumber'        => $order_number,
            'amount'             => round($wa_order->total * 100), //convert to cent
            'currency'           => $this->getCurrencyISO4217Code($wa_order->currency),
            'returnUrl'          => $return_url,
            'failUrl'            => $return_url,
            'description'        => $wa_order->datetime,
            'language'           => $this->getLanguage(),
            'sessionTimeoutSecs' => ($this->sessionTimeoutSecs ? $this->sessionTimeoutSecs : 24) * 60 * 60, //convert hours to sec,
        );

        // This flag was added in the recurrent method
        if ($wa_order['is_recurrent'] && $wa_order->card_native_id) {
            $register_fields['features'] = 'AUTO_PAYMENT';
            $register_fields['clientId'] = $wa_order->contact_id;
        }

        if ($this->fiscalization) {
            $register_fields['orderBundle'] = $this->getInfoForFiscalization($wa_order);
            $register_fields['taxSystem'] = $this->tax_system;
        }

        // Create a bunch
        if ($wa_order->save_card) {
            $register_fields['clientId'] = $wa_order->contact_id;
        }

        if ($this->credit) {
            $register_fields['jsonParams'] = json_encode($this->getUserData($wa_order['contact_id']));

            if ($this->TESTMODE) {
                $register_fields['dummy'] = true;
            }
        }

        $response = $this->sendRequest($this->getURL('URL_ORDER_REGISTER'), $register_fields, false);
        $response = $this->validateRegisterResponse($response);

        $this->saveCheckTransaction($response, $wa_order);

        return $response;
    }

    /**
     * Returns transaction by type
     *
     * @param string $type CHECK || PARENT || LAST
     * @return array
     */
    protected function getTransactionByType($type)
    {
        $result = array();

        if (is_array($this->transactions)) {
            switch ($type) {
                case 'CHECK':
                    foreach ($this->transactions as $id => $transaction) {
                        $type = ifset($transaction, 'type', false);
                        // set last check transaction
                        if ($type == 'CHECK') {
                            $result = $transaction;
                        }
                    }
                    break;
                case 'PARENT':
                    foreach ($this->transactions as $id => $transaction) {
                        $type = ifset($transaction, 'type', false);
                        // get first payment transaction
                        if ($type == self::OPERATION_AUTH_CAPTURE || $type == self::OPERATION_AUTH_ONLY) {
                            $result = $transaction;
                            break;
                        }
                    }
                    break;
                case 'LAST':
                    $last = end($this->transactions);
                    if (is_array($last)) {
                        $result = $last;
                    }
                    break;
            }
        }

        return $result;
    }

    /**
     * @param waOrder $waOrder
     * @return string
     * @throws waPaymentException
     * @throws waException
     */
    protected function getInfoForFiscalization($waOrder)
    {
        $contact = new waContact($waOrder['contact_id']);
        $data = $this->getUserData($waOrder['contact_id']);

        if (!$data['email'] && !$data['phone']) {
            $this->logError( 'Не установлен системный Email.');
            throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
        }

        $order_bundle = array(
            'orderCreationDate' => time() * 1000,
            'customerDetails'   => array(
                'email'   => $data['email'],
                'phone'   => $data['phone'],
                'contact' => $contact->getName(),
            ),
            'cartItems'         => array(
                'items' => $this->getItemsForFiscalization($waOrder)
            ),
        );

        $country = $this->getISO2CountryCode($waOrder['shipping_address']['country']);
        $city = $waOrder['shipping_address']['city'];
        $post_address = $waOrder['shipping_address']['street'];

        if ($country && $city && $post_address) {
            $order_bundle['customerDetails']['deliveryInfo'] = array(
                'country'     => $country,
                'city'        => $city,
                'postAddress' => $post_address,
            );
        }

        if ($this->credit) {
            $order_bundle['installments'] = array(
                'productID'   => '10',
                'productType' => $this->credit_type
            );
        }

        return json_encode($order_bundle);
    }

    /**
     * @param $contact_id
     * @param bool $default
     * @return array
     * @throws waException
     */
    protected function getUserData($contact_id, $default = false)
    {
        $contact = new waContact($contact_id);
        $phone = $contact->get('phone', 'default');
        $email = $contact->get('email', 'default');

        if (!$email && $default) {
            $email = waMail::getDefaultFrom();
            $email = key($email);
        }
        $result = [
            'email' => $email,
            'phone' => $phone,
        ];

        return $result;
    }

    /**
     * @param null $tax
     * @return int|mixed
     * @throws waPaymentException
     */
    protected function getTaxType($tax = null)
    {
        $tax_type = null;

        if ($tax == 20) {
            $tax_type = 6;
        } elseif ($tax == 18) {
            $tax_type = 3;
        } elseif ($tax == 10) {
            $tax_type = 2;
        } elseif (is_numeric($tax) && $tax == 0) {
            $tax_type = 1;
        } elseif ($tax === null) {
            $tax_type = 0;
        } else {
            $this->logError("Unknown VAT rate: {$tax}. The list of available bets: see Sberbank documentation.");
            throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
        }

        return $tax_type;
    }

    /**
     * Calculation of VAT
     * @param int|float $amount
     * @param int|float $tax
     * @return float|int
     */
    protected function getTaxSum($amount, $tax)
    {
        if (!isset($tax) || !is_numeric($amount) || !is_numeric($tax)) {
            return 0;
        }

        $vat = ($amount * $tax) / (100 + $tax);
        return $vat;
    }

    /**
     * @param waOrder $order_data
     * @return array
     * @throws waException
     * @throws waPaymentException
     */
    protected function getItemsForFiscalization($order_data)
    {
        $items = array();
        $item_number = 0;

        if (is_array($order_data->items)) {
            foreach ($order_data->items as $data) {
                $item_number++;
                if (!$data['tax_included'] && (int)$data['tax_rate'] > 0) {
                    $this->logError(sprintf('НДС не включён в цену товара: %s.', var_export($data, true)));
                    throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
                }

                $items[] = $this->formalizeItemData($data, $order_data, $item_number);
            }
        };

        if (!empty($order_data['shipping'])) {
            if (!$order_data->shipping_tax_included && (int)$order_data->shipping_tax_rate > 0) {
                $this->logError(sprintf('НДС не включён в стоимость доставки (%s).', $order_data->shipping_name));
                throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
            }
            $data = array(
                'name'     => $order_data->shipping_name,
                'total'    => $order_data->shipping,
                'price'    => $order_data->shipping,
                'quantity' => 1,
                'tax_rate' => $order_data->shipping_tax_rate,
                'type'     => 'shipping'
            );
            $shipping_number = $item_number + 1;
            $items[] = $this->formalizeItemData($data, $order_data, $shipping_number);
        }

        return $items;
    }

    /**
     * @param $data
     * @param waOrder $order_data
     * @param $number
     * @return array
     * @throws waException
     * @throws waPaymentException
     */
    protected function formalizeItemData($data, $order_data, $number)
    {
        if (!empty($data['total_discount'])) {
            $data['total'] = $data['total'] - $data['total_discount'];
            $discount = round(ifset($data, 'discount', 0.0), 2);
            $data['price'] = round($data['price'], 2) - $discount; //calculate flexible discounts
        }
        $tax_sum = $this->getTaxSum($data['price'], $data['tax_rate']);
        $item_data = array(
            'positionId'   => $number,
            'name'         => mb_substr($data['name'], 0, 100),
            'quantity'     => array(
                'value'   => (int)$data['quantity'],
                'measure' => 'шт.',
            ),
            'itemAmount'   => round($data['total'] * 100),
            'itemPrice'    => round($data['price'] * 100),
            'itemCurrency' => $this->getCurrencyISO4217Code($order_data['currency']),
            'itemCode'     => $this->app_id.'_order_'.$order_data['id'].'_'.$order_data['type'].'_'.rand(1, 100000),
            'tax'          => array(
                'taxType' => $this->getTaxType($data['tax_rate']),
                'taxSum'  => round($tax_sum * 100, 2),
            ),
        );

        //Credit dont work for ФФД 1.05
        if (!$this->credit) {
            $item_data['itemAttributes'] = [
                'attributes' => [
                    [
                        'name'  => 'paymentMethod',
                        'value' => $this->payment_method,
                    ],
                    [
                        'name'  => 'paymentObject',
                        'value' => $this->getPaymentObject(ifset($data, 'type', null))
                    ],
                ]
            ];
        }

        return $item_data;
    }

    protected function getPaymentObject($type)
    {
        $result = '13';

        switch ($type) {
            case 'product':
                $result = $this->payment_subject_product;
                break;
            case 'service':
                $result = $this->payment_subject_service;
                break;
            case 'shipping':
                $result = $this->payment_subject_shipping;
                break;
        }

        return $result;
    }

    /**
     * Checks whether there is a Check Transaction. If so, it requests the status of native_id
     * @param $order_id
     * @return array
     * @throws waException
     * @throws waPaymentException
     */
    protected function getGatewayTransactionStatus($order_id)
    {
        $response = array();

        if (is_null($this->transactions)) {
            $this->setAllTransaction($order_id);
        }

        $last = $this->getTransactionByType('LAST');
        if ($last) {
            $order_number = $this->getOrderNumber($order_id);
            $request = ['orderId' => $last['native_id'], 'orderNumber' => $order_number];

            if ($this->credit) {
                unset($request['orderId']);
            }

            $response = $this->sendRequest($this->getURL('URL_ORDER_STATUS'), $request);
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
            $this->logError( array(
                'errorMessage' => ifset($response['errorMessage']),
                'errorCode'    => ifset($response['errorCode']),
            ));

            throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
        }

        if (empty($response['formUrl'])) {
            $this->logError( 'formUrl not received');
            throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
        }

        return $response;
    }

    /**
     * Get all transactions with order
     * @param $order_id
     * @throws waException
     */
    protected function setAllTransaction($order_id)
    {
        $this->transactions = $this->getTransactionsByFields(array(
            'plugin'      => $this->id,
            'order_id'    => $order_id,
            'app_id'      => $this->app_id,
            'merchant_id' => $this->key,
        ));

        // because paranoia.
        asort($this->transactions);
    }

    /**
     * Writes information to the order log
     *
     * @param $gateway_info
     * @return string
     */
    protected function getViewData($gateway_info)
    {
        $view_data = array();
        $status = ifset($gateway_info, 'orderStatus', null);

        if ($status === self::SB_ORDER_HOLD && $this->app_id == 'shop') {
            $view_data[] = 'Деньги заблокированы';
        } elseif ($status === self::SB_ORDER_PAID) {
            $pan = ifset($gateway_info, 'cardAuthInfo', 'pan', false);
            if ($pan) {
                $view_data[] = "Card: {$pan}";
            }

            $cardholder_name = ifset($gateway_info, 'cardAuthInfo', 'cardholderName', false);
            if ($cardholder_name) {
                $view_data[] = "Name: {$cardholder_name}";
            }

            $deposited_amount = ifset($gateway_info, 'paymentAmountInfo', 'depositedAmount', false);
            $approved_amount = ifset($gateway_info, 'paymentAmountInfo', 'approvedAmount', false);
            if ($deposited_amount && $approved_amount) {
                if ($deposited_amount != 0) {
                    $status_amount = $deposited_amount;
                } else {
                    $status_amount = $approved_amount;
                }

                $parent = $this->getTransactionByType('PARENT');
                $amount = ifset($parent, 'amount', 0) * 100; //convert to rubles

                if ($amount != $status_amount) {
                    $view_data[] = 'Заказ оплачен на сумму '.$status_amount / 100 .' RUB из '.$amount;
                }
            }
        }

        $view_data = implode('. ', $view_data);
        return $view_data;
    }

    /**
     * Compare the last transaction to the transaction being added
     * @param $type
     * @return bool
     */
    protected function isIdenticalToSaved($type)
    {
        $result = false;
        $last_transaction = $this->getTransactionByType('LAST');
        $last_type = ifset($last_transaction, 'type', false);

        if ($last_type == $type) {
            $result = true;
        }

        return $result;
    }

    /**
     * @param $url
     * @param $data
     * @param bool $net_type
     * @return array
     * @throws waPaymentException
     */
    protected function sendRequest($url, $data, $net_type = true)
    {
        $data['userName'] = $this->userName;
        $data['password'] = $this->password;

        if ($net_type) {
            $net = new waNet(array(
                'request_format' => waNet::FORMAT_RAW,
                'format'         => waNet::FORMAT_JSON,
            ));

        } else {
            $net = new waNet(array(
                'request_format' => 'default',
                'format'         => waNet::FORMAT_JSON,
                'verify'         => false,
            ));
        }

        try {
            $response = $net->query($url, $data, waNet::METHOD_POST);
        } catch (Exception $e) {

        }

        if (empty($response) || !empty($response['errorCode'])) {
            $this->logRequest($net, $data);
            throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
        }

        return $response;
    }

    /**
     * @param waNet $net
     * @param $data
     */
    protected function logRequest($net, $data)
    {
        unset($data['userName']);
        unset($data['password']);

        $request = var_export($data, true);
        $response = $net->getResponse(true);
        $headers =  var_export($net->getResponseHeader('http_code'),true);

        $log = <<<HTML
_________________________________
Request: {$request}
Headers: {$headers}
Response: {$response}
_________________________________
HTML;

        $this->logError( $log);
    }

    /**
     * @param $order_id
     * @return string
     */
    protected function getOrderNumber($order_id)
    {
        $result = null;
        if (is_numeric($order_id) || is_string($order_id)) {
            $result = $this->app_id.'_'.$this->merchant_id.'_'.$order_id;
        }

        return $result;
    }

    /**
     * Return locale code
     * @return string
     * @throws waException
     */
    protected function getLanguage()
    {
        $locale = wa()->getLocale();

        $result = '';
        if ($locale && strlen($locale) >= 2) {
            $result = substr($locale, 0, 2);
        }

        return $result;
    }

    /**
     * Connection coordinates
     *
     * @see https://goo.gl/yBzLJy
     * @param $url
     * @return string
     */
    protected function getURL($url)
    {
        $domain = 'https://securepayments.sberbank.ru';

        $urls = array(
            'URL_ORDER_REGISTER'        => 'register.do',
            'URL_ORDER_PRE_REGISTER'    => 'registerPreAuth.do',
            'URL_ORDER_STATUS'          => '/payment/rest/getOrderStatusExtended.do',
            'URL_PAYMENT_COMPLETE'      => '/payment/rest/deposit.do',
            'URL_PAYMENT_CANCEL'        => '/payment/rest/reverse.do',
            'URL_PAYMENT_REFUND'        => '/payment/rest/refund.do',
            'URL_PAYMENT_ORDER_BINDING' => '/payment/rest/paymentOrderBinding.do',
        );

        if ($url === 'URL_ORDER_REGISTER') {
            $path = $urls[$url];
            //For a two-stage payment, you need another link

            if ($this->two_step && !$this->credit) {
                $path = $urls['URL_ORDER_PRE_REGISTER'];
            }

            if ($this->credit) {
                $path = '/sbercredit/'.$path;
            } else {
                $path = '/payment/rest/'.$path;
            }

        } else {
            $path = ifset($urls, $url, '');
        }


        // If the test mode is enabled, replace the URL
        if ($this->TESTMODE) {
            $domain = 'https://3dsec.sberbank.ru';
        }

        $result = null;
        if ($path) {
            $result = $domain.$path;
        }

        return $result;
    }

    /**
     * need for tests. Delete when we go to the version of php> 5.3 and use static::log();
     * @param $data
     */
    protected function logError($data)
    {
        self::log($this->id, $data);
    }

    /** @noinspection PhpUnused */
    public static function settingsPaymentSubjectOptions()
    {
        return array(
            '1'  => 'товар',
            '2'  => 'подакцизный товар',
            '3'  => 'работа',
            '4'  => 'услуга',
            '5'  => 'ставка в азартной игре',
            '6'  => 'выигрыш в азартной игре',
            '7'  => 'лотерейный билет',
            '8'  => 'выигрыш в лотерею',
            '9'  => 'результаты интеллектуальной деятельности',
            '10' => 'платёж',
            '11' => 'агентское вознаграждение',
            '12' => 'несколько вариантов',
            '13' => 'другое',
        );
    }

    /**
     * Parse Sberbank format YYYYMM to Sql Date format
     *
     * @param int $expiration should be 6 digits
     * @return string
     */
    protected function parseExpiration($expiration)
    {
        $result = null;

        if (is_numeric($expiration)) {
            $expiration = (int)$expiration;
        }

        if (is_integer($expiration) && strlen($expiration) === 6) {
            $year = substr($expiration, 0, 4);
            $month = substr($expiration, 4, 2);

            $implode = $year.'-'.$month;
            try {
                $date = new DateTime($implode);
                $result = $date->format('Y-m-t');
            } catch (Exception $e) {

            }
        }

        return $result;
    }

    /**
     * @param string $iso3
     * @return string
     */
    protected function getISO2CountryCode($iso3)
    {
        $country_model = new waCountryModel();
        $iso2 = $country_model
            ->select('iso2letter')
            ->where(
                'iso3letter = :iso3',
                array('iso3' => $iso3)
            )
            ->fetchField('iso2letter');

        return $iso2;
    }
}