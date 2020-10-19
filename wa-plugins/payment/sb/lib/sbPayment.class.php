<?php

use phpseclib\File\X509;

/**
 * Class sbPayment
 *
 * All amounts must be in the minimum currency units
 * @property-read string  $userName
 * @property-read string  $password
 * @property-read boolean $TESTMODE
 *
 * @property-read string  $currency_id
 * @property-read int     $sessionTimeoutSecs
 *
 * @property-read boolean $two_step
 * @property-read boolean $cancel  Need to activate in Sberbank
 *
 * @property-read boolean $fiscalization
 * @property-read string  $tax_system
 *
 * @property-read array   $payment_method
 * @property-read array   $payment_subject_product
 * @property-read array   $payment_subject_service
 * @property-read array   $payment_subject_shipping
 *
 * @property-read boolean $credit
 * @property-read string  $credit_type
 *
 * @link https://developer.sberbank.ru/doc
 * @link https://securepayments.sberbank.ru/wiki/doku.php
 */
class sbPayment extends waPayment implements waIPaymentCapture, waIPaymentCancel, waIPaymentRefund, waIPaymentRecurrent
{
    const SB_ORDER_CREATE = 0;//заказ зарегистрирован, но не оплачен;
    const SB_ORDER_HOLD = 1;//предавторизованная сумма удержана (для двухстадийных платежей);
    const SB_ORDER_PAID = 2;//проведена полная авторизация суммы заказа;
    const SB_ORDER_CANCEL = 3;//авторизация отменена;
    const SB_ORDER_REFUND = 4;//по транзакции была проведена операция возврата;
    const SB_ORDER_PROCESSING = 5;//инициирована авторизация через сервер контроля доступа банка-эмитента;
    const SB_ORDER_DECLINE = 6;//авторизация отклонена.

    const URL_ORDER_REGISTER = 'URL_ORDER_REGISTER';
    const URL_ORDER_PRE_REGISTER = 'URL_ORDER_PRE_REGISTER';
    const URL_ORDER_STATUS = 'URL_ORDER_STATUS';
    const URL_PAYMENT_COMPLETE = 'URL_PAYMENT_COMPLETE';
    const URL_PAYMENT_CANCEL = 'URL_PAYMENT_CANCEL';
    const URL_PAYMENT_REFUND = 'URL_PAYMENT_REFUND';
    const URL_PAYMENT_ORDER_BINDING = 'URL_PAYMENT_ORDER_BINDING';

    protected $receipt_number;

