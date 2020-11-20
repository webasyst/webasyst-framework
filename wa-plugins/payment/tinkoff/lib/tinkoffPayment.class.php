<?php

/**
 *
 * @author      Webasyst
 * @name tinkoffPayment
 * @description tinkoff Payments Standard Integration
 *
 * @link        https://oplata.tinkoff.ru/develop/api/payments/
 *
 * @property-read        $terminal_key
 * @property-read        $terminal_password
 * @property-read        $currency_id
 * @property-read        $two_steps
 * @property-read        $testmode
 * @property-read int    $check_data_tax
 * @property-read string $taxation
 * @property-read string $payment_object_type_product
 * @property-read string $payment_object_type_service
 * @property-read string $payment_object_type_shipping
 * @property-read string $payment_method_type
 *
 */
class tinkoffPayment extends waPayment implements waIPayment, waIPaymentRefund, waIPaymentRecurrent, waIPaymentCancel, waIPaymentCapture
{
    private $order_id;
    private $receipt;

    private static $currencies = array(
        'RUB' => 643,
        'USD' => 840,
    );

    /**
     * @return string callback gateway url
     */
    protected function getEndpointUrl()
    {
        /*  v1
            ? 'https://rest-api-test.tinkoff.ru/rest/'
            : 'https://securepay.tinkoff.ru/rest/';
        */
        return $this->testmode
            ? 'https://rest-api-test.tinkoff.ru/v2/'
            : 'https://securepay.tinkoff.ru/v2/';
    }

    public function allowedCurrency()
    {
        return $this->getSettings('currency_id');
        //return array_keys(self::$currencies);
    }

    public function supportedOperations()
    {
        return array(
            self::OPERATION_AUTH_CAPTURE,
            self::OPERATION_AUTH_ONLY,
            self::OPERATION_CHECK,
            self::OPERATION_CAPTURE,
            self::OPERATION_REFUND,
            self::OPERATION_CANCEL,
            self::OPERATION_RECURRENT,
        );
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order_data = waOrder::factory($order_data);

        if (empty($order_data['description_en'])) {
            $order_data['description_en'] = 'Order '.$order_data['order_id'];
        }

        $c = new waContact($order_data['customer_contact_id']);
        $email = $c->get('email', 'default');

        if (empty($email)) {
            $email = $this->getDefaultEmail();
        }

        $args = array(
            'Amount'      => round($order_data['amount'] * 100),
            'Currency'    => ifset(self::$currencies[$this->currency_id]),
            'OrderId'     => $this->app_id.'_'.$this->merchant_id.'_'.$order_data['order_id'],
            'CustomerKey' => $c->getId(),
            'Description' => ifempty($order_data, 'summary', ''),
            'PayType'     => $this->two_steps ? 'T' : 'O',
            'DATA'        => array(
                'Email' => $email,
            ),
        );

        if ($phone = $c->get('phone', 'default')) {
            $args['DATA']['Phone'] = $phone;
        }

        if ($order_data->save_card) {
            $args['Recurrent'] = 'Y';
        }

        if ($this->getSettings('check_data_tax')) {
            $args['Receipt'] = $this->getReceiptData($order_data);
            if (!$args['Receipt']) {
                return 'Данный вариант платежа недоступен. Воспользуйтесь другим способом оплаты.';
            }
        }

        if ($this->getSettings('payment_language') == 'en') {
            $args['Language'] = 'en';
        }

        try {
            $response = $this->apiQuery('Init', $args);
            $payment_url = ifset($response, 'PaymentURL', '');

            if (!$payment_url) {
                return null;
            }
        } catch (Exception $ex) {
            return 'Данный вариант платежа недоступен. Воспользуйтесь другим способом оплаты.';
        }
        $view = wa()->getView();

        $view->assign('plugin', $this);
        $view->assign('form_url', $payment_url);
        $view->assign('auto_submit', $auto_submit);

        return $view->fetch($this->path.'/templates/payment.html');

    }

