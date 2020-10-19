<?php

/**
 * @see https://kassa.yandex.ru/developers/api
 *
 * @property-read string  $shop_id
 * @property-read string  $shop_password
 * @property-read string  $payment_type
 * @property-read string  $customer_payment_type
 * @property-read bool    $receipt
 * @property-read int     $tax_system_code
 * @property-read string  $taxes
 * @property-read string  $payment_subject_type_product
 * @property-read string  $payment_subject_type_service
 * @property-read string  $payment_subject_type_shipping
 * @property-read string  $payment_method_type
 * @property-read string  $merchant_currency
 * @property-read boolean $manual_capture
 */
class yandexkassaPayment extends waPayment implements waIPayment, waIPaymentCancel, waIPaymentRefund, waIPaymentCapture
{
    protected static $currencies = array(
        'RUB',
        'EUR',
        'USD',
    );

    const CHESTNYZNAK_PRODUCT_CODE = 'chestnyznak';

    public function getSettingsHTML($params = array())
    {
        $html = parent::getSettingsHTML($params);

        $js = file_get_contents($this->path.'/js/settings.js');
        $html .= sprintf('<script type="text/javascript">%s</script>', $js);
        return $html;
    }

    /**
     * @param waOrder $order
     * @return string HTML
     * @throws SmartyException
     * @throws waException
     */
    public function displayPaymentForm($order)
    {
        $view = wa()->getView();

        $custom_fields = $this->customFields($order);

        $params = array();
        /*
        $params['namespace'] = 'payment_'.$m['id'];
        $params['title_wrapper'] = '%s';
        $params['description_wrapper'] = '<br><span class="hint">%s</span>';
        $params['control_wrapper'] = '<div class="name">%s</div><div class="value">%s %s</div>';
        $params['control_separator'] = '</div><div class="value>"';

        */
        $values = $order->billing_params;
        foreach ($custom_fields as $name => $row) {
            $row = array_merge($row, $params);

            if (!empty($row['control_type'])) {
                if (isset($values[$name])) {
                    $row['value'] = $values[$name];
                }
                $controls[$name] = waHtmlControl::getControl($row['control_type'], $name, $row);
            }
        }

        $view->assign('controls', $controls);

        return $view->fetch($this->path.'/templates/details.html');
    }

    public function customFields(waOrder $order)
    {
        $fields = array();

        $type = $this->payment_type;
        if ($type === 'customer') {
            $available_options = $this->customer_payment_type;
            $options = self::settingsCustomerPaymentTypeOptions();
            foreach ($options as $id => $option) {
                if (empty($available_options[$id])) {
                    unset($options[$id]);
                }
            }

            $params = $order->billing_params;
            $value = ifset($params, 'payment_type', null);;

            if (empty($value) || empty($available_types[$value])) {
                $value = null;
            }

            if (wa()->getEnv() === 'backend') {
                array_unshift($options, array(
                    'value' => '',
                    'title' => 'TODO:на выбор покупателя',
                    'group' => 'TODO group: на выбор покупателя',
                ));
            }


            $fields['payment_type'] = array(
                'value'        => $value,
                'name'         => 'TODO: Вид оплаты',
                'description'  => 'TODO: Выберите желаемый вариант оплаты',
                'control_type' => waHtmlControl::SELECT,
                'options'      => $options,
            );
        }

        return parent::customFields($order) + $fields;
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        try {
            $order = waOrder::factory($order_data);

            $type = $this->payment_type;
            if ($type === 'customer') {
                $params = $order->billing_params;
                $default = ifset($params, 'payment_type', null);
                $type = ifset($payment_form_data, 'payment_type', $default);
                $available_types = $this->customer_payment_type;
                if (empty($type) || empty($available_types[$type])) {
                    return $this->displayPaymentForm($order);
                }
            }

            $payment = $this->createPayment($order, $type);

            switch ($payment['status']) {
                case 'succeeded':
                case 'canceled':
                    $this->handlePayment($payment);
                    return $this->_w('Состояние платежа изменилось — обновите страницу.');
                    break;
                case 'pending':
                    if (!empty($payment['paid'])) {
                        switch (ifset($payment['receipt_registration'])) {
                            case 'pending':
                                return $this->_w('Ожидание регистрации чека.');
                        }
                        return $this->_w('Ожидание поступления денег.');
                    }
                    break;
                case 'waiting_for_capture':
                    return $this->_w('Платёж ожидает списания денег.');
                    break;
            }

            $url = null;
            $instruction = '';

            switch (ifset($payment['confirmation']['type'])) {
                case 'redirect':
                    $url = $payment['confirmation']['confirmation_url'];
                    break;
                case 'external':
                    $instruction = '';
                    break;
                case 'direct':
                    break;

            }

            $view = wa()->getView();

            $view->assign('order', $order);

            $view->assign('url', $url);

            $hidden_fields = array();
            parse_str(parse_url($url, PHP_URL_QUERY), $hidden_fields);
            $view->assign('form_url', preg_replace('@\?.*$@', '', $url));
            $view->assign('hidden_fields', $hidden_fields);

            $view->assign('instruction', $instruction);

            $view->assign('auto_submit', $auto_submit);

            $view->assign('settings', $this->getSettings());

            $view->assign('plugin', $this);
            return $view->fetch($this->path.'/templates/payment.html');
        } catch (Exception $ex) {
            $message = sprintf("Error occurred during %s: %s", __METHOD__, $ex->getMessage());
            self::log($this->id, $message);
            return $this->_w('Ошибка платежа. Обратитесь в службу поддержки.');
        }
    }

    public function capture($transaction_raw_data)
    {
        try {
            $transaction = $transaction_raw_data['transaction'];


            $payment = $this->getPaymentInfo($transaction['native_id']);

            if (!empty($payment['status']) && ($payment['status'] === 'waiting_for_capture')) {


                if (!empty($transaction_raw_data['order_data'])) {
                    $order = waOrder::factory($transaction_raw_data['order_data']);
                    //handle changed amount
                    $transaction['amount'] = $order->total;
                    $transaction['currency_id'] = $order->currency;
                    $transaction['receipt'] = $this->getReceiptData($order);
                } elseif ($this->receipt && !empty($payment['receipt'])) {
                    $transaction['receipt'] = $payment['receipt'];
                }

                $hash = md5(var_export($transaction, true));

                $payment = $this->apiQuery('capture', $transaction, $hash);
                $transaction_data = $this->formalizeData($payment);
            } else {
                $transaction_data = $this->handlePayment($payment);
            }

            return array(
                'result'      => 0,
                'data'        => $transaction_data,
                'description' => null,
            );

        } catch (Exception $ex) {
            $message = sprintf("Error occurred during %s: %s", __METHOD__, $ex->getMessage());
            self::log($this->id, $message);
            return array(
                'result'      => -1,
                'description' => $ex->getMessage(),
            );
        }
    }