    const CHESTNYZNAK_PRODUCT_CODE = 'chestnyznak';

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
     * @param array   $payment_form_data
     * @param waOrder $order_data
     * @param bool    $auto_submit
     * @return string
     */
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        try {
            if ($auto_submit && $this->TESTMODE) {
                $auto_submit = false;
            }

            $order_data = waOrder::factory($order_data);
            $redirect_url = null;

            $transactions = $this->getAvailableTransactions($order_data->id);
            if (isset($transactions[waPayment::TRANSACTION_PAYMENT])) {
                $create_transaction = $transactions[waPayment::TRANSACTION_PAYMENT];
                $transaction_data = $this->getGatewayTransactionStatus($order_data['order_id'], $create_transaction);

                if ($transaction_data['amount'] != $order_data['total']) {
                    return 'Сумма оплаты отличается от суммы заказа';
                } elseif (!empty($transaction_data['callback_method'])) {
                    $this->handleTransaction($transaction_data);
                    return 'Состояние платежа изменилось — обновите страницу.';
                } elseif (!empty($create_transaction['raw_data']['formUrl'])) {
                    return $this->viewForm($create_transaction['raw_data']['formUrl'], $auto_submit);
                } else {
                    return 'Ошибка получения формы оплаты.';
                }

            } else {
                // It's a new order, we need to create new transaction
                $storage = wa()->getStorage();
                $storage->write('sb_order', $order_data);
                $get_query = array(
                    'register_order' => 1,
                    'app_id' => $this->app_id,
                    'merchant_id' => $this->merchant_id,
                    'orderNumber' => $this->getOrderNumber($order_data['order_id']),
                );
                $redirect_url = wa()->getRootUrl() . 'payments.php/sb/?' . http_build_query($get_query);
                return $this->viewForm($redirect_url, $auto_submit);
            }
        } catch (waPaymentException $ex) {
            if ($this->credit && ($ex->getCode() == 6)) {
                if (!empty($create_transaction['raw_data']['formUrl'])) {
                    return $this->viewForm($create_transaction['raw_data']['formUrl'], $auto_submit);
                }
            }
            return $ex->getMessage();
        } catch (waException $ex) {
            $log = array(
                'method'    => __METHOD__,
                'exception' => get_class($ex),
                'message'   => $ex->getMessage(),
                'code'      => $ex->getCode(),
                'trace'     => $ex->getTraceAsString(),
            );
            static::log($this->id, $log);
            return 'При обработке платежа произошла ошибка. Попробуйте позднее или обратитесь к администратору сайта.';
        }
    }

    /**
     * @see https://securepayments.sberbank.ru/wiki/doku.php/integration:api:rest:requests:reverse
     * @param $transaction_raw_data
     * @return array|null
     */
    public function cancel($transaction_raw_data)
    {
        try {
            //Need to activate "CANCEL" in Sberbank
            if (!$this->cancel) {
                return null;
            }

            $transaction = $transaction_raw_data['transaction'];

            $request = array(
                'orderId'  => $transaction['native_id'],
                'language' => $this->getLanguage(),
            );

            $this->sendRequest(self::URL_PAYMENT_CANCEL, $request);

            $transaction_data = $this->formalizeRequestResult(self::OPERATION_CANCEL, $transaction);

            return array(
                'result'      => 0,
                'description' => '',
                'data'        => $this->saveExtendedTransaction($transaction_data),
            );

        } catch (Exception $ex) {
            return $this->handleMethodException($ex, __FUNCTION__);
        }
    }

    /**
     * @see https://securepayments.sberbank.ru/wiki/doku.php/integration:api:rest:requests:refund
     * @see https://securepayments.sberbank.ru/wiki/doku.php/integration:api:rest:requests:refund_cart_part
     * @param array $transaction_raw_data
     * @return array
     */
    public function refund($transaction_raw_data)
    {
        try {
            $transaction_raw_data = $this->getRefundTransactionData($transaction_raw_data);

            $transaction = $transaction_raw_data['transaction'];

            $request = array(
                'orderId'  => $transaction['native_id'],
                'language' => $this->getLanguage(),
                'amount'   => number_format($transaction_raw_data['refund_amount'], 2, '', ''), //convert to cent
            );

            $items = ifset($transaction_raw_data, 'refund_items', array());
            if ($this->fiscalization && count($items)) {
                $order = array(
                    'items'    => $items,
                    'currency' => $transaction['currency_id'],
                    'id'       => $transaction['order_id'],
                );
                $wa_order = waOrder::factory($order);
                $order_bundle = array(
                    'items' => $this->getItemsForFiscalization($wa_order),
                );

                $request['refundItems'] = json_encode($order_bundle, JSON_UNESCAPED_UNICODE);
            }

            $this->sendRequest(self::URL_PAYMENT_REFUND, $request);
            $transaction_data = array(
                'amount'    => $transaction_raw_data['refund_amount'],
                'parent_id' => $transaction['id'],
            );
            if ($transaction_raw_data['refund_amount'] < $transaction['amount']) {
                $transaction_data['state'] = self::STATE_PARTIAL_REFUNDED;
            }

            $transaction_data = $this->formalizeRequestResult(self::OPERATION_REFUND, $transaction, $transaction_data);

            return array(
                'result'      => 0,
                'description' => '',
                'data'        => $this->saveExtendedTransaction($transaction_data),
            );

        } catch (Exception $ex) {
            return $this->handleMethodException($ex, __FUNCTION__);
        }
    }

    /**
     * @see https://securepayments.sberbank.ru/wiki/doku.php/integration:api:rest:requests:deposit
     * @param $transaction_raw_data
     * @return array|bool
     */
    public function capture($transaction_raw_data)
    {
        try {
            $transaction = $transaction_raw_data['transaction'];

            $request = array(
                'orderId' => $transaction['native_id'],
            );

            if (!empty($transaction_raw_data['order_data'])) {
                $order = waOrder::factory($transaction_raw_data['order_data']);

                // handle changed amount; convert to cents for API
                $transaction['amount'] = number_format($order->total, 2, '.', '');
                $request['amount'] = number_format($order->total, 2, '', '');

                $order_bundle = array(
                    'items' => $this->getItemsForFiscalization($order),
                );

                $request['depositItems'] = json_encode($order_bundle, JSON_UNESCAPED_UNICODE);
            } else {
                $request['amount'] = number_format($transaction['amount'], 2, '', ''); //convert to cent
            }

            $this->sendRequest(self::URL_PAYMENT_COMPLETE, $request);

            $transaction_data = $this->formalizeRequestResult(self::OPERATION_CAPTURE, $transaction);

            return array(
                'result'      => 0,
                'description' => '',
                'data'        => $this->saveExtendedTransaction($transaction_data),
            );
        } catch (waPaymentException $ex) {
            switch ($ex->getCode()) {
                case 7:
                    break;
            }
            return $this->handleMethodException($ex, __FUNCTION__);
        } catch (Exception $ex) {
            return $this->handleMethodException($ex, __FUNCTION__);
        }
    }

    /**
     * @see  https://securepayments.sberbank.ru/wiki/doku.php/integration:api:rest:requests:paymentorderbinding
     * @param waOrder $order_data
     * @return bool|array
     * @todo KISS it too
     */
    public function recurrent($order_data)
    {
        $order_data = waOrder::factory($order_data);
        if (empty($order_data['card_native_id'])) {
            return false;
        }

        try {
            // We get the last transaction on the order and look at its status.
            // The last transaction must be a check, otherwise we are not interested and we create a new application
            $related_transactions = $this->getRelatedTransactions($order_data['order_id']);
            $transaction = end($related_transactions);
            $native_id = ifset($transaction, 'native_id', null);

            if (empty($native_id)) {
                // Register a new payment for bundles
                $order_data['is_recurrent'] = true;
                $transaction_data = $this->registerOrder($order_data);
                $native_id = ifset($transaction_data, 'native_id', null);
            } else {

                $last_data_transaction = $this->getGatewayTransactionStatus($order_data['order_id'], $native_id);
                // We valid that the money is not blocked and it makes sense to re-register
                if (isset($last_data_transaction['callback_method'])) {
                    switch ($last_data_transaction['callback_method']) {
                        case self::CALLBACK_AUTH:
                            throw new waPaymentException('Деньги заблокированы');
                            break;
                        case self::CALLBACK_DECLINE:
                            throw new waPaymentException('Платеж отклонен. Обратитесь в службу поддержки.');
                        case self::CALLBACK_CANCEL:
                            throw new waPaymentException('Платеж отменен. Обратитесь в службу поддержки.');
                            break;
                    }
                }

                if ($last_data_transaction['state'] != self::STATE_VERIFIED) {
                    // the order is registered but not paid;
                    $native_id = null;
                }
            }

            if (empty($native_id)) {
                return array(
                    'result'      => true,
                    'description' => 'Empty new order id',
                );
            } else {
                $user_data = $this->getUserData($order_data);
                $request_data = array(
                    'mdOrder'   => $native_id,
                    'bindingId' => $order_data['card_native_id'],
                    'language'  => $this->getLanguage(),
                    'ip'        => waRequest::getIp(),
                    'email'     => $user_data['email'],
                );

                $this->sendRequest(self::URL_PAYMENT_ORDER_BINDING, $request_data);
                $transaction['amount'] = $order_data->total;
                $transaction['currency_id'] = $order_data->currency;
                $transaction['native_id'] = $native_id;

                if (true) {
                    //extend transaction data via re-request
                    $transaction_data = $this->getGatewayTransactionStatus($order_data->id, ['native_id' => $native_id]);
                    $transaction_data = $this->handleTransaction($transaction_data);

                    return array(
                        'result'      => true,
                        'description' => '',
                        'data'        => $transaction_data,
                    );

                } else {
                    $transaction_data = $this->formalizeRequestResult(self::OPERATION_AUTH_CAPTURE, $transaction);

                    return array(
                        'result'      => true,
                        'description' => '',
                        'data'        => $this->saveExtendedTransaction($transaction_data),
                    );
                }
            }

        } catch (Exception $ex) {
            return $this->handleMethodException($ex, __FUNCTION__);
        }
    }

    /**
     * @param Exception $ex
     * @param string    $method
     * @return array
     */
    protected function handleMethodException($ex, $method)
    {
        $message = sprintf("Error occurred during %s: %s", $method, $ex->getMessage());
        self::log($this->id, $message);
        return array(
            'result'      => -1,
            'data'        => null,
            'description' => $ex->getMessage(),
        );
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

    /**
     * @see https://securepayments.sberbank.ru/wiki/doku.php/integration:api:rest:requests:getorderstatusextended
     * @param array $transaction_raw_data
     * @return array
     * @throws waException
     */
    public function formalizeData($transaction_raw_data)
    {
        $transaction_data = parent::formalizeData($transaction_raw_data);

        $transaction_raw_data = $this->extendByCallbackRequest($transaction_raw_data);
        $transaction_raw_data = $this->formalizeRawData($transaction_raw_data);

        $related_transactions = $this->getRelatedTransactions($transaction_raw_data['order_id']);
        $transaction = end($related_transactions);

        $transaction_data += array(
            'native_id'   => $transaction['native_id'],
            'order_id'    => $transaction_raw_data['order_id'],
            'customer_id' => $transaction['customer_id'],
            'view_data'   => [],
            'amount'      => intval($transaction_raw_data['amount']) / 100,
            'currency_id' => $transaction['currency_id'],
            'result'      => 1,
        );

        $view_data = &$transaction_data['view_data'];

        // Существует три набора параметров ответа.
        // Какие именно наборы параметров будут возвращены, завит от версии getOrderStatusExtended

        $state = ifset($transaction_raw_data, 'paymentAmountInfo.paymentState', 'NOT_SUPPORTED');
        switch ($state) {
            case 'NOT_SUPPORTED':
                //требуется getOrderStatusExtended 03 и выше
                break;
            case 'CREATED': # заказ создан;
                $transaction_data += array(
                    'type'  => waPayment::OPERATION_CHECK,
                    'state' => waPayment::STATE_VERIFIED,
                );
                break;
            case 'APPROVED': # заказ подтверждён;
                $transaction_data += array(
                    'type'  => self::OPERATION_AUTH_ONLY,
                    'state' => self::STATE_AUTH,
                );
                break;
            case 'DEPOSITED': # заказ завершён;
                $transaction_data += array(
                    'type'  => self::OPERATION_AUTH_CAPTURE,
                    'state' => self::STATE_CAPTURED,
                );
                break;
            case 'DECLINED': # заказ отклонён;
                $transaction_data += array(
                    'type'  => self::OPERATION_AUTH_ONLY,
                    'state' => self::STATE_DECLINED,
                );
                break;
            case 'REVERSED': # заказ отменён;
                $transaction_data += array(
                    'type'  => self::OPERATION_CANCEL,
                    'state' => self::STATE_CANCELED,
                );
                break;
            case 'REFUNDED': # произведён возврат средств по заказу;
                $transaction_data += array(
                    'type'  => self::OPERATION_REFUND,
                    'state' => self::STATE_REFUNDED,
                );

                break;
        }

        switch ($transaction_raw_data['orderStatus']) {
            case self::SB_ORDER_CREATE: # заказ зарегистрирован, но не оплачен
                $transaction_data += array(
                    'type'  => waPayment::OPERATION_CHECK,
                    'state' => waPayment::STATE_VERIFIED,
                );
                break;
            case self::SB_ORDER_HOLD: # предавторизованная сумма удержана (для двухстадийных платежей)
                $transaction_data += array(
                    'type'  => self::OPERATION_AUTH_ONLY,
                    'state' => self::STATE_AUTH,
                );
                break;
            case self::SB_ORDER_PAID: # проведена полная авторизация суммы заказа
                $transaction_data += array(
                    'type'  => self::OPERATION_AUTH_CAPTURE,
                    'state' => self::STATE_CAPTURED,
                );

                break;
            case self::SB_ORDER_CANCEL: # авторизация отменена
                $transaction_data += array(
                    'type'  => self::OPERATION_CANCEL,
                    'state' => self::STATE_CANCELED,
                );
                break;
            case self::SB_ORDER_REFUND: # по транзакции была проведена операция возврата
                $transaction_data += array(
                    'type'  => self::OPERATION_REFUND,
                    'state' => self::STATE_REFUNDED,
                );
                break;
            case self::SB_ORDER_PROCESSING: # инициирована авторизация через сервер контроля доступа банка-эмитента

                break;
            case self::SB_ORDER_DECLINE: # авторизация отклонена
                $transaction_data += array(
                    'type'  => self::OPERATION_AUTH_ONLY,
                    'state' => self::STATE_DECLINED,
                );
                break;
        }

        switch ($transaction_data['type']) {
            case self::OPERATION_REFUND:
                //уточнение - это полный возврат или частичный
                if (intval(ifset($transaction_raw_data, 'paymentAmountInfo.depositedAmount', 0))) {
                    $transaction_data['state'] = self::STATE_PARTIAL_REFUNDED;
                }
                $amount = ifset($transaction_raw_data, 'paymentAmountInfo.refundedAmount', 0) / 100;
                if ($amount) {
                    $transaction_data['refunded_amount'] = $amount;
                    if (isset($related_transactions[self::TRANSACTION_REFUND])) {
                        //XXX если это возврат из личного кабинета, то сумма неизвестна %)
                        $transaction_data['amount'] = $related_transactions[self::TRANSACTION_REFUND]['amount'];
                    }
                }
                break;
            case self::OPERATION_AUTH_CAPTURE:
                // уточнение - двухстадийная/одностадийная оплата
                $available_transactions = $this->getAvailableTransactions($transaction_data['order_id']);
                if (isset($available_transactions[self::TRANSACTION_CANCEL])) {
                    $transaction_data['type'] = self::OPERATION_CAPTURE;
                } elseif (isset($related_transactions[self::TRANSACTION_CAPTURE])) {
                    $transaction_data['type'] = $related_transactions[self::TRANSACTION_CAPTURE]['type'];
                }
                break;
        }

        //update parent transaction
        switch ($transaction_data['type']) {
            case self::OPERATION_REFUND:
                if ($transaction_data['state'] === self::STATE_REFUNDED) {
                    if (isset($related_transactions[self::TRANSACTION_CAPTURE])) {
                        $transaction_data['parent_id'] = $related_transactions[self::TRANSACTION_CAPTURE]['id'];
                        $transaction_data['parent_state'] = self::STATE_REFUNDED;
                    }
                }
                break;
            case self::OPERATION_CAPTURE:
                if (isset($related_transactions[self::TRANSACTION_AUTH])) {
                    $transaction_data['parent_id'] = $related_transactions[self::TRANSACTION_AUTH]['id'];
                    $transaction_data['parent_state'] = self::STATE_CAPTURED;
                }
                break;
            case self::OPERATION_CANCEL:
                if (isset($related_transactions[self::TRANSACTION_AUTH])) {
                    $transaction_data['parent_id'] = $related_transactions[self::TRANSACTION_AUTH]['id'];
                    $transaction_data['parent_state'] = self::STATE_CANCELED;
                }
                break;
        }

        //fill view data
        $card_fields = array();
        $payment_fields = array();
        switch ($transaction_data['type']) {
            case self::OPERATION_AUTH_ONLY:
                if ($transaction_data['state'] === self::STATE_AUTH) {
                    $view_data[] = 'Деньги заблокированы';
                }
                break;

            case self::OPERATION_CANCEL:
                $card_fields = array(
                    'cardAuthInfo.pan'            => 'Pan: %s',
                    'cardAuthInfo.cardholderName' => 'CardHolder: %s',
                );

                if (!empty($transaction_raw_data['actionCodeDescription'])) {
                    $transaction_data['error'] = $transaction_raw_data['actionCodeDescription'];
                } elseif (!empty($transaction_raw_data['actionCode'])) {
                    $transaction_data['error'] = $transaction_raw_data['actionCode'];
                }

                break;
            case self::OPERATION_AUTH_CAPTURE:
            case self::OPERATION_CAPTURE:
                $card_fields = array(
                    'cardAuthInfo.pan'            => 'Pan: %s',
                    'cardAuthInfo.cardholderName' => 'CardHolder: %s',
                );

                $payment_fields = array(
                    'paymentAmountInfo.depositedAmount' => 'Списано %0.2f %s',
                    'paymentAmountInfo.approvedAmount'  => 'Заблокировано %0.2f %s',
                );
                break;
        }

        if ($transaction_data['state'] === self::STATE_DECLINED) {
            $transaction_data['result'] = '';
            $card_fields = array(
                'cardAuthInfo.pan'            => 'Pan: %s',
                'cardAuthInfo.cardholderName' => 'CardHolder: %s',
            );

            if (!empty($transaction_raw_data['actionCodeDescription'])) {
                $transaction_data['error'] = $transaction_raw_data['actionCodeDescription'];
            } elseif (!empty($transaction_raw_data['actionCode'])) {
                $transaction_data['error'] = $transaction_raw_data['actionCode'];
            }
            $view_data[] = $transaction_data['error'];
        }

        foreach ($card_fields as $field => $format) {
            if (!empty($transaction_raw_data[$field])) {
                $view_data[] = sprintf($format, $transaction_raw_data[$field]);
            }
        }

        foreach ($payment_fields as $field => $format) {
            if (!empty($transaction_raw_data[$field])) {
                $view_data[] = sprintf($format, intval($transaction_raw_data[$field]) / 100, $transaction_data['currency_id']);
            }
        }


        //fill binding info

        $binding_id = ifset($transaction_raw_data, 'bindingInfo.bindingId', false);
        if ($binding_id) {
            $transaction_data['card_native_id'] = $binding_id;

            $pan = ifset($transaction_raw_data, 'cardAuthInfo.pan', false);
            if ($pan) {
                $transaction_data['card_view'] = $pan;
            }

            $expiration = ifset($transaction_raw_data, 'cardAuthInfo.expiration', false);
            if ($expiration) {
                $transaction_data['card_expire_date'] = $this->parseExpiration($expiration);
            }
        }

        unset($view_data);


        $transaction_data['raw_data'] = $transaction_raw_data;
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
    protected function callbackInit($request)
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
     * @param array $request
     * @return array
     * @throws waException
     */
    public function callbackHandler($request)
    {
        if (isset($request['register_order'])) {
            $storage = wa()->getStorage();
            $order_data = $storage->get('sb_order');
            $transaction = $this->registerOrder($order_data);
            $redirect_url = $transaction['raw_data']['formUrl'];
            $storage->remove('sb_order');
            wa()->getResponse()->redirect($redirect_url);
        }

        $verified = $this->verifyRequest($request);

        //extend request data
        $request = $this->extendByCallbackRequest($request);
        $transaction_data = null;
        if ($verified) {
            $transaction_data = $this->handleRequest($request);
        }

        $order_id = ifset($request, 'order_id', null);

        if (empty($transaction_data['callback_method'])) {
            // Уточняем данные, если только запроса недостаточно


            if (!empty($request['mdOrder'])) {
                $related = array(
                    'native_id' => $request['mdOrder'],
                );
            } else {
                $transactions = $this->getRelatedTransactions($order_id);
                $related = end($transactions);
            }

            try {
                if ($transaction = $this->getGatewayTransactionStatus($order_id, $related)) {
                    $transaction_data = $this->handleTransaction($transaction);
                }
            } catch (waException $ex) {
                if ($verified) {
                    if (empty($transaction_data['callback_method'])) {
                        //Запрос обработан, но требуются расширенные данные
                        throw $ex;
                    }
                } elseif ($verified === false) {
                    //Запрос не обработан, требуется еще одна попытка обработки данных
                    throw $ex;
                }
            }
        }


        if ($verified) {
            //It's gateway callback
            return array(
                'message' => 'YES',
            );
        } else {
            // Need order_id to redirect
            $redirect_data = array('order_id' => $order_id,);

            //Redirect user
            if (empty($transaction_data) //кажется, что-то пошло не так
                || (ifset($request['wa_result']) == 'fail') //это пользователь пришел по URL-у возврата в случае неуспешной оплаты
            ) {
                $redirect = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $redirect_data);
            } else {
                $redirect = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $redirect_data);
            };

            return compact('redirect');
        }
    }

    /**
     * @param $request
     * @return array
     * @throws waException
     */
    protected function formalizeRequestData($request)
    {
        $transaction_data = parent::formalizeData($request);
        $transaction_data += array(
            'native_id' => $request['mdOrder'],
            'order_id'  => $request['order_id'],
            'view_data' => array(),
        );
        if (isset($request['amount'])) {
            $transaction_data['amount'] = intval($request['amount']) / 100;
        }

        $related_transactions = $this->getRelatedTransactions($transaction_data['order_id']);
        $available_transactions = $this->getAvailableTransactions($transaction_data['order_id']);
        switch (ifset($request, 'operation', 'unknown')) {
            case 'approved'://операция удержания (холдирования) суммы;
                $transaction_data += array(
                    'type'  => self::OPERATION_AUTH_ONLY,
                    'state' => self::STATE_AUTH,
                );
                $parent_transaction = end($related_transactions);
                break;
            case 'deposited'://операция завершения;
                $transaction_data['state'] = self::STATE_CAPTURED;

                if (isset($available_transactions[self::TRANSACTION_CANCEL])) {
                    $transaction_data['type'] = self::OPERATION_CAPTURE;
                    $parent_transaction = $available_transactions[self::TRANSACTION_CANCEL];
                } else {
                    if (isset($related_transactions[self::TRANSACTION_CAPTURE])) {
                        $transaction_data['type'] = $related_transactions[self::TRANSACTION_CAPTURE]['type'];
                        $parent_transaction = $related_transactions[self::TRANSACTION_CAPTURE];
                    } else {
                        $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
                        $parent_transaction = end($related_transactions);
                    }
                }

                break;
            case 'reversed'://операция отмены;
                $transaction_data += array(
                    'type'  => self::OPERATION_CANCEL,
                    'state' => self::STATE_CANCELED,
                );
                $parent_transaction = end($related_transactions);
                break;
            case 'refunded'://операция возврата;
                $transaction_data += array(
                    'type'  => self::OPERATION_REFUND,
                    'state' => '',
                    //'state' => self::STATE_REFUNDED,
                );
                if (isset($available_transactions[self::TRANSACTION_REFUND])) {
                    $parent_transaction = $available_transactions[self::TRANSACTION_REFUND];
                    //XXX Partial refund?
                }

                break;
            case 'unknown':
                break;
            default:
                break;
        }

        if (!empty($parent_transaction)) {
            $transaction_data['currency_id'] = $parent_transaction['currency_id'];
            $transaction_data['amount'] = $parent_transaction['amount'];
        }

        return $transaction_data;
    }

    /**
     * @param $request
     * @return array
     * @throws waException
     */
    protected function handleRequest($request)
    {
        $transaction_data = $this->formalizeRequestData($request);
        $operation = ifset($request, 'operation', 'unknown');

        if (empty($request['status'])) {
            switch ($operation) {
                case 'approved'://операция удержания (холдирования) суммы;
                    $operation_name = 'авторизации';
                    break;
                case 'deposited'://операция завершения;
                    $operation_name = 'оплаты';
                    break;
                case 'reversed'://операция отмены;
                    $operation_name = 'отмены';
                    break;
                case 'refunded'://операция возврата;
                    $operation_name = 'возврата';
                    break;
                case 'unknown':
                    $operation_name = '[не указано]';
                    break;
                default:
                    $operation_name = sprintf('[%s] (не поддерживается)', $operation);
                    break;
            }

            $error = sprintf('Неуспешная операция %s', $operation_name);

            $transaction_data['view_data'][] = $error;
            $transaction_data['error'] = $error;
            $transaction_data['state'] = null;
            $transaction_data['type'] = null;
            $method = self::CALLBACK_NOTIFY;
        } else {
            switch ($operation) {
                case 'approved'://операция удержания (холдирования) суммы;
                    $method = self::CALLBACK_AUTH;
                    break;
                case 'deposited'://операция завершения;
                    $method = self::CALLBACK_PAYMENT;
                    break;
                case 'reversed'://операция отмены;
                    $method = self::CALLBACK_CANCEL;
                    break;
                case 'refunded'://операция возврата;
                    $method = false;
                    break;
                case 'unknown':
                    $method = null;
                    break;
                default:
                    $method = self::CALLBACK_NOTIFY;
                    $transaction_data['view_data'][] = sprintf('Неподдерживаемое уведомление [%s]', $operation);
                    break;
            }
        }

        $extra = array(
            'callback_method' => $method,
        );

        if ($method) {
            $transaction_data = $this->saveExtendedTransaction($transaction_data, $request);
            if ($method !== true) {
                $result = $this->execAppCallback($method, $transaction_data);
                $extra['callback_result'] = $result;
            }
        }

        return $transaction_data + $extra;
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
            'bindingInfo'       => '',
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
            //if there are new arrays
            if (is_array($value)) {
                if (array_key_exists($key, $slice)) {
                    foreach ($value as $name => $param) {
                        $order_status[$key.'.'.$name] = $param;
                    }
                }
                $order_status[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }
        return array_diff_key($order_status, $delete);
    }

    protected function saveExtendedTransaction($transaction_data, $raw_data = array())
    {
        $view_data = ifempty($transaction_data, 'view_data', []);
        if (!is_array($view_data)) {
            $view_data = array(
                $view_data,
            );
        }
        unset($transaction_data['view_data']);
        if ($this->TESTMODE) {
            $view_data[] = 'Тестовый режим';
        }
        $transaction_data['view_data'] = implode('. ', $view_data);

        $raw_data = $this->formalizeRawData($raw_data);
        $transaction_data = $this->saveTransaction($transaction_data, $raw_data);

        $transaction_data += compact('raw_data');


        if ($this->TESTMODE) {
            $log = array(
                'method'           => __METHOD__,
                'transaction_data' => $transaction_data,
                'TESTMODE'         => 'Extra logging enabled',
            );
            static::log($this->id, $log);
        }

        return $transaction_data;
    }


    /**
     * @param $transaction_data
     * @return array transaction_data
     */
    protected function handleTransaction($transaction_data)
    {
        if (isset($transaction_data['callback_method'])) {
            $app_payment_method = $transaction_data['callback_method'];
        } else {
            switch ($transaction_data['type']) {
                case self::OPERATION_CHECK:
                    $app_payment_method = true;
                    $transaction_data['state'] = self::STATE_VERIFIED;
                    break;
                case self::OPERATION_AUTH_ONLY:
                    $app_payment_method = self::CALLBACK_AUTH;
                    $transaction_data['state'] = self::STATE_AUTH;
                    break;
                case self::OPERATION_CANCEL:
                    $app_payment_method = self::CALLBACK_CANCEL;
                    $transaction_data['state'] = self::STATE_CANCELED;
                    break;

                case self::OPERATION_CAPTURE:
                    $app_payment_method = self::CALLBACK_PAYMENT;
                    $transaction_data['state'] = self::STATE_CAPTURED;
                    break;

                case self::OPERATION_AUTH_CAPTURE:
                    $app_payment_method = self::CALLBACK_PAYMENT;
                    $transaction_data['state'] = self::STATE_CAPTURED;
                    break;

                case self::OPERATION_REFUND:
                    $app_payment_method = self::CALLBACK_NOTIFY;
                    $transaction_data['state'] = '';
                    break;
                default:
                    $app_payment_method = self::CALLBACK_NOTIFY;
                    $transaction_data['state'] = '';
            }
        }

        $result = null;
        if ($app_payment_method) {
            $transaction_data = $this->saveExtendedTransaction($transaction_data, ifset($transaction_data['raw_data'], []));
            if ($app_payment_method !== true) {
                $result = $this->execAppCallback($app_payment_method, $transaction_data);
                $transaction_data['callback_result'] = $result;
            }
        }
        $transaction_data['callback_method'] = $app_payment_method;

        return $transaction_data;
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
                if (isset($data[3])) {
                    $request['modifier'] = intval($data[3]);
                }
            }
        }

        return $request;
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
        try {
            $view = wa()->getView();

            $view->assign(array(
                'form_url'         => $explode_url[0],
                'url_params_array' => $url_params_array,
                'auto_submit'      => $auto_submit,
            ));
            $template = $view->fetch($this->path.'/templates/payment.html');
        } catch (SmartyException $ex) {
            return $ex->getMessage();
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
        return $template;
    }

    /**
     * @param string $operation on of self::OPERATION_* constant
     * @param array  $transaction
     * @param array  $transaction_data
     * @return array
     */
    protected function formalizeRequestResult($operation, $transaction, $transaction_data = array())
    {
        $transaction_data += parent::formalizeData([]);

        if (!empty($transaction['id'])) {
            $transaction_data += array(
                'parent_id' => $transaction['id'],
            );
        }

        switch ($operation) {
            case self::OPERATION_CHECK:
                $transaction_data += array(
                    'type'  => waPayment::OPERATION_CHECK,
                    'state' => waPayment::STATE_VERIFIED,
                );
                break;
            case self::OPERATION_AUTH_ONLY:
                $transaction_data += array(
                    'type'  => self::OPERATION_AUTH_ONLY,
                    'state' => self::STATE_AUTH,
                );
                break;
            case self::OPERATION_CAPTURE:
                $transaction_data += array(
                    'type'         => self::OPERATION_CAPTURE,
                    'state'        => self::STATE_CAPTURED,
                    'parent_state' => self::STATE_CAPTURED,
                );
                break;
            case self::OPERATION_AUTH_CAPTURE:
                $transaction_data += array(
                    'type'  => self::OPERATION_AUTH_CAPTURE,
                    'state' => self::STATE_CAPTURED,
                );
                break;
            case self::OPERATION_CANCEL:
                $transaction_data += array(
                    'type'         => self::OPERATION_CANCEL,
                    'state'        => self::STATE_CANCELED,
                    'parent_state' => self::STATE_CANCELED,
                );
                break;
            case self::OPERATION_REFUND:
                $transaction_data += array(
                    'type'  => self::OPERATION_REFUND,
                    'state' => self::STATE_REFUNDED,
                );
                if ($transaction_data['state'] === self::STATE_REFUNDED) {
                    $transaction_data += array(
                        'parent_state' => self::STATE_REFUNDED,
                    );
                }
                break;
        }

        $transaction_data += $transaction;
        unset($transaction_data['id']);
        unset($transaction_data['raw_data']);
        if (empty($transaction_data['parent_id'])) {
            unset($transaction_data['parent_state']);
        }

        return $transaction_data;
    }

    /**
     * @see https://securepayments.sberbank.ru/wiki/doku.php/integration:api:rest:requests:register
     * @see https://securepayments.sberbank.ru/wiki/doku.php/integration:api:rest:requests:register_cart
     * @param waOrder $wa_order
     * @return array
     * @throws waException
     */
    protected function registerOrder($wa_order)
    {
        $order_number = $this->getOrderNumber($wa_order->id);
        $return_url = $this->getRelayUrl().'?orderNumber='.$order_number;
        $fail_url = $this->getRelayUrl().'?orderNumber='.$order_number.'&wa_result=fail';
        $register_fields = array(
            'orderNumber'        => $order_number,
            'amount'             => number_format($wa_order->total, 2, '', ''), //Сумма платежа в минимальных единицах валюты (копейки, центы и т. п.).
            'currency'           => $this->getCurrencyISO4217Code($wa_order->currency), //Код валюты платежа ISO 4217
            'returnUrl'          => $return_url,
            'failUrl'            => $fail_url,
            'description'        => $wa_order->datetime,
            'language'           => $this->getLanguage(),
            'sessionTimeoutSecs' => ($this->sessionTimeoutSecs ? $this->sessionTimeoutSecs : 24) * 60 * 60, //convert hours to sec,
            'jsonParams'         => array(),
        );


        //$register_fields['jsonParams']['merchantOrderId'] = $order_number;
        $register_fields['jsonParams']['description'] = $wa_order->description;

        //$register_fields['expirationDate']='yyyy-MM-ddTHH:mm:ss';

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
            $register_fields['jsonParams'] += $this->getUserData($wa_order);

            if ($this->TESTMODE) {
                $register_fields['dummy'] = true;
            }
        }

        if (empty($register_fields['jsonParams'])) {
            unset($register_fields['jsonParams']);
        } else {
            $register_fields['jsonParams'] = json_encode($register_fields['jsonParams'], JSON_UNESCAPED_UNICODE);
        }
        try {
            $response = $this->sendRequest(self::URL_ORDER_REGISTER, $register_fields);
            $response = $this->validateRegisterResponse($response);

            $transaction_data = array(
                'native_id'   => $response['orderId'],
                'order_id'    => $wa_order['id'],
                'customer_id' => $wa_order['contact_id'],
                'amount'      => $wa_order['total'],
                'currency_id' => $wa_order['currency'],
                'result'      => 1,
                'type'        => self::OPERATION_CHECK,
                'state'       => self::STATE_VERIFIED,
                'raw_data'    => array(
                    'formUrl' => $response['formUrl'],
                ),
            );

            $transaction_data = $this->handleTransaction($transaction_data);

            //for testing purpose
            $transaction_data['raw_response'] = $response;
            $transaction_data['raw_request'] = $register_fields;

            return $transaction_data;
        } catch (waPaymentException $ex) {
            switch ($ex->getCode()) {
                case 1://Неверный номер заказа./Заказ с таким номером уже обработан.
                    //TODO use suffix for orders;
                    //проверить статус заказа
                    //если есть проблемы, то получить доступные суффиксы
                    break;
            }
            throw $ex;
        }

    }

    /**
     * @param waOrder $wa_order
     * @return string
     * @throws waPaymentException
     * @throws waException
     */
    protected function getInfoForFiscalization($wa_order)
    {
        $data = $this->getUserData($wa_order);

        if (!$data['email'] && !$data['phone']) {
            static::log($this->id, 'Не установлен системный email-адрес.');
            throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
        }

        $order_bundle = array(
            'orderCreationDate' => time() * 1000,
            'customerDetails'   => array(
                'email'   => $data['email'],
                'phone'   => $data['phone'],
                'contact' => $wa_order->getContact()->getName(),
            ),
            'cartItems'         => array(
                'items' => $this->getItemsForFiscalization($wa_order),
            ),
        );

        $country = $this->getISO2CountryCode($wa_order['shipping_address']['country']);
        $city = $wa_order['shipping_address']['city'];
        $post_address = $wa_order['shipping_address']['street'];

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
                'productType' => $this->credit_type,
            );
        }

        return json_encode($order_bundle, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param waOrder $wa_order
     * @return array
     */
    protected function getUserData($wa_order)
    {
        $result = [
            'email' => $wa_order->getContactField('email', 'default'),
            'phone' => $wa_order->getContactField('phone', 'default'),
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
            static::log($this->id, "Unknown VAT rate: {$tax}. The list of available bets: see Sberbank documentation.");
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

        $this->receipt_number = 1;

        if (!empty($order_data['shipping'])) {
            if (!$order_data->shipping_tax_included && (int)$order_data->shipping_tax_rate > 0) {
                static::log($this->id, sprintf('НДС не включён в стоимость доставки (%s).', $order_data->shipping_name));
                throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
            }
            $data = array(
                'name'     => $order_data->shipping_name,
                'total'    => $order_data->shipping,
                'price'    => $order_data->shipping,
                'quantity' => 1,
                'tax_rate' => $order_data->shipping_tax_rate,
                'type'     => 'shipping',
            );
            $items[] = $this->formalizeItemData($data, $order_data);
        }

        if (is_array($order_data->items)) {
            foreach ($order_data->items as $item_data) {
                if (!$item_data['tax_included'] && (int)$item_data['tax_rate'] > 0) {
                    static::log($this->id, sprintf('НДС не включён в цену товара: %s.', var_export($item_data, true)));
                    throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
                }

                $items_data = [];
                $item_type = ifset($item_data, 'type', null);
                if ($item_type === 'product') {
                    $values = $this->getChestnyznakCodeValues($item_data['product_codes']);
                    if ($values) {
                        $items_data = $this->splitItem($item_data, $values);
                    }
                }

                if ($items_data) {
                    foreach ($items_data as $split_index => $data) {
                        $data['split_index'] = $split_index;
                        $items[] = $this->formalizeItemData($data, $order_data);
                    }
                } else {
                    $items[] = $this->formalizeItemData($item_data, $order_data);
                }
            }
        };

        // Never send zero-quantity item to API
        foreach ($items as $i => $item) {
            if (empty($item['quantity']['value'])) {
                unset($items[$i]);
            }
        }

        return array_values($items);
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
            $item['total'] = $item['price'];
            $items[] = $item;
        }
        return $items;
    }

    /**
     * @link https://developer.sberbank.ru/doc/v1/acquiring/api-basket
     * @param array   $data
     * @param waOrder $order_data
     * @return array
     * @throws waException
     * @throws waPaymentException
     */
    protected function formalizeItemData($data, $order_data)
    {
        if (!empty($data['total_discount'])) {
            $data['total'] = $data['total'] - $data['total_discount'];
            $discount = round(ifset($data, 'discount', 0.0), 2);
            $data['price'] = round($data['price'], 2) - $discount; //calculate flexible discounts
        }

        $is_split = isset($data['split_index']);

        /**
         * В корзине запрещены для передачи товарные позиции, отсутствующие в оригинальном заказе.
         * Происходит проверка наличия указанного товара в корзине запроса на возврат в изначальном заказе.
         * Необходимо совпадение элементов positionId, name, itemCode.
         * Если хотя бы одно из значений не совпадает, считается, что данная товарная позиция отсутствует в оригинальном заказе.
         */

        if (empty($data['id'])) {
            $position_id = $this->receipt_number++;
            $data['id'] = $position_id;
        } elseif ($is_split) {
            // position_id must be unique in order, so if item has been split position number is autoincrement receipt_number
            $position_id = $this->receipt_number++;
        } else {
            $position_id = max($data['id'], $this->receipt_number);
            if ($position_id == $this->receipt_number) {
                ++$this->receipt_number;
            }
        }

        $item_code = $this->app_id.'_order_'.$order_data['id'].'_'.$data['type'].'_'.$data['id'];

        // if item has been split cause of fiscal code, there is split index, add it as part of item code
        if ($is_split) {
            $item_code .= '_' . $data['split_index'];
        }

        $tax_sum = $this->getTaxSum($data['price'], $data['tax_rate']);
        $item_data = array(
            'positionId'   => $position_id,
            'name'         => mb_substr($data['name'], 0, 100),
            'quantity'     => array(
                'value'   => (int)$data['quantity'],
                'measure' => 'шт.',
            ),
            'itemAmount'   => number_format($data['total'], 2, '', ''),
            'itemPrice'    => number_format($data['price'], 2, '', ''),
            'itemCurrency' => $this->getCurrencyISO4217Code($order_data['currency']),
            'itemCode'     => $item_code,
            'tax'          => array(
                'taxType' => $this->getTaxType($data['tax_rate']),
                'taxSum'  => number_format($tax_sum, 2, '', ''),
            ),
        );


        // Credit dont work for ФФД 1.05
        if (!$this->credit) {
            $attributes = [
                [
                    'name'  => 'paymentMethod',
                    'value' => $this->payment_method,
                ],
                [
                    'name'  => 'paymentObject',
                    'value' => $this->getPaymentObject(ifset($data, 'type', null)),
                ],
            ];

            // Код товара — уникальный номер, который присваивается экземпляру товара при маркировке
            // Тут идет конвертация из DataMatrix кода (Честный знак) в 1162 тег код для ККТ
            if (isset($data['chestnyznak'])) {
                $nomenclature = $this->convertToNomenclatureCode($data['chestnyznak']);
                if ($nomenclature) {
                    $attributes[] = [
                        'name' => 'nomenclature',
                        'value' => $nomenclature
                    ];
                }
            }

            $item_data['itemAttributes'] = [
                'attributes' => $attributes,
            ];

        }

        return $item_data;
    }

    protected function getPaymentObject($type)
    {
        $result = '13';//XXX проверить корректность значения по умолчанию

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
     * Конвертация из DataMatrix кода (Честный знак) в код номенклатуры
     * @param $uid
     * @return bool|string
     */
    protected function convertToNomenclatureCode($uid)
    {
        if (!class_exists('shopChestnyznakPluginCodeParser')) {
            return false;
        }

        $code = shopChestnyznakPluginCodeParser::extractProductCode($uid);
        if (!$code) {
            return false;
        }

        return $code;
    }

    /**
     * Checks whether there is a Check Transaction. If so, it requests the status of native_id
     * @see https://securepayments.sberbank.ru/wiki/doku.php/integration:api:rest:requests:getorderstatusextended
     * @param string $order_id
     * @param array  $previous_transaction
     * @param bool   $raw
     * @return array
     * @throws waException
     * @throws waPaymentException
     */
    protected function getGatewayTransactionStatus($order_id, $previous_transaction, $raw = false)
    {
        $response = array();

        //get native order_id
        $request = [
            'orderNumber' => $this->getOrderNumber($order_id),
        ];

        if (!$this->credit) {
            if (empty($previous_transaction)) {
                return $response;
            }
            if (!is_array($previous_transaction)) {
                $previous_transaction['native_id'] = $previous_transaction;
            }

            $request['orderId'] = $previous_transaction['native_id'];
        }
        $response = $this->sendRequest(self::URL_ORDER_STATUS, $request);
        if ($raw) {
            return $response;
        } else {
            $transaction = $this->formalizeData($response);

            switch ($response['orderStatus']) {
                case self::SB_ORDER_CREATE: # заказ зарегистрирован, но не оплачен
                    break;
                case self::SB_ORDER_HOLD: # предавторизованная сумма удержана (для двухстадийных платежей)
                    $transaction['callback_method'] = self::CALLBACK_AUTH;
                    break;
                case self::SB_ORDER_PAID: # проведена полная авторизация суммы заказа
                    $transaction['callback_method'] = self::CALLBACK_PAYMENT;
                    break;
                case self::SB_ORDER_CANCEL: # авторизация отменена
                    $transaction['callback_method'] = self::CALLBACK_CANCEL;
                    break;
                case self::SB_ORDER_REFUND: # по транзакции была проведена операция возврата
                    if ($transaction['state'] == self::STATE_PARTIAL_REFUNDED) {
                        $transaction['callback_method'] = self::CALLBACK_NOTIFY;
                    } else {
                        $transaction['callback_method'] = self::CALLBACK_REFUND;
                    }
                    break;
                case self::SB_ORDER_PROCESSING: # инициирована авторизация через сервер контроля доступа банка-эмитента
                    $transaction['callback_method'] = false;
                    break;
                case self::SB_ORDER_DECLINE: # авторизация отклонена
                    $transaction['callback_method'] = self::CALLBACK_DECLINE;
                    break;

            }

            return $transaction;
        }
    }

    /**
     * @param $response
     * @return mixed
     * @throws waPaymentException
     */
    protected function validateRegisterResponse($response)
    {
        if (empty($response['formUrl'])) {
            static::log($this->id, 'formUrl not received');
            throw new waPaymentException('Ошибка платежа. Обратитесь в службу поддержки.');
        }

        return $response;
    }

    /**
     * @param string $api_url one of sbPayment::URL_* constants
     * @param array  $request
     * @return array
     * @throws waException
     * @throws waPaymentException
     *
     */
    protected function sendRequest($api_url, $request)
    {
        $url = $this->getURL($api_url);
        $log = array(
            'method' => __METHOD__,
        );
        $log += compact('url', 'request');

        //next data shouldn't be logged
        $request['userName'] = $this->userName;
        $request['password'] = $this->password;

        //create transport
        $options = array(
            'format'         => waNet::FORMAT_JSON,
            'request_format' => waNet::FORMAT_RAW,
            'timeout'        => 60,
        );
        if ($this->TESTMODE && class_exists('sbtestNet')) {
            $net = new sbtestNet($options);
        } else {
            $net = new waNet($options);
        }

        try {

            $response = $net->query($url, $request, waNet::METHOD_POST);

            if (empty($response)) {
                throw new waException('Empty response');
            }

            $log['response'] = $response;
            $code = intval(ifset($response, 'errorCode', 0));

            if ($code) {
                $message = sprintf(
                    'Ошибка #%d: %s',
                    $code,
                    ifset($response, 'errorMessage', $code)
                );

                throw new waPaymentException($message, $code);
            }
        } catch (waException $ex) {
            $this->handleRequestException($ex, $net, $log);
            throw $ex;
        } catch (Exception $ex) {
            $this->handleRequestException($ex, $net, $log);
            throw new waException($ex->getMessage());
        }

        if ($this->TESTMODE) {
            $log['TESTMODE'] = 'Extra logging enabled';
            static::log($this->id, $log);
        }

        return $response;
    }

    /**
     * @param Exception $ex
     * @param waNet     $net
     * @param array     $log
     */
    protected function handleRequestException($ex, $net, $log)
    {
        $log += array(
            'exception' => get_class($ex),
            'message'   => $ex->getMessage(),
            'code'      => $ex->getCode(),
            'trace'     => $ex->getTraceAsString(),
        );
        if (!empty($net)) {
            if (empty($log['response'])) {
                $log['raw_response'] = $net->getResponse(true);
            }

            $log['response_headers'] = $net->getResponseHeader();
        }

        static::log($this->id, $log);
    }

    /**
     * @param string $order_id
     * @param int    $modifier
     * @return string
     */
    protected function getOrderNumber($order_id, $modifier = null)
    {
        $result = null;
        if (is_numeric($order_id) || is_string($order_id)) {
            $result = sprintf('%s_%s_%s', $this->app_id, $this->merchant_id, $order_id);
            if ($modifier) {
                $result .= '_'.$modifier;
            }
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
     * @return string
     */
    private function getEndpointUrl()
    {
        // If the test mode is enabled, replace the URL
        if ($this->getSettings('TESTMODE')) {
            $domain = 'https://3dsec.sberbank.ru';
        } else {
            $domain = 'https://securepayments.sberbank.ru';
        }
        return $domain;
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
        $domain = $this->getEndpointUrl();

        $urls = array(
            self::URL_ORDER_REGISTER        => 'register.do',
            self::URL_ORDER_PRE_REGISTER    => 'registerPreAuth.do',
            self::URL_ORDER_STATUS          => '/payment/rest/getOrderStatusExtended.do',
            self::URL_PAYMENT_COMPLETE      => '/payment/rest/deposit.do',
            self::URL_PAYMENT_CANCEL        => '/payment/rest/reverse.do',
            self::URL_PAYMENT_REFUND        => '/payment/rest/refund.do',
            self::URL_PAYMENT_ORDER_BINDING => '/payment/rest/paymentOrderBinding.do',
        );

        if ($url === self::URL_ORDER_REGISTER) {
            $path = $urls[$url];
            //For a two-stage payment, you need another link

            if ($this->two_step && !$this->credit) {
                $path = $urls[self::URL_ORDER_PRE_REGISTER];
            }

            if ($this->credit) {
                $path = '/sbercredit/'.$path;
            } else {
                $path = '/payment/rest/'.$path;
            }

        } else {
            $path = ifset($urls, $url, '');
        }


        $result = null;
        if ($path) {
            $result = $domain.$path;
        }

        return $result;
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
     * @throws waDbException
     * @throws waException
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


    /**
     * @param array $params
     * @return string
     * @throws SmartyException
     * @throws waException
     */
    public function getSettingsHTML($params = array())
    {
        $view = wa()->getView();
        $settings = $this->getSettings();

        $view->assign(array(
            'obj'             => $this,
            'namespace'       => waHtmlControl::makeNamespace($params),
            'settings'        => $settings,
            'currencies'      => $this->settingsCurrency(),
            'payment_methods' => $this->settingsPaymentMethods(),
            'payment_subject' => $this->settingsPaymentSubjectOptions(),
            'tax_systems'     => $this->settingsTaxSystem(),
            'credit_types'    => $this->settingsCreditTypes(),
        ));

        return $view->fetch($this->path.'/templates/settings.html');
    }

    protected function settingsCurrency()
    {
        return [['title' => 'RUB', 'value' => 'RUB']];
    }

    /** @noinspection PhpUnused */
    public function settingsPaymentSubjectOptions()
    {
        return array(
            ['title' => 'товар', 'value' => '1'],
            ['title' => 'подакцизный товар', 'value' => '2'],
            ['title' => 'работа', 'value' => '3'],
            ['title' => 'услуга', 'value' => '4'],
            ['title' => 'ставка в азартной игре', 'value' => '5'],
            ['title' => 'выигрыш в азартной игре', 'value' => '6'],
            ['title' => 'лотерейный билет', 'value' => '7'],
            ['title' => 'выигрыш в лотерею', 'value' => '8'],
            ['title' => 'результаты интеллектуальной деятельности', 'value' => '9'],
            ['title' => 'платёж', 'value' => '10'],
            ['title' => 'агентское вознаграждение', 'value' => '11'],
            ['title' => 'несколько вариантов', 'value' => '12'],
            ['title' => 'другое', 'value' => '13'],
        );
    }

    /**
     * @return array
     */
    public function settingsPaymentMethods()
    {
        return [
            ['title' => 'полная предоплата', 'value' => '1'],
            ['title' => 'частичная предоплата', 'value' => '2'],
            ['title' => 'аванс', 'value' => '3'],
            ['title' => 'полный расчёт', 'value' => '4'],
            ['title' => 'частичный расчёт и кредит', 'value' => '5'],
            ['title' => 'кредит', 'value' => '6'],
            ['title' => 'выплата по кредиту', 'value' => '7'],
        ];
    }

    /**
     * @return array
     */
    public function settingsTaxSystem()
    {
        return [
            [
                'title' => 'Общая',
                'value' => '0',
            ],
            [
                'title' => 'Упрощённая,
             доход',
                'value' => '1',
            ],
            [
                'title' => 'Упрощённая,
            доход минус расход',
                'value' => '2',
            ],
            [
                'title' => 'Единый налог на вменённый доход',
                'value' => '3',
            ],
            [
                'title' => 'Единый сельскохозяйственный налог',
                'value' => '4',
            ],
            [
                'title' => 'Патентная система налогообложения',
                'value' => '5',
            ],
        ];
    }

    public function settingsCreditTypes()
    {
        return [
            [
                'title' => 'кредит без переплаты',
                'value' => 'INSTALLMENT',
            ],
            [
                'title' => 'кредит',
                'value' => 'CREDIT',
            ],
        ];
    }


    /**
     * @see https://securepayments.sberbank.ru/wiki/doku.php/integration:api:callback:start
     * @see phpseclib/phpseclib
     * @param array  $request
     * @param string $cer optional CER data for testing purpose
     * @return bool `true` if signature valid, `false` if it couldn't be checked, `null` if there no checksum
     * @throws waPaymentException if signature is incorrect
     */
    protected function verifyRequest($request, $cer = null)
    {
        #extract signature
        $signature = ifset($request, 'checksum', null);
        if ($signature === null) {
            return null;
        }

        if ($this->TESTMODE && class_exists('sbtestNet')) {
            return true;
        }

        $signature = hex2bin($signature);

        #cleanup and prepare request data
        $data = '';

        unset($request['checksum']);
        unset($request['sign_alias']);
        ksort($request, SORT_NATURAL);
        foreach ($request as $key => $value) {
            $data .= sprintf('%s;%s;', $key, $value);
        };

        #load default Certificate
        if (empty($cer)) {
            if ($this->getSettings('TESTMODE')) {
                $path = $this->path.'/lib/config/sb.testmode.cer';
            } else {
                $path = $this->path.'/lib/config/sb.production.cer';
            }
            $cer = file_get_contents($path);
        }

        #use vendor autoload
        require_once($this->path.'/lib/vendor/autoload.php');

        $x509 = new X509();

        if (!$x509->loadX509($cer)) {
            static::log($this->id, 'Error occurred during loading CER file');
            return false;
        }

        if (!$x509->validateDate()) {
            static::log($this->id, 'ERROR: CER file is outdated');
            return false;
        } elseif (!$x509->validateDate('+14 days')) {
            static::log($this->id, 'WARNING: CER file is outdated ');
        }


        if (!$x509->validateURL($this->getEndpointUrl())) {
            static::log($this->id, sprintf('WARNING: CER file is incompatible for [%s]', $this->getEndpointUrl()));
        }

        /** @var phpseclib\Crypt\RSA $rsa_key */
        $rsa_key = $x509->getPublicKey();

        if (empty($rsa_key)) {
            static::log($this->id, 'ERROR: Invalid public key ar CER file');
            return false;
        }

        #setup properly signature algorithm
        $rsa_key->setSignatureMode(phpseclib\Crypt\RSA::SIGNATURE_PKCS1);
        $rsa_key->setHash('sha512');

        if ($rsa_key->verify($data, $signature)) {
            return true;
        }

        throw new waPaymentException('Invalid signature');
    }
}