    /**
     * Generates token
     *
     * @param array $args array of query params
     *
     * @return string
     */
    private function genToken($args)
    {
        $token = '';
        $args['Password'] = trim($this->terminal_password);

        ksort($args);

        foreach ($args as $k => $arg) {
            if (!is_array($arg)) {
                $token .= $arg;
            }
        }

        $token = hash('sha256', $token);

        return $token;
    }

    /**
     * @param $args
     * @throws waPaymentException
     */
    private function checkToken($args)
    {
        $args['Password'] = trim($this->getSettings('terminal_password'));

        if (!strlen($args['Password'])) {
            throw new waPaymentException('Password misconfiguration');
        }

        $token = ifset($args, 'Token', false);
        unset($args['Token']);


        ksort($args);
        foreach ($args as &$arg) {
            if (is_bool($arg)) {
                $arg = $arg ? 'true' : 'false';
            }
            unset($arg);
        }

        $expected_token = hash('sha256', implode('', $args));

        if (empty($token) || ($token !== $expected_token)) {
            throw new waPaymentException('Invalid token');
        }
    }

    protected function callbackInit($request)
    {
        $request = $this->sanitizeRequest($request);

        $pattern = '/^([a-z]+)_(\d+)_(.+)$/';
        if (!empty($request['OrderId']) && preg_match($pattern, $request['OrderId'], $match)) {
            $this->app_id = $match[1];
            $this->merchant_id = $match[2];
            $this->order_id = $match[3];
        }
        return parent::callbackInit($request);
    }

    /**
     * Main method. Call API with params
     *
     * @param string $method  API Url
     * @param array  $request API params
     *
     * @return mixed
     * @throws Exception
     */
    private function apiQuery($method, $request)
    {
        if (is_array($request)) {
            if (!array_key_exists('TerminalKey', $request)) {
                $request['TerminalKey'] = trim($this->terminal_key);
            }
            if (!array_key_exists('Token', $request)) {
                $request['Token'] = $this->genToken($request);
            }
        }

        $api_url = $this->getEndpointUrl().$method;

        $options = array(
            'request_format' => 'json',
            'format'         => waNet::FORMAT_JSON,
            'verify'         => false,
        );

        if (class_exists('tinkofftestNet')) {
            $net = new tinkofftestNet($options);
        } else {
            $net = new waNet($options);
        }

        $log = array(
            'method' => __METHOD__,
            'url' => $api_url,
            'request' => $request,
        );
        try {

            $response = $net->query($api_url, $request, waNet::METHOD_POST);
            $log['response'] = $response;

            if (!empty($response['ErrorCode'])) {
                $message = sprintf(
                    '%s #%d: %s',
                    ifset($response, 'Message', 'Error'),
                    $response['ErrorCode'],
                    ifset($response, 'Details', $this->translateError($response['ErrorCode']))
                );

                throw new waPaymentException($message);
            } elseif (!isset($response['Success']) || !$response['Success'] || $response['Success'] === 'false') {
                $message = sprintf(
                    '%s: %s',
                    ifset($response, 'Message', 'Error'),
                    ifset($response, 'Details', 'common error')
                );

                throw new waPaymentException($message);
            }

        } catch (Exception $ex) {
            $log['message'] = $ex->getMessage();
            if (empty($log['response'])) {
                $log['raw_response'] = $net->getResponse(true);
            }
            $log['response_headers'] = $net->getResponseHeader();

            self::log($this->id, $log);

            throw $ex;
        }

        if ($this->isTestMode()) {
            $log['testmode'] = 'Extra logging enabled';
            static::log($this->id, $log);
        }

        return $response;
    }