    public function cancel($transaction_raw_data)
    {
        try {
            $transaction = $transaction_raw_data['transaction'];
            $hash = md5(var_export($transaction, true));
            $payment = $this->apiQuery('cancel', $transaction, $hash);
            $transaction_data = $this->formalizeData($payment);
            return array(
                'result'      => 0,
                'data'        => $transaction_data,
                'description' => null,
            );
        } catch (Exception $ex) {
            $message = sprintf("Error occurred during %s: %s", __METHOD__, $ex->getMessage());
            self::log($this->id, $message);
            return array(
                'result'      => -1,
                'description' => $ex->getMessage(),
            );
        }
    }

    /**
     * @param waOrder $order
     * @param string  $type
     * @return array
     * @throws waPaymentException
     */
    protected function formatPaymentData(waOrder $order, $type)
    {
        $data = array(
            'amount'       => array(
                'value'    => number_format(round($order->total, 2), 2, '.', ''),
                'currency' => $order->currency,
            ),
            'confirmation' => array(
                'type'       => 'redirect',
                'return_url' => $this->getRelayUrl(),
            ),
            'capture'      => !$this->manual_capture,
            'description'  => $order->description,
            'receipt'      => $this->getReceiptData($order),
            'client_ip'    => waRequest::getIp(),
            /**
             * Любые дополнительные данные, которые нужны вам для работы с платежами (например, номер заказа).
             * Передаются в виде набора пар «ключ-значение» и возвращаются в ответе от Яндекс.Кассы.
             * Ограничения: максимум 16 ключей, имя ключа не больше 32 символов, значение ключа не больше 512 символов.
             */
            'metadata'     => array(
                'app_id'      => $this->app_id,
                'merchant_id' => $this->merchant_id,
                'order_id'    => $order->id,
            ),
        );

        if (!empty($type)) {
            $data['payment_method_data'] = array(
                'type' => $type,
            );
        }

        if (empty($data['receipt'])) {
            unset($data['receipt']);
        }
        $return = array(
            'metadata' => $data['metadata'],
            'result'   => 'success',
        );
        $data['confirmation']['return_url'] .= '?'.http_build_query($return);

        return $data;
    }

    /**
     * @param $method
     * @param $data
     * @return string
     * @throws waPaymentException
     */
    protected function getEndpointUrl($method, &$data)
    {
        $url = 'https://payment.yandex.net/api/v3/';
        switch ($method) {
            case 'info': #https://payment.yandex.net/api/v3/payments/{payment_id}
                $url .= sprintf('payments/%s', $data);
                $data = null;
                break;
            case 'capture': #https://payment.yandex.net/api/v3/payments/{payment_id}/capture
                $url .= sprintf('payments/%s/capture ', $data['native_id']);
                $data = array(
                    'amount'  => array(
                        'value'    => number_format($data['amount'], 2, '.', ''),
                        'currency' => $data['currency_id'],
                    ),
                    'receipt' => ifset($data, 'receipt', null),
                );


                if (!$this->receipt || empty($data['receipt'])) {
                    unset($data['receipt']);
                }
                break;
            case 'cancel': #https://payment.yandex.net/api/v3/payments/{payment_id}/cancel
                $url .= sprintf('payments/%s/cancel', $data['native_id']);
                $data = '{}';
                break;
            case 'create': #https://payment.yandex.net/api/v3/payments
                if ($data instanceof waOrder) {
                    $data = $this->formatPaymentData($data);
                }
                $url .= 'payments';
                break;
            case 'refunds':
            case 'refunds_info':
                if (!is_array($data)) {
                    $url .= sprintf('refunds/%s', $data);
                    $data = null;
                } else {
                    $url .= 'refunds';
                }
                break;
        }
        return $url;
    }

    /**
     * @param $action
     * @param $data
     * @param $hash
     * @return array
     * @throws waException
     */
    protected function apiQuery($action, $data, $hash = null)
    {
        if (empty($hash) && ($hash !== false)) {
            $hash = md5(var_export($data, true));
        }
        if (!empty($hash)) {
            $headers = array(
                'Idempotence-Key' => $hash,
            );
        } else {
            $headers = array();
        }

        $params = array();
        if (is_array($data) && isset($data['%params'])) {
            $params = $data['%params'];
            unset($data['%params']);
        }

        $actions = array(
            //'cancel',
            'create',
            'refunds',
            //'capture',
        );

        $url = $this->getEndpointUrl($action, $data);

        $debug = array();

        $debug['url'] = $url;
        $debug['action'] = $action;
        $debug['merchant_id'] = $this->merchant_id;
        $debug['app_id'] = $this->app_id;
        if (!empty($data)) {
            $debug['data'] = $data;
            $debug['headers'] = $headers;
            $debug['params'] = $params;
        }

        try {
            $net = $this->getTransport($headers);

            $response = $net->query($url, $data, $data === null ? waNet::METHOD_GET : waNet::METHOD_POST);

            if (ifset($response['type']) === 'error') {
                $debug['response'] = $response;
                $message = sprintf(
                    '%s (#%s): %s',
                    ifset($response, 'parameter', 'Error'),
                    ifset($response, 'code', 'unknown'),
                    ifset($response, 'description', '--')
                );
                throw new waPaymentException($message);
            }
            if (in_array($action, $actions)) {

                $raw_data = array(
                    'response' => waUtils::jsonEncode($response),
                );

                switch ($action) {
                    case 'refunds':
                        if (!empty($params['refundable'])) {
                            $response['refundable'] = true;
                        }
                        $response['refunded_amount'] = $response['amount'];

                        break;
                }

                $wa_transaction_data = $this->formalizeData($response);
                if (!empty($params['order_id'])) {
                    $wa_transaction_data += array(
                        'order_id' => $params['order_id'],
                    );
                }
                if (!empty($params['parent_id'])) {
                    $wa_transaction_data['parent_id'] = $params['parent_id'];
                    $wa_transaction_data['parent_state'] = $wa_transaction_data['state'];
                }

                if (!empty($data['receipt']['customer'])) {
                    foreach ($data['receipt']['customer'] as $customer_field => $value) {
                        $raw_data[$customer_field] = $value;
                    }
                }
                $debug['transaction'] = $this->saveTransaction($wa_transaction_data, $raw_data);
            }
            $debug['response'] = $response;

            if (waSystemConfig::isDebug()) {
                self::log($this->id, $debug);
            }
            return $response;
        } catch (waException $ex) {
            $debug['message'] = $ex->getMessage();
            $debug['exception'] = get_class($ex);
            if (!empty($net)) {
                if (!isset($debug['response'])) {
                    $debug['raw_response'] = $net->getResponse(true);
                }
                $debug['header'] = $net->getResponseHeader();
            }
            self::log($this->id, $debug);
            throw $ex;
        }
    }

    /**
     * @param waOrder $order
     * @param string  $type
     * @return array
     * @throws waException
     */
    protected function createPayment(waOrder $order, $type)
    {
        #Payment data
        $data = $this->formatPaymentData($order, $type);
        $hash = md5(var_export($order, true).var_export($this->getSettings(), true));

        return $this->apiQuery('create', $data, $hash);
    }