    /**
     * IPN (Instant Payment Notification)
     * @param $data - get from gateway
     * @return array|void
     * @throws waPaymentException
     * @throws waException
     */
    protected function callbackHandler($data)
    {
        $data = $this->sanitizeRequest($data);

        // Redirect customer mode
        if (empty($data['Token']) && !empty($data['PaymentId'])) {
            if (!key_exists('Success', $data)) {
                self::log($this->id, 'Error: missed request parameter "Success"');
                return;
            }
            $type = $data['Success'] == 'true' ? waAppPayment::URL_SUCCESS : waAppPayment::URL_FAIL;
            $url = $this->getAdapter()->getBackUrl($type, array('order_id' => $this->order_id));
            return array(
                'redirect' => $url,
            );
        }

        // Verify token
        $this->checkToken($data);

        $transaction_data = $this->formalizeData($data);

        $app_payment_method = null;

        switch ($transaction_data['type']) {
            case self::OPERATION_AUTH_ONLY:
                if ($transaction_data['result']) {
                    $app_payment_method = self::CALLBACK_AUTH;
                } else {
                    $app_payment_method = self::CALLBACK_DECLINE;
                }
                break;

            case self::OPERATION_AUTH_CAPTURE:
                if ($transaction_data['result']) {
                    $app_payment_method = self::CALLBACK_PAYMENT;
                } else {
                    $app_payment_method = self::CALLBACK_DECLINE;
                }
                break;

            case self::OPERATION_CHECK:
                $app_payment_method = self::CALLBACK_CONFIRMATION;
                break;

            case self::OPERATION_CAPTURE:
                $app_payment_method = self::CALLBACK_CAPTURE;
                break;

            case self::OPERATION_REFUND:
                if ($transaction_data['state'] === self::STATE_PARTIAL_REFUNDED) {
                    $app_payment_method = self::CALLBACK_NOTIFY;
                } else {
                    $app_payment_method = self::CALLBACK_REFUND;
                }
                break;

            case self::OPERATION_CANCEL:
                if ($transaction_data['state'] === self::STATE_DECLINED) {
                    $app_payment_method = self::CALLBACK_DECLINE;
                } else {
                    $app_payment_method = self::CALLBACK_CANCEL;
                }

                break;

            default:
                self::log($this->id, 'Unsupported callback operation: '.$transaction_data['type']);
                return;
        }
        if ($app_payment_method) {
            $method = $this->isRepeatedCallback($app_payment_method, $transaction_data);
            if ($method == $app_payment_method) {
                //Save transaction and run app callback only if it not repeated callback;
                $transaction_data = $this->saveTransaction($transaction_data, $data);
                $this->execAppCallback($app_payment_method, $transaction_data);
            } else {
                $log = array(
                    'message'                  => 'silent skip callback as repeated',
                    'method'                   => __METHOD__,
                    'app_id'                   => $this->app_id,
                    'callback_method'          => $method,
                    'original_callback_method' => $app_payment_method,
                    'transaction_data'         => $transaction_data,
                );

                static::log($this->id, $log);
            }
        }
    }

    public function refund($transaction_raw_data)
    {
        try {
            $transaction_raw_data = $this->getRefundTransactionData($transaction_raw_data);

            $amount = round($transaction_raw_data['refund_amount'] * 100);

            $args = array(
                'PaymentId' => $transaction_raw_data['transaction']['native_id'],
                'Amount'    => $amount,
            );

            if (isset($transaction_raw_data['refund_description'])) {
                $args['Description'] = ifempty($transaction_raw_data['refund_description'], '');
            }

            $items = ifset($transaction_raw_data, 'refund_items', array());

            if ($this->getSettings('check_data_tax') && $items) {
                $order_data = waOrder::factory(array(
                    'items'      => $items,
                    'currency'   => $transaction_raw_data['transaction']['currency_id'],
                    'id'         => $transaction_raw_data['transaction']['order_id'],
                    'contact_id' => $transaction_raw_data['transaction']['customer_id'],
                ));
                $args['Receipt'] = $this->getReceiptData($order_data);
                if (!$args['Receipt']) {
                    throw new waPaymentException('Ошибка формирования чека возврата');
                }
            }

            $res = $this->apiQuery('Cancel', $args);


            $response = array(
                'result'      => 0,
                'data'        => $res,
                'description' => '',
            );
            $now = date('Y-m-d H:i:s');
            $transaction = array(
                'native_id'       => $transaction_raw_data['transaction']['native_id'],
                'type'            => self::OPERATION_REFUND,
                'state'           => $this->formalizeDataState($res),
                'result'          => 1,
                'order_id'        => $transaction_raw_data['transaction']['order_id'],
                'customer_id'     => $transaction_raw_data['transaction']['customer_id'],
                'amount'          => $amount/100,
                'currency_id'     => $transaction_raw_data['transaction']['currency_id'],
                'parent_id'       => $transaction_raw_data['transaction']['id'],
                'create_datetime' => $now,
                'update_datetime' => $now,
            );

            $expected_states = array(
                self::STATE_REFUNDED,
                self::STATE_PARTIAL_REFUNDED,
            );

            if (!in_array($transaction['state'], $expected_states, true)) {
                $transaction['state'] = self::STATE_DECLINED;
                $transaction['result'] = 0;
                $transaction['error'] = ifset($res['Message']); // $this->translateError(isset($res['ErrorCode']))
                $transaction['view_data'] = ifset($res['Details']);
                $response['result'] = -1;
                $response['description'] = $transaction['error'].' '.$transaction['view_data'];
            } elseif ($transaction['state'] === self::STATE_REFUNDED) {
                $transaction['parent_state'] = $transaction['state'];
            }

            if (isset($res['TerminalKey'])) {
                unset($res['TerminalKey']);
            }

            $this->saveTransaction($transaction, [
                // used by $this->formalizeData() and eventually by waPayment->isRefundAvailable()
                // to calculate how large should total refund be
                'captured_amount' => round((
                    $transaction_raw_data['transaction']['amount'] +
                    ifset($transaction_raw_data, 'transaction', 'refunded_amount', 0)
                )*100),
                'Amount' => $amount,
            ] + $res);

            return $response;
        } catch (Exception $ex) {
            $message = sprintf("Error occurred during %s: %s", __METHOD__, $ex->getMessage());
            self::log($this->id, $message);
            return array(
                'result'      => -1,
                'data'        => null,
                'description' => $ex->getMessage(),
            );
        }
    }

    public function recurrent($order_data)
    {
        $order_data = waOrder::factory($order_data);

        $amount = round($order_data['amount'] * 100);

        $c = new waContact($order_data['customer_contact_id']);

        if (!($email = $c->get('email', 'default'))) {
            $email = $this->getDefaultEmail();
        }

        $args = array(
            'Amount'      => $amount,
            'Currency'    => ifset(self::$currencies[$this->currency_id]),
            'OrderId'     => $this->app_id.'_'.$this->merchant_id.'_'.$order_data['order_id'],
            'CustomerKey' => $c->getId(),
            'Description' => ifempty($order_data, 'summary', ''),
            'DATA'        => array(
                'Email' => $email,
            ),
        );
        if ($phone = $c->get('phone', 'default')) {
            $args['DATA']['Phone'] = $phone;
        }

        try {
            $res = $this->apiQuery('Init', $args);

            $payment_id = ifset($res, 'PaymentId', '');

            if (!$payment_id) {
                throw new waPaymentException('Empty payment ID');
            }

            $args = array(
                'PaymentId' => $payment_id,
                'RebillId'  => $order_data['card_native_id'],
            );

            if ($this->getSettings('check_data_tax')) {
                $receipt = $this->getReceiptData($order_data);
                if ($receipt) {
                    $args['Receipt'] = $receipt;
                }
            }

            $res = $this->apiQuery('Charge', $args);

            return array(
                'result'      => true,
                'description' => '',
            );
        } catch (Exception $ex) {
            return array(
                'result'      => false,
                'description' => $ex->getMessage(),
            );
        }


    }