    protected function getTransport($headers = array())
    {
        $options = array(
            'authorization'      => true,
            'login'              => $this->shop_id,
            'password'           => $this->shop_password,
            'format'             => waNet::FORMAT_JSON,
            'expected_http_code' => null,
        );
        $net = new waNet($options, $headers);

        return $net;
    }

    /**
     * @see https://kassa.yandex.ru/developers/api#payment_object
     * @param string $native_order_id
     * @return array
     * @throws waException
     */
    protected function getPaymentInfo($native_order_id)
    {
        return $this->apiQuery('info', $native_order_id, false);
    }

    /**
     * @see https://kassa.yandex.ru/developers/api#refund_object
     * @param string $native_refund_id
     * @return array
     * @throws waException
     */
    protected function getRefundInfo($native_refund_id)
    {
        return $this->apiQuery('refunds_info', $native_refund_id, false);
    }

    /**
     * @param array $request
     * @return waPayment
     * @throws waException
     */
    public function callbackInit($request)
    {
        if (empty($request)) {
            $event = $this->decodeRequest();
            $payment = ifset($event['object'], array());
        } else {
            $payment = $request;
        }
        $this->app_id = ifset($payment, 'metadata', 'app_id', null);
        $this->merchant_id = ifset($payment, 'metadata', 'merchant_id', null);
        if (!empty($payment['payment_id']) && empty($this->app_id) && empty($this->merchant_id)) {
            $search = array(
                'plugin'    => $this->id,
                'native_id' => $payment['payment_id'],
                'type'      => array(
                    self::OPERATION_AUTH_ONLY,
                    self::OPERATION_AUTH_CAPTURE,
                    self::OPERATION_CAPTURE,
                ),
            );

            $transaction_model = new waTransactionModel();

            $wa_transactions = $transaction_model->getByField($search, $transaction_model->getTableId());
            if ($wa_transactions) {
                ksort($wa_transactions, SORT_NUMERIC);
            }

            $app_id = array();
            $merchant_id = array();
            foreach ($wa_transactions as $wa_transaction) {
                if (!empty($wa_transaction['app_id'])) {
                    $app_id[] = $wa_transaction['app_id'];
                }
                if (!empty($wa_transaction['merchant_id'])) {
                    $merchant_id[] = $wa_transaction['merchant_id'];
                }
            }

            $app_id = array_unique($app_id);
            $merchant_id = array_unique($merchant_id);
            $debug = compact('payment', 'search', 'wa_transactions_data');
            self::log($this->id, var_export($debug, true));

            if ((count($merchant_id) === 1) && (count($app_id) === 1)) {
                $this->merchant_id = reset($merchant_id);
                $this->app_id = reset($app_id);
            }
        }
        return parent::callbackInit($payment);
    }

    /**
     * @param $payment
     * @return array
     */
    protected function handlePayment($payment)
    {
        $transaction_data = $this->formalizeData($payment);

        switch ($transaction_data['type']) {
            case self::OPERATION_CHECK:
                $app_payment_method = self::CALLBACK_CONFIRMATION;
                $transaction_data['state'] = '';
                break;

            case self::OPERATION_AUTH_CAPTURE:
            case self::OPERATION_CAPTURE:
                $app_payment_method = self::CALLBACK_PAYMENT;
                $transaction_data['state'] = self::STATE_CAPTURED;
                break;
            case self::OPERATION_CANCEL:
                $app_payment_method = self::CALLBACK_CANCEL;
                $transaction_data['state'] = self::STATE_CANCELED;
                break;
            case self::OPERATION_REFUND:
                if ($transaction_data['state'] == self::STATE_REFUNDED) {
                    //XXX update parent state;
                    $app_payment_method = self::CALLBACK_REFUND;
                    $app_payment_method = self::CALLBACK_NOTIFY;
                } else {
                    $app_payment_method = self::CALLBACK_NOTIFY;
                }
                break;
            case self::OPERATION_AUTH_ONLY:
                $app_payment_method = self::CALLBACK_AUTH;
                $transaction_data['state'] = self::STATE_AUTH;
                break;
            default:
                $app_payment_method = self::CALLBACK_NOTIFY;
                $transaction_data['state'] = '';
        }

        if (!empty($app_payment_method)) {
            $transaction = $transaction_data;
            unset($transaction['view_data']);
            $transaction_data = $this->saveTransaction($transaction, waUtils::jsonEncode($payment)) + $transaction_data;
            $this->execAppCallback($app_payment_method, $transaction_data);
        }

        return $transaction_data;
    }

    /**
     * @return array
     * @throws waException
     */
    protected function decodeRequest()
    {
        $request = @file_get_contents("php://input");
        try {
            return waUtils::jsonDecode($request, true);
        } catch (waException $ex) {
            $debug = array(
                'message' => $ex->getMessage(),
                'request' => $request,
            );
            self::log($this->id, var_export($debug, true));
            throw new waException('Invalid request');
        }
    }

    /**
     * @param array $request
     * @return array|mixed
     * @throws waException
     */
    public function callbackHandler($request)
    {
        if (waRequest::get('result')) {
            $transaction_data = $this->formalizeData($request);
            return $this->callbackHandlerRedirect($transaction_data, $request);
        } else {
            if (empty($request)) {
                $event = $this->decodeRequest();
                $object = ifset($event['object'], array());
            } else {
                $object = $request;
            }
            if (!empty($event['type'])) {
                switch ($event['type']) {
                    case 'notification':
                        /**
                         * payment.waiting_for_capture
                         */
                        switch ($event['event']) {
                            case 'payment.succeeded':
                            case 'payment.waiting_for_capture'://платеж перешел в статус waiting_for_capture
                            case 'payment.canceled':// платеж перешел в статус canceled
                                if (!empty($object['id'])) {
                                    try {
                                        $actual_payment = $this->getPaymentInfo($object['id']);
                                    } catch (waException $ex) {
                                        $message = sprintf("Error occurred during %s: %s", __METHOD__, $ex->getMessage());
                                        self::log($this->id, $message);
                                        throw new waPaymentException('Error during get payment info');
                                    }
                                }
                                break;
                            case 'refund.succeeded'://возврат перешел в статус succeeded
                                if (!empty($object['payment_id'])) {
                                    try {
                                        $actual_payment = $this->getPaymentInfo($object['payment_id']);
                                        if (!empty($object['id'])) {
                                            $actual_payment['refund'] = $this->getRefundInfo($object['id']);
                                        }
                                    } catch (waException $ex) {
                                        $message = sprintf("Error occurred during %s: %s", __METHOD__, $ex->getMessage());
                                        self::log($this->id, $message);
                                        throw new waPaymentException('Error during get payment info');
                                    }
                                }
                                break;
                        }

                        if (!empty($actual_payment)) {
                            $this->handlePayment($actual_payment);
                        }
                        break;
                }
            }
            return array(
                'message' => 'SUCCESS',
            );
        }
    }

    /**
     * @param $transaction_data
     * @param $request
     * @return array
     * @throws waException
     */
    protected function callbackHandlerRedirect($transaction_data, $request)
    {
        $params = '';
        if ((ifset($request['action']) == 'PaymentFail') || (waRequest::get('result') == 'fail')) {
            $type = waAppPayment::URL_FAIL;
        } else {
            if (isset($transaction_data['order_id']) && !empty($transaction_data['order_id'])) {
                $fields = array(
                    'plugin' => $this->id,
                    'app_id' => $this->app_id,
                    'merchant_id' => $this->merchant_id,
                    'error' => null,
                );
                $fields['order_id'] = filter_var($transaction_data['order_id'], FILTER_SANITIZE_NUMBER_INT);
                $transaction_model = new waTransactionModel();
                $transaction = $transaction_model->getByFields($fields);
                if (isset(end($transaction)['native_id']) && !empty(end($transaction)['native_id'])) {
                    $native_id = end($transaction)['native_id'];
                    $payment = $this->getPaymentInfo($native_id);
                    if ($payment['status'] == 'pending' || $payment['status'] == 'canceled') {
                        $transaction_data['error'] = _w('Вы отказались от совершения платежа. Повторите попытку позднее, пожалуйста.'); // max length 255 characters
                        $transaction = $this->saveTransaction($transaction_data);
                        $params = isset($transaction['id']) ? '&transaction_id=' .  $transaction['id'] : '';

                        $type = waAppPayment::URL_DECLINE;
                    }
                }
            }
        }

        if (!isset($type)) {
            $type = waAppPayment::URL_SUCCESS;
        }

        return array(
            'redirect' => $this->getAdapter()->getBackUrl($type, $transaction_data) . $params,
        );
    }

    /**
     * @param $data
     * @return array|null
     * @throws waException
     * @throws waPaymentException
     */
    protected function getRefundReceiptData($data)
    {
        $receipt = null;
        $items = ifset($data, 'refund_items', array());
        if ($this->receipt && $items) {
            $customer = array();
            #search related transaction
            $search = array(
                'plugin'      => $this->id,
                'app_id'      => $this->app_id,
                'merchant_id' => $this->merchant_id,
                'order_id'    => $data['transaction']['order_id'],
                'native_id'   => $data['transaction']['native_id'],
                'state'       => self::STATE_VERIFIED,
            );

            $transactions = self::getTransactionsByFields($search);

            foreach ($transactions as $transaction) {
                if (!empty($transaction['raw_data']['email'])) {
                    $customer['email'] = $transaction['raw_data']['email'];
                }
                if (!empty($transaction['raw_data']['phone'])) {
                    $customer['phone'] = $transaction['raw_data']['phone'];
                }
            }

            $receipt = array(
                'customer' => $customer,
            );
            $receipt['items'] = array();

            $currency = ifset($data, 'transaction', 'currency_id', null);
            foreach ($items as $item) {
                $item['amount'] = round($item['price'], 2) - round(ifset($item['discount'], 0.0), 2);
                $receipt['items'][] = $this->formatReceiptItem($item, $currency);
                unset($item);
            }

            $receipt += $customer;
        }
        return $receipt;
    }


    /**
     * @see https://kassa.yandex.ru/developers/api#%D1%81%D0%BE%D0%B7%D0%B4%D0%B0%D0%BD%D0%B8%D0%B5_%D0%BF%D0%BB%D0%B0%D1%82%D0%B5%D0%B6%D0%B0_receipt
     * @param waOrder $order
     * @return array|null
     * @throws waPaymentException
     */
    protected function getReceiptData(waOrder $order)
    {
        if (!$this->receipt) {
            return null;
        }

        // Customer contacts
        $customer = array(
            'email' => $order->getContactField('email'),
        );
        $phone = $order->getContactField('phone');
        if ($phone) {
            //TODO format ITU-T E.164
            $customer['phone'] = sprintf('%s', preg_replace('@^8@', '7', $phone));
        }

        $customer = array_filter($customer);
        if (empty($customer)) {
            throw new waPaymentException('Empty customer data');
        }

        $receipt = array(
            'customer' => $customer,
            'items'    => array(),
        );
        if ($this->tax_system_code) {
            $receipt['tax_system_code'] = $this->tax_system_code;
        }

        $receipt['items'] = $this->getReceiptItems($order);

        #shipping
        if (($order->shipping) || strlen($order->shipping_name)) {
            $item = array(
                'quantity'     => 1,
                'name'         => mb_substr($order->shipping_name, 0, 128),
                'amount'       => round($order->shipping, 2),
                'tax_rate'     => $order->shipping_tax_rate,
                'tax_included' => ($order->shipping_tax_included !== null) ? $order->shipping_tax_included : true,
                'type'         => 'shipping',
            );
            $receipt['items'][] = $this->formatReceiptItem($item, $order->currency);
        }

        if (empty($receipt['items'])) {
            throw new waPaymentException('Empty order items');
        }

        $receipt += $customer;
        return $receipt;
    }

    protected function getReceiptItems(waOrder $order)
    {
        $receipt_items = [];

        foreach ($order->items as $item) {
            $quantity = (int)ifset($item, 'quantity', 0);
            if ($quantity <= 0) {
                continue;
            }
            $item['amount'] = round($item['price'], 2) - round(ifset($item['discount'], 0.0), 2);

            // possible splitting items into array of items
            $items = [$item];

            // "Честный знак" marking code for product item leads to splitting by 'quantity'
            if ($item['type'] === 'product') {
                $values = $this->getChestnyznakCodeValues($item['product_codes']);
                if ($values) {
                    $items = $this->splitItem($item, $values);
                }
            }

            foreach ($items as $it) {
                $receipt_items[] = $this->formatReceiptItem($it, $order->currency);
            }

            unset($item);
        }

        return $receipt_items;
    }

    /**
     * Split one product item to several items because chestnyznak marking code must be related for single product instance
     * Extend each new item with 'chestnyznak' value from $values
     * Invariant $item['quantity'] === count($values)
     * @param array $item - order item
     * @param array $values - chestnyznak values
     * @return array[] - array of items. Each item has 'product_code'
     */
    protected function splitItem(array $item, array $values)
    {
        $quantity = (int)ifset($item, 'quantity', 0);
        $items = [];
        for ($i = 0; $i < $quantity; $i++) {
            $value = isset($values[$i]) ? $values[$i] : '';
            $item['chestnyznak'] = $value;
            $item['quantity'] = 1;
            $items[] = $item;
        }
        return $items;
    }