    public function cancel($transaction_raw_data)
    {
        try {
            $transaction = $transaction_raw_data['transaction'];
            $args = array(
                'PaymentId' => $transaction['native_id'],
            );

            $data = $this->apiQuery('Cancel', $args);
            $transaction_data = $this->formalizeData($data);

            $this->saveTransaction($transaction_data, $data);

            return array(
                'result'      => 0,
                'data'        => $transaction_data,
                'description' => '',
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

    public function capture($data)
    {
        $args = array(
            'PaymentId' => $data['transaction']['native_id'],
            'Amount'    => $data['transaction']['amount'] * 100,
        );

        if (!empty($data['order_data'])) {
            $order = waOrder::factory($data['order_data']);

            if ($data['transaction']['currency_id'] != $order->currency) {
                throw new waPaymentException(sprintf('Currency id changed. Expected %s, but get %s.', $data['transaction']['currency_id'], $order->currency));
            }

            $args['Amount'] = round($order->total*100);

            if ($this->getSettings('check_data_tax')) {
                $args['Receipt'] = $this->getReceiptData($order);
            }
        }

        // Callbacks from Tinkoff API are pretty fast and often come before
        // the call to /Confirm endpoint returns.
        // We create wa_transaction record beforehand so that
        // the callback is ignored
        $datetime = date('Y-m-d H:i:s');
        $transaction_model = new waTransactionModel();
        $transaction = $this->saveTransaction([
            'native_id'       => $data['transaction']['native_id'],
            'type'            => self::OPERATION_CAPTURE,
            'result'          => 'unfinished',
            'order_id'        => $data['transaction']['order_id'],
            'customer_id'     => $data['transaction']['customer_id'],
            'amount'          => $args['Amount'] / 100,
            'currency_id'     => $data['transaction']['currency_id'],
            'parent_id'       => $data['transaction']['id'],
            'create_datetime' => $datetime,
            'update_datetime' => $datetime,
            'state'           => $data['transaction']['state'],
        ]);

        try {
            $res = $this->apiQuery('Confirm', $args);

            $response = array(
                'result'      => 0,
                'description' => '',
            );

            $status = ifset($res, 'Status', '');

            if ($status != 'CONFIRMED') {
                $transaction['state'] = self::STATE_DECLINED;
                $transaction['result'] = 0;
                $transaction['error'] = ifset($res['Message']); // $this->translateError(isset($res['ErrorCode']))
                $transaction['view_data'] = ifset($res['Details']);
                $response['result'] = -1;
                $response['description'] = $transaction['error'];
            } else {
                $transaction['result'] = 1;
                $transaction['state'] = self::STATE_CAPTURED;
            }

            $transaction['parent_state'] = $transaction['state'];

            $transaction_model->deleteById($transaction['id']);
            unset($transaction['id']);
            $response['data'] = $this->saveTransaction($transaction, $res);

            return $response;
        } catch (Exception $ex) {
            if (isset($transaction['id'])) {
                $transaction_model->deleteById($transaction['id']);
            }
            return null;
        }
    }

    protected function formalizeDataState($data)
    {
        $state = null;
        switch (ifset($data['Status'])) {
            case 'AUTHORIZED':
                $state = self::STATE_AUTH;
                break;

            case 'CONFIRMED':
                $state = self::STATE_CAPTURED;
                break;

            case 'PARTIAL_REFUNDED':
                $state = self::STATE_PARTIAL_REFUNDED;
                break;

            case 'REFUNDED':
                $state = self::STATE_REFUNDED;
                break;

            case 'REJECTED':
                $state = self::STATE_DECLINED;
                break;

            case 'REVERSED':
                $state = self::STATE_DECLINED;
                break;

            default:
                throw new waException('Invalid transaction status');
        }

        return $state;
    }

    /**
     * Convert transaction raw data to formatted data
     * @param array $data - transaction raw data
     * @return array $transaction_data
     * @throws waException
     */
    protected function formalizeData($data)
    {
        $transaction_data = parent::formalizeData(null);

        $transaction_data['native_id'] = ifset($data['PaymentId']);
        if (empty($data['Status'])) {
            throw new waException('Empty transaction status');
        }
        $transaction_data['state'] = $this->formalizeDataState($data);
        $transaction_data['parent_id'] = null;
        $parent_transaction = null;
        if (!empty($data['PaymentId'])) {
            $parent_transaction = $this->getParentTransaction($data['PaymentId']);
            if ($parent_transaction) {
                $transaction_data['parent_id'] = $parent_transaction['id'];
            }
        }

        switch ($data['Status']) {
            case 'AUTHORIZED':
                if ($this->two_steps) {
                    $transaction_data['type'] = self::OPERATION_AUTH_ONLY;
                } else {
                    $transaction_data['type'] = self::OPERATION_CHECK;
                }
                break;

            case 'CONFIRMED':
                if ($parent_transaction) {
                    $transaction_data['type'] = self::OPERATION_CAPTURE;
                } else {
                    $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
                }
                break;

            case 'PARTIAL_REFUNDED':
                $transaction_data['type'] = self::OPERATION_REFUND;
                // 'refunded_amount' is used by waPayment->isRefundAvailable()
                // It contains total amount refunded so far, including by this transaction.
                if (!empty($data['NewAmount'])) {
                    if (!empty($data['captured_amount'])) {
                        // for partially-captured orders
                        // 'captured_amount' contains amount captured, this may differ from `OriginalAmount`
                        $transaction_data['refunded_amount'] = (intval($data['captured_amount']) - intval($data['NewAmount'])) / 100;
                    } else if (!empty($data['OriginalAmount'])) {
                        // for fully-captured orders
                        // (old plugin versions did not write `captured_amount` to raw data)
                        $transaction_data['refunded_amount'] = (intval($data['OriginalAmount']) - intval($data['NewAmount'])) / 100;
                    }
                }

                break;

            case 'REFUNDED':
                $transaction_data['type'] = self::OPERATION_REFUND;
                break;

            case 'REJECTED':
                $transaction_data['type'] = self::OPERATION_CANCEL;
                break;

            case 'REVERSED':
                $transaction_data['type'] = self::OPERATION_CANCEL;
                break;

            default:
                throw new waException('Invalid transaction status');
        }


        if (!empty($data['Pan'])) {
            $transaction_data['view_data'] = $data['Pan'];
        }

        $transaction_data['amount'] = ifset($data['Amount']) / 100;
        $transaction_data['currency_id'] = $this->currency_id;
        $transaction_data['order_id'] = $this->order_id;
        $transaction_data['result'] = (isset($data['Success']) && $data['Success'] == 'true') ? 1 : 0;
        $error_code = intval(ifset($data['ErrorCode']));

        $transaction_data['error'] = $this->translateError($error_code);
        if (!empty($transaction_data['error'])) {
            $transaction_data['view_data'] = isset($transaction_data['view_data']) ? ($transaction_data['view_data'].'; ') : '';
            $transaction_data['view_data'] .= $transaction_data['error'];
        }

        if (!empty($data['RebillId'])) {
            $transaction_data['card_native_id'] = $data['RebillId'];
            $transaction_data['card_view'] = $data['Pan'];
            if (!empty($data['ExpDate']) && preg_match('/^(\d{2})(\d{2})$/', $data['ExpDate'], $m)) {
                $expire_date = '20'.$m[2].'-'.$m[1].'-'.date('t', strtotime('20'.$m[2].'-'.$m[1].'-01'));
                $transaction_data['card_expire_date'] = $expire_date;
            }
        }
        return $transaction_data;
    }

    private function translateError($error_code)
    {
        $errors = [
            0 => null,
            7 => 'Покупатель не найден',
            53 => 'Обратитесь к продавцу',
            100 => 'Повторите попытку позже',
            101 => 'Не пройдена идентификация 3DS',
            102 => 'Операция отклонена, пожалуйста обратитесь в интернет-магазин или воспользуйтесь другой картой',
            103 => 'Повторите попытку позже',
            119 => 'Превышено кол-во запросов на авторизацию',
            1001 => 'Свяжитесь с банком, выпустившим карту, чтобы провести платеж',
            1003 => 'Неверный merchant ID',
            1004 => 'Карта украдена. Свяжитесь с банком, выпустившим карту',
            1005 => 'Платеж отклонен банком, выпустившим карту',
            1006 => 'Свяжитесь с банком, выпустившим карту, чтобы провести платеж',
            1007 => 'Карта украдена. Свяжитесь с банком, выпустившим карту',
            1012 => 'Такие операции запрещены для этой карты',
            1013 => 'Повторите попытку позже',
            1014 => 'Карта недействительна. Свяжитесь с банком, выпустившим карту',
            1015 => 'Попробуйте снова или свяжитесь с банком, выпустившим карту',
            1030 => 'Повторите попытку позже',
            1033 => 'Истек срок действия карты. Свяжитесь с банком, выпустившим карту',
            1034 => 'Попробуйте повторить попытку позже',
            1041 => 'Карта утеряна. Свяжитесь с банком, выпустившим карту',
            1043 => 'Карта украдена. Свяжитесь с банком, выпустившим карту',
            1051 => 'Недостаточно средств на карте',
            1054 => 'Истек срок действия карты',
            1057 => 'Такие операции запрещены для этой карты',
            1058 => 'Такие операции запрещены для этой карты',
            1059 => 'Подозрение в мошенничестве. Свяжитесь с банком, выпустившим карту',
            1061 => 'Превышен дневной лимит платежей по карте',
            1062 => 'Платежи по карте ограничены',
            1063 => 'Операции по карте ограничены',
            1065 => 'Превышен дневной лимит транзакций',
            1075 => 'Превышено число попыток ввода ПИН-кода',
            1082 => 'Неверный CVV',
            1088 => 'Ошибка шифрования. Попробуйте снова',
            1089 => 'Попробуйте повторить попытку позже',
            1091 => 'Банк, выпустивший карту недоступен для проведения авторизации',
            1093 => 'Подозрение в мошенничестве. Свяжитесь с банком, выпустившим карту',
            1094 => 'Системная ошбка',
            1096 => 'Повторите попытку позже',
            9999 => 'Внутренняя ошибка системы',
        ];

        return array_key_exists($error_code, $errors) ? $errors[$error_code] : 'Неизвестная ошибка ('.$error_code.').';
    }

    private function getParentTransaction($native_id)
    {
        $tm = new waTransactionModel();
        $search = array(
            'native_id' => $native_id,
            'app_id'    => $this->app_id,
            'plugin'    => $this->id,
            'type'      => array(
                self::OPERATION_AUTH_ONLY,
                self::OPERATION_AUTH_CAPTURE,
            ),
        );

        $transactions = $tm->getByFields($search);
        return $transactions ? reset($transactions) : null;
    }

    /**
     * @param waOrder $order
     * @return array|null
     */
    private function getReceiptData(waOrder $order)
    {
        if (!$this->receipt) {
            if (!($email = $order->getContactField('email'))) {
                $email = $this->getDefaultEmail();
            }
            $this->receipt = array(
                'Items'    => array(),
                'Taxation' => $this->getSettings('taxation'),
                'Email'    => $email,
            );
            if ($phone = $order->getContactField('phone')) {
                $this->receipt['Phone'] = sprintf('+%s', preg_replace('/^8/', '7', $phone));
            }
            foreach ($order->items as $item) {
                $item['amount'] = $item['price'] - ifset($item['discount'], 0.0);
                if ($item['price'] > 0 && $item['quantity'] > 0) {

                    switch (ifset($item['type'])) {
                        case 'shipping':
                            $item['payment_object_type'] = $this->payment_object_type_shipping;
                            break;
                        case 'service':
                            $item['payment_object_type'] = $this->payment_object_type_service;
                            break;
                        case 'product':
                        default:
                            $item['payment_object_type'] = $this->payment_object_type_product;
                            break;
                    }

                    $this->receipt['Items'][] = array(
                        'Name'          => mb_substr($item['name'], 0, 64),
                        'Price'         => round($item['amount'] * 100),
                        'Quantity'      => floatval($item['quantity']),
                        'Amount'        => round($item['amount'] * $item['quantity'] * 100),
                        'PaymentMethod' => $this->payment_method_type,
                        'PaymentObject' => $item['payment_object_type'],
                        'Tax'           => $this->getTaxId($item),
                    );
                }

                if (!empty($item['tax_rate']) && (!$item['tax_included'] || !in_array($item['tax_rate'], array(0, 10, 18, 20)))) {
                    return null;
                }
            }
            if ($order->shipping && $order->shipping > 0) {
                $item = array(
                    'tax_rate'     => $order->shipping_tax_rate,
                    'tax_included' => $order->shipping_tax_included,
                );
                $this->receipt['Items'][] = array(
                    'Name'          => mb_substr($order->shipping_name, 0, 64),
                    'Price'         => round($order->shipping * 100),
                    'Quantity'      => 1,
                    'Amount'        => round($order->shipping * 100),
                    'PaymentObject' => $this->payment_object_type_shipping,
                    'PaymentMethod' => $this->payment_method_type,
                    'Tax'           => $this->getTaxId($item),
                );
                if (!empty($item['tax_rate']) && (!$item['tax_included'] || !in_array($item['tax_rate'], array(0, 10, 18, 20)))) {
                    return null;
                }
            }
        }
        return $this->receipt;
    }

    private function getTaxId($item)
    {
        $tax = 'none';
        if (array_key_exists('tax_rate', $item) && array_key_exists('tax_included', $item) && $item['tax_rate'] !== null) {
            if ($item['tax_rate'] == 0) {
                $tax = 'vat0';
            } elseif ($item['tax_included'] && $item['tax_rate'] == 10) {
                $tax = 'vat10';
            } elseif ($item['tax_included'] && $item['tax_rate'] == 18) {
                $tax = 'vat18';
            } elseif ($item['tax_included'] && $item['tax_rate'] == 20) {
                $tax = 'vat20';
            } elseif (!$item['tax_included'] && $item['tax_rate'] == 10) {
                $tax = 'vat110';
            } elseif (!$item['tax_included'] && $item['tax_rate'] == 18) {
                $tax = 'vat118';
            } elseif (!$item['tax_included'] && $item['tax_rate'] == 20) {
                $tax = 'vat120';
            }
        }
        return $tax;
    }

    protected function sanitizeRequest($request)
    {
        if (count($request) <= 1) {
            $json = json_decode(file_get_contents("php://input"), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request = $json;
            }
        }
        return $request;
    }

    protected function getDefaultEmail()
    {
        $mail = new waMail();
        $from = $mail->getDefaultFrom();
        return key($from);
    }

    public function saveSettings($settings = array())
    {
        $settings['terminal_key'] = trim($settings['terminal_key']);
        $settings['terminal_password'] = trim($settings['terminal_password']);
        return parent::saveSettings($settings);
    }

    protected function isTestMode()
    {
        return $this->testmode || 'DEMO' === substr($this->getSettings('terminal_key'), -4);
    }
}