    /**
     * @see https://kassa.yandex.ru/developers/api#%D1%81%D0%BE%D0%B7%D0%B4%D0%B0%D0%BD%D0%B8%D0%B5_%D0%BF%D0%BB%D0%B0%D1%82%D0%B5%D0%B6%D0%B0_receipt_items
     * @param array  $item
     * @param string $currency
     * @return array
     * @throws waPaymentException
     */
    protected function formatReceiptItem($item, $currency)
    {
        if (isset($item['tax_included']) && empty($item['tax_included']) && !empty($item['tax_rate'])) {
            $item['amount'] += round(floatval($item['tax_rate']) * $item['amount'] / 100.0, 2);
        }

        $type = ifset($item['type']);
        switch ($type) {
            case 'shipping':
                $item['payment_subject_type'] = $this->payment_subject_type_shipping;
                break;
            case 'service':
                $item['payment_subject_type'] = $this->payment_subject_type_service;
                break;
            case 'product':
            default:
                $item['payment_subject_type'] = $this->payment_subject_type_product;
                break;
        }

        $result = [
            //Название товара (не более 128 символов).
            'description'     => mb_substr($item['name'], 0, 128),
            //Количество товара. Максимально возможное значение зависит от модели вашей онлайн-кассы.
            'quantity'        => $item['quantity'],
            'amount'          => array(
                'value'    => number_format(round($item['amount'], 2), 2, '.', ''),
                'currency' => $currency,
            ),
            'vat_code'        => $this->getTaxId($item),
            //Признак предмета расчета.
            'payment_subject' => $item['payment_subject_type'],
            //Признак способа расчета.
            'payment_mode'    => $this->payment_method_type,
        ];

        // Код товара — уникальный номер, который присваивается экземпляру товара при маркировке
        // Тут идет конвертация из DataMatrix кода (Честный знак) в 1162 тег код для ККТ
        if (isset($item['chestnyznak'])) {
            $fiscal_code = $this->convertToFiscalCode($item['chestnyznak']);
            if ($fiscal_code) {
                $result['product_code'] = $fiscal_code;
            }
        }

        return $result;
    }

    /**
     * Конвертация из DataMatrix кода (Честный знак) в 1162 тег код для ККТ
     * @param $uid
     * @return bool|string
     */
    protected function convertToFiscalCode($uid)
    {
        if (!class_exists('shopChestnyznakPluginCodeParser')) {
            return false;
        }
        return shopChestnyznakPluginCodeParser::convertToFiscalCode($uid);
    }

    /**
     * @see https://kassa.yandex.ru/developers/payments/54fz/parameters-values#vat-codes
     * @param $item
     * @return int
     * @throws waPaymentException
     */
    protected function getTaxId($item)
    {
        $id = 1;
        switch ($this->taxes) {
            case 'no':
                # 1 — без НДС;
                $id = 1;
                break;
            case 'map':
                $tax_included = !isset($item['tax_included']) ? true : $item['tax_included'];
                $rate = ifset($item['tax_rate']);
                if (in_array($rate, array(null, false, ''), true)) {
                    $rate = -1;
                }

                if (!$tax_included && $rate > 0) {
                    throw new waPaymentException('Фискализация товаров с налогом, не включённым в стоимость, недоступна. Обратитесь в службу поддержки.');
                }

                switch ($rate) {
                    case 18:
                    case 20:
                        if ($tax_included) {
                            # 4 — НДС чека по ставке 20% после 01.01.2019;
                            $id = 4;
                        } else {
                            #6 — НДС чека по расчетной ставке 20/120 после 01.01.2019;
                            $id = 6;
                        }
                        break;
                    case 10:
                        if ($tax_included) {
                            # 3 — НДС чека по ставке 10%;
                            $id = 3;
                        } else {
                            #  5 — НДС чека по расчетной ставке 10/110;
                            $id = 5;
                        }
                        break;
                    case 0:
                        # 2 — НДС по ставке 0%;
                        $id = 2;
                        break;
                    default:
                        # 1 — без НДС;
                        $id = 1;
                        break;
                }
                break;
        }
        return $id;
    }

    /**
     * @param array $transaction_raw_data
     * @return array
     */
    protected function formalizeData($transaction_raw_data)
    {
        $data = parent::formalizeData($transaction_raw_data);
        $data += array(
            'app_id' => $this->app_id,
        );
        $data['native_id'] = ifset($transaction_raw_data, 'id', null);
        $data['amount'] = ifset($transaction_raw_data, 'amount', 'value', null);
        $data['currency_id'] = ifset($transaction_raw_data, 'amount', 'currency', null);
        $data['order_id'] = ifset($transaction_raw_data, 'metadata', 'order_id', null);
        $data['type'] = null;
        $view = array();
        switch (ifset($transaction_raw_data, 'status', 'unknown')) {
            case 'pending':
                $data['state'] = self::STATE_VERIFIED;
                $view[] = 'Ожидание оплаты';
                break;
            case 'waiting_for_capture':
                $data['state'] = self::STATE_AUTH;
                $data['type'] = self::OPERATION_AUTH_ONLY;
                $view[] = 'Ожидание оплаты';
                break;
            case 'succeeded':
                if (isset($transaction_raw_data['refunded_amount']['value'])) {
                    $refunded = floatval($transaction_raw_data['refunded_amount']['value']);
                } else {
                    $refunded = null;
                }
                if (!empty($refunded)) {
                    if (($transaction_raw_data['refunded_amount']['value'] == $transaction_raw_data['amount']['value'])
                        && empty($transaction_raw_data['refundable'])
                    ) {
                        $data['state'] = self::STATE_REFUNDED;
                    } else {
                        $data['state'] = self::STATE_PARTIAL_REFUNDED;
                    }

                    if (isset($transaction_raw_data['refund'])) {
                        $data['amount'] = floatval($transaction_raw_data['refund']['amount']['value']);
                        if (!empty($transaction_raw_data['refund']['description'])) {
                            $view[] = sprintf(
                                'Основание для возврата денег клиенту: %s',
                                htmlentities($transaction_raw_data['refund']['description'], ENT_QUOTES, 'utf-8')
                            );
                        }

                        if (!empty($transaction_raw_data['refund']['requestor'])) {
                            switch ($transaction_raw_data['refund']['requestor']['type']) {
                                case 'merchant':
                                    $view[] = sprintf(
                                        'Возврат инициирован магазином: #%s',
                                        htmlentities($transaction_raw_data['refund']['requestor']['account_id'], ENT_QUOTES, 'utf-8')
                                    );
                                    break;
                                case 'third_party_client':
                                    $view[] = sprintf(
                                        'Возврат инициирован приложением: #%s (%s)',
                                        htmlentities($transaction_raw_data['refund']['requestor']['client_id'], ENT_QUOTES, 'utf-8'),
                                        htmlentities(ifset($transaction_raw_data['refund']['requestor']['client_name']), ENT_QUOTES, 'utf-8')
                                    );
                                    break;
                            }


                        }

                    } else {
                        $data['amount'] = max(0, $data['amount'] - $refunded);
                    }


                    $data['type'] = self::OPERATION_REFUND;

                    $value = wa_currency(
                        (float)$transaction_raw_data['refunded_amount']['value'],
                        $transaction_raw_data['refunded_amount']['currency']
                    );
                    $view[] = sprintf('Выполнен возврат %s', $value);

                    if (empty($transaction_raw_data['refundable'])
                        || ($transaction_raw_data['refunded_amount']['value'] != $transaction_raw_data['amount']['value'])
                    ) {
                        $value = wa_currency(
                            (float)$transaction_raw_data['amount']['value'],
                            $transaction_raw_data['amount']['currency']
                        );
                        $view[] = sprintf('Из общей суммы %s', $value);
                    }

                } else {
                    $data['state'] = self::STATE_CAPTURED;
                    $data['type'] = true ? self::OPERATION_AUTH_CAPTURE : self::OPERATION_CAPTURE;

                    $payment_method = ifset($transaction_raw_data, 'payment_method', array());

                    switch (ifset($payment_method, 'type', null)) {
                        case 'bank_card':
                            $template = 'Оплата банковской картой %s: %s %s/%s';
                            $card = ifset($payment_method, 'card', array());
                            $card += array(
                                'first6'       => '******',
                                'last4'        => '****',
                                'expiry_month' => '--',
                                'expiry_year'  => '----',
                                'card_type'    => 'Unknown',
                            );
                            $card['number'] = $card['first6'].'******'.$card['last4'];
                            $card['number'] = chunk_split($card['number'], 4, ' ');
                            $view[] = sprintf($template, $card['card_type'], $card['number'], $card['expiry_month'], $card['expiry_year']);
                            break;
                        case 'yandex_money':
                            if (!empty($payment_method['account_number'])) {
                                $template = 'Аккаунт: %s';
                                $view[] = sprintf($template, $payment_method['account_number']);
                            }
                            break;
                        default:
                            if (!empty($payment_method['title'])) {
                                $view[] = $payment_method['title'];
                            }
                            break;
                    }

                }
                break;
            case 'canceled':
                if (isset($transaction_raw_data['refunded_amount']['value'])) {
                    $refunded = floatval($transaction_raw_data['refunded_amount']['value']);
                } else {
                    $refunded = null;
                }
                if (!empty($refunded)) {
                    if (($transaction_raw_data['refunded_amount']['value'] == $transaction_raw_data['amount']['value'])
                        && empty($transaction_raw_data['refundable'])
                    ) {
                        $data['state'] = self::STATE_REFUNDED;
                    } else {
                        $data['state'] = self::STATE_PARTIAL_REFUNDED;
                    }
                    $data['type'] = self::OPERATION_REFUND;
                    $value = wa_currency(
                        (float)$transaction_raw_data['refunded_amount']['value'],
                        $transaction_raw_data['refunded_amount']['currency']
                    );
                    $view[] = sprintf('Выполнен возврат %s', $value);

                    $value = wa_currency(
                        (float)$transaction_raw_data['amount']['value'],
                        $transaction_raw_data['amount']['currency']
                    );
                    $view[] = sprintf('Из общей суммы %s', $value);

                } else {
                    $data['state'] = self::STATE_VERIFIED;
                    $data['type'] = self::OPERATION_CHECK;
                }
                $view[] = self::getCancelDescription(ifset($transaction_raw_data['cancellation_details']));
                break;
            default:
                $view[] = 'DEBUG: status='.ifset($transaction_raw_data, 'status', 'unknown');
                break;
        }

        if (!empty($transaction_raw_data['test'])) {
            $view[] = 'Тестовая операция';
        }

        if ($view) {
            $data['view_data'] = implode('; ', array_filter($view, 'strlen'));
        }
        return $data;
    }

    protected static function getCancelDescription($details)
    {
        $reasons = array(
            '3d_secure_failed'              => 'Не пройдена аутентификация по 3-D Secure. Клиенту следует повторить платёж, обратиться в банк за уточнениями или использовать другое платёжное средство.',
            'call_issuer'                   => 'Оплата данным платёжным средством отклонена по неизвестным причинам. Клиенту следует обратиться в организацию, выпустившую платёжное средство.',
            'card_expired'                  => 'Истёк срок действия банковской карты. Клиенту следует использовать другое платёжное средство.',
            'country_forbidden'             => 'Нельзя заплатить банковской картой, выпущенной в этой стране. Клиенту следует использовать другое платёжное средство. Вы можете настроить ограничения на оплату иностранными банковскими картами.',
            'fraud_suspected'               => 'Платёж заблокирован из-за подозрения в мошенничестве. Клиенту следует использовать другое платёжное средство.',
            'general_decline'               => 'Причина не детализирована. Клиенту следует обратиться к инициатору отмены платежа за уточнением подробностей.',
            'identification_required'       => 'Превышены ограничения на платежи для кошелька в «Яндекс.Деньгах». Клиенту следует идентифицировать кошелёк или выбрать другое платёжное средство.',
            'insufficient_funds'            => 'Недостаточно денег для оплаты. Клиенту следует пополнить баланс или использовать другое платёжное средство.',
            'invalid_card_number'           => 'Неправильно указан номер карты. Клиенту следует повторить платёж и ввести корректные данные.',
            'invalid_csc'                   => 'Неправильно указан код CVV2 (CVC2, CID). Клиенту следует повторить платёж и ввести корректные данные.',
            'issuer_unavailable'            => 'Организация, выпустившая платёжное средство, недоступна. Клиенту следует повторить платёж позже или использовать другое платёжное средство.',
            'payment_method_limit_exceeded' => 'Исчерпан лимит платежей для данного платёжного средства или вашего аккаунта продавца. Клиенту следует повторить платёж на следующий день или использовать другое платёжное средство',
            'payment_method_restricted'     => 'Запрещены операции данным платёжным средством (например, карта заблокирована из-за утери, кошелёк — из-за взлома мошенниками). Клиенту следует обратиться в организацию, выпустившую платёжное средство.',
            'permission_revoked'            => 'Нельзя выполнить безакцептное списание: клиент отозвал разрешение на повторы платежей. Если клиент ещё хочет заплатить, вам необходимо создать новый платёж, а клиенту — подтвердить оплату.',
        );

        switch ($details['party']) {
            case 'merchant':
                $view = 'Продавец товаров и услуг (вы)';
                break;
            case 'yandex_checkout':
                $view = 'Яндекс.Касса';
                break;
            case 'payment_network':
                $view = '«Внешние» участники платёжного процесса';
                break;
            default:
                $view = '';
                break;
        }

        if (isset($reasons[$details['reason']])) {
            $view .= sprintf(' Причина: %s', $reasons[$details['reason']]);
        }

        return $view;
    }

    /**
     * @see https://kassa.yandex.ru/developers/payments/54fz/parameters-values#payment-subject
     * @return array
     */
    public static function settingsPaymentSubjectTypeOptions()
    {
        return array(
            'commodity'             => 'товар',
            'excise'                => 'подакцизный товар',
            'job'                   => 'работа',
            'service'               => 'услуга',
            'gambling_bet'          => 'ставка в азартной игре',
            'gambling_prize'        => 'выигрыш в азартной игре',
            'lottery'               => 'лотерейный билет',
            'lottery_prize'         => 'выигрыш в лотерею',
            'intellectual_activity' => 'результаты интеллектуальной деятельности',
            'payment'               => 'платёж',
            'agent_commission'      => 'агентское вознаграждение',
            'property_right'        => 'имущественные права',
            'non_operating_gain'    => 'внереализационный доход',
            'insurance_premium'     => 'страховой сбор',
            'sales_tax'             => 'торговый сбор',
            'resort_fee'            => 'курортный сбор',
            'composite'             => 'несколько вариантов',
            'another'               => 'другое',
        );
    }

    /**
     * @see https://kassa.yandex.ru/developers/payments/54fz/parameters-values#tax-systems
     * @return array
     * @throws waException
     */
    public function settingsTaxOptions()
    {
        $disabled = !$this->getAdapter()->getAppProperties('taxes');
        return array(
            array(
                'value' => 0,
                'title' => 'Не передавать',
            ),
            array(
                'value'    => 1,
                'title'    => 'Общая система налогообложения',
                'disabled' => $disabled,
            ),
            array(
                'value'    => 2,
                'title'    => 'Упрощённая (УСН, доходы)',
                'disabled' => $disabled,
            ),
            array(
                'value'    => 3,
                'title'    => 'Упрощённая (УСН, доходы минус расходы)',
                'disabled' => $disabled,
            ),
            array(
                'value'    => 4,
                'title'    => 'Единый налог на вменённый доход (ЕНВД)',
                'disabled' => $disabled,
            ),
            array(
                'value'    => 5,
                'title'    => 'Единый сельскохозяйственный налог (ЕСН)',
                'disabled' => $disabled,
            ),
            array(
                'value'    => 6,
                'title'    => 'Патентная система налогообложения',
                'disabled' => $disabled,
            ),
        );
    }

    /**
     * @return array
     * @throws waException
     */
    public function taxesOptions()
    {
        $disabled = !$this->getAdapter()->getAppProperties('taxes');
        return array(
            array(
                'value' => 'no',
                'title' => 'НДС не облагается',
            ),
            array(
                'value'    => 'map',
                'title'    => 'Передавать ставки НДС по каждой позиции',
                'disabled' => $disabled,
            ),
        );
    }

    /**
     * @return array
     * @throws waException
     */
    public function settingsCurrencyOptions()
    {
        $available = array();
        $app_config = wa($this->app_id)->getConfig();
        if (method_exists($app_config, 'getCurrencies')) {
            $currencies = $app_config->getCurrencies();
            foreach ($currencies as $code => $c) {
                if (in_array($code, self::$currencies)) {
                    $available[] = array(
                        'value'       => $code,
                        'title'       => sprintf('%s %s', $c['code'], $c['title']),
                        'description' => $c['sign'],
                    );
                }
            }
        } else {
            $code = 'RUB';
            $c = waCurrency::getInfo($code);
            $available[] = array(
                'value'       => $code,
                'title'       => sprintf('%s %s', $c['code'], $c['title']),
                'description' => $c['sign'],
            );
        }
        return $available;
    }

    /**
     * @link https://kassa.yandex.ru/developers/payment-methods/overview
     * @return array
     */
    public static function settingsCustomerPaymentTypeOptions()
    {
        $options = array(
            'bank_card'      => array(
                'value'     => 'bank_card',
                'title'     => 'Банковская карта',
                'ttl'       => '1 час',
                'hold'      => '7 дней',
                'code'      => 'AC',
                'refund'    => true,
                'recurrent' => true,
            ),
            'apple_pay'      => array(
                'value'     => 'apple_pay',
                'title'     => 'Apple Pay',
                'disabled'  => 'disabled',
                'ttl'       => '1 час',
                'hold'      => '7 дней',
                'code'      => 'AC',
                'refund'    => true,
                'recurrent' => false,
            ),
            'google_pay'     => array(
                'value'     => 'google_pay',
                'title'     => 'Google Pay',
                'disabled'  => 'disabled',
                'ttl'       => '1 час',
                'hold'      => '7 дней',
                'code'      => 'AC',
                'refund'    => true,
                'recurrent' => false,
            ),
            //Электронные деньги
            'yandex_money'   => array(
                'value'     => 'yandex_money',
                'title'     => 'Яндекс.Деньги',
                'ttl'       => '1 час',
                'hold'      => '7 дней',
                'code'      => 'PC',
                'refund'    => true,
                'recurrent' => true,
                'group'     => 'Электронные деньги',
            ),
            'qiwi'           => array(
                'value'     => 'qiwi',
                'title'     => 'QIWI Кошелек',
                'ttl'       => '1 час',
                'hold'      => '6 часов',
                'code'      => 'QW',
                'refund'    => true,
                'recurrent' => false,
                'group'     => 'Электронные деньги',
            ),
            'webmoney'       => array(
                'value'     => 'webmoney',
                'title'     => 'WebMoney',
                'ttl'       => '1 час',
                'hold'      => '6 часов',
                'code'      => 'WM',
                'refund'    => true,
                'recurrent' => false,
                'group'     => 'Электронные деньги',
            ),
            'wechat'         => array(
                'value'     => 'wechat',
                'title'     => 'WeChat',
                'ttl'       => '2 минуты',
                'hold'      => '—',
                'code'      => 'WP',
                'refund'    => true,
                'recurrent' => false,
                'group'     => 'Электронные деньги',
            ),
            //Интернет-банкинг
            'sberbank'       => array(
                'value'     => 'sberbank',
                'title'     => 'Сбербанк Онлайн',
                'ttl'       => '8 часов',
                'hold'      => '6 часов',
                'code'      => 'SB',
                'refund'    => true,
                'recurrent' => false,
                'group'     => 'Интернет-банкинг',
            ),
            'alfabank'       => array(
                'value'     => 'alfabank',
                'title'     => 'Альфа-Клик',
                'ttl'       => '8 часов',
                'hold'      => '6 часов',
                'code'      => 'AB',
                'refund'    => true,
                'recurrent' => false,
                'group'     => 'Интернет-банкинг',
            ),
            'tinkoff_bank'   => array(
                'value'     => 'tinkoff_bank',
                'title'     => 'Тинькофф Банк',
                'ttl'       => '1 час',
                'hold'      => '6 часов',
                'code'      => 'TB',
                'refund'    => true,
                'recurrent' => false,
                'group'     => 'Интернет-банкинг',
            ),
            //B2B-платежи
            'b2b_sberbank'   => array(
                'value'     => 'b2b_sberbank',
                'title'     => 'Сбербанк Бизнес Онлайн',
                'disabled'  => 'disabled', //нужны данные о налоговых ставках
                'ttl'       => '72 часа',
                'hold'      => '—',
                'code'      => '2S',
                'refund'    => false,
                'recurrent' => false,
                'group'     => 'B2B-платежи',
            ),
            //Другие способы
            'mobile_balance' => array(
                'value'     => 'mobile_balance',
                'title'     => 'Баланс телефона',
                'disabled'  => 'disabled', //нужен номер телефона
                'ttl'       => '1 час',
                'hold'      => '6 часов',
                'code'      => 'MC',
                'refund'    => true,
                'recurrent' => false,
                'group'     => 'Другие способы',
            ),
            'cash'           => array(
                'value'     => 'cash',
                'title'     => 'Наличные',
                'ttl'       => 'Без ограничений',
                'hold'      => '6 часов',
                'code'      => 'GP',
                'refund'    => false,
                'recurrent' => false,
                'group'     => 'Другие способы',
            ),
            'installments'   => array(
                'value'     => 'installments',
                'title'     => 'Заплатить по частям (кредит)',
                'ttl'       => '8 часов',
                'hold'      => '6 часов',
                'code'      => 'CR',
                'refund'    => true,
                'recurrent' => false,
                'group'     => 'Другие способы',
            ),
        );

        foreach ($options as $id => $option) {
            if (!empty($option['disabled'])) {
                unset($options[$id]);
            }
        }

        return $options;
    }

    public static function settingsPaymentTypeOptions()
    {
        return array(

                ''         => array(
                    'value' => '',
                    'title' => 'На выбор покупателя после перехода на сайт «Яндекс.Кассы» (рекомендуется)',
                ),
                'customer' => 'На выбор покупателя до перехода на сайт «Яндекс.Кассы»',
            ) + self::settingsCustomerPaymentTypeOptions();
    }


    public function allowedCurrency()
    {
        $currency = $this->merchant_currency ? $this->merchant_currency : reset(self::$currencies);
        if (!in_array($currency, self::$currencies)) {
            $currency = reset(self::$currencies);
        }
        return $currency;
    }

    public function supportedOperations()
    {
        $operations = array(
            self::OPERATION_AUTH_CAPTURE,
            self::OPERATION_REFUND,
            self::OPERATION_CANCEL,
        );

        if ($this->manual_capture) {
            $operations[] = self::OPERATION_AUTH_ONLY;
        }
        return $operations;
    }

    public function isRefundAvailable($order_id)
    {
        $transaction = parent::isRefundAvailable($order_id);
        if ($transaction) {
            try {
                $payment = $this->getPaymentInfo($transaction['native_id']);
                //check that $transaction is refundable
                if (empty($payment['refundable'])) {
                    $transaction = false;
                }
                if (isset($payment['refunded_amount'])) {
                    $transaction['refunded_amount'] = floatval($payment['refunded_amount']['value']);
                    if (isset($payment['amount'])) {
                        $transaction['amount'] = floatval($payment['amount']['value']);
                        $transaction['amount'] = max(0, $transaction['amount'] - $transaction['refunded_amount']);
                    }
                }
            } catch (waException $ex) {
                $transaction = false;
            }
        }
        return $transaction;
    }

    public function refund($transaction_raw_data)
    {
        try {
            $transaction_raw_data = $this->getRefundTransactionData($transaction_raw_data);
            $transaction = $transaction_raw_data['transaction'];
            $payment = $this->getPaymentInfo($transaction['native_id']);

            if (!empty($payment['refundable'])) {
                $refund_amount = $transaction_raw_data['refund_amount'];
                $data = array(
                    'amount'     => array(
                        'value'    => number_format(round($refund_amount, 2), 2, '.', ''),
                        'currency' => $transaction['currency_id'],
                    ),
                    'payment_id' => $transaction['native_id'],
                );
                $receipt = $this->getRefundReceiptData($transaction_raw_data);
                if ($receipt) {
                    $data['receipt'] = $receipt;
                }

                $params = array(
                    'order_id'        => $transaction['order_id'],
                    'amount'          => $transaction['amount'],
                    'refunded_amount' => isset($transaction['refunded_amount']) ? $transaction['refunded_amount'] : false,
                );

                if ($refund_amount < $transaction['amount']) {
                    $params['refundable'] = true;
                } else {
                    $params['parent_id'] = $transaction['id'];
                }

                $data['%params'] = $params;

                $data = $this->apiQuery('refunds', $data);
                return array(
                    'result'      => 0,
                    'data'        => $data,
                    'description' => null,
                );
            } else {
                throw new waPaymentException('Refund not available');
            }
        } catch (waException $ex) {
            $message = sprintf("Error occurred during %s: %s", __METHOD__, $ex->getMessage());
            self::log($this->id, $message);
            return array(
                'result'      => -1,
                'data'        => null,
                'description' => $ex->getMessage(),
            );
        }
    }

    public function callbackMatchSettings($settings)
    {
        $result = !empty($settings['shop_id']);
        if ($result) {

            $result = intval($result);
            switch (ifset($settings['payment_type'])) {
                case 'installments':
                    $result += 2;
                    break;
                case 'customer':
                    if (!empty($settings['customer_payment_type']['installments'])) {
                        $result += 1;
                    } else {
                        $result = false;
                    }
                    break;
            }
        }
        return $result;
    }

    /**
     * @param array<int, array> $item_product_codes - array of product code records indexed by id of record
     *  id => [
     *      int      'id'
     *      string   'code'
     *      string   'name' [optional]
     *      string   'icon' [optional]
     *      string   'logo' [optional]
     *      string[] 'values' - promo code item value for each instance of product item
     *  ]
     * @return array - chestnyznak values
     */
    protected function getChestnyznakCodeValues(array $item_product_codes)
    {
        $values = [];
        foreach ($item_product_codes as $product_code) {
            if (isset($product_code['code']) && $product_code['code'] === self::CHESTNYZNAK_PRODUCT_CODE) {
                if (isset($product_code['values'])) {
                    $values = $product_code['values'];
                    break;
                }
            }
        }

        return $values;
    }

    public static function getCreditInfo($amount, $app_id = null, $id = null, $selector = null)
    {
        if (empty($id)) {
            $id = '*';
        } elseif ($id === 'yandexkassa') {
            $id = '*';
        }
        $instance = new self($id);
        $instance->type = self::PLUGIN_TYPE;
        $instance->app_id = $app_id;
        $instance->id = 'yandexkassa';

        if ($id === '*') {
            $instance->key = array($instance, 'callbackMatchSettings');
        }
        $instance->init();

        $amount = intval($amount * 100);

        $result = '';
        if (empty($selector)) {
            $dom_id = sprintf('wa_yandexkassa_%d', $instance->shop_id);
            $result .= /** @lang html */
                <<<HTML
<div id="{$dom_id}"><!-- Yandex.Kassa placeholder --></div>
HTML;
            $selector = '#'.$dom_id;
        }

        $result .= /** @lang html */
            <<<HTML
<script src="https://static.yandex.net/kassa/pay-in-parts/ui/v1"></script>
<script>
const \$checkoutCreditUI = YandexCheckoutCreditUI({
    shopId: '{$instance->shop_id}',
    sum: {$amount}
});
const checkoutCreditText = \$checkoutCreditUI({
    type: 'info',
    domSelector: '{$selector}'
});

</script>
HTML;


        return $result;
    }
}
