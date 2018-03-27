<?php

/**
 *
 * @author Webasyst
 * @name tinkoff
 * @description tinkoff Payments Standard Integration
 *
 * @property-read $terminal_key
 * @property-read $terminal_password
 * @property-read $currency_id
 * @property-read $two_steps
 * @property-read $testmode
 * @property-read int $atolonline_on
 * @property-read string $atolonline_sno
 *
 */
class tinkoffPayment extends waPayment implements waIPayment, waIPaymentRefund, waIPaymentRecurrent
{
    private $order_id;
    private $error;
    private $response;
    private $status;
    private $payment_url;
    private $payment_id;
    private $parent_transaction;
    private $send_log = true;
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
        return $this->restPayment($payment_form_data, $order_data, $auto_submit);
    }

    private function restPayment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order_data = waOrder::factory($order_data);

        if (empty($order_data['description_en'])) {
            $order_data['description_en'] = 'Order '.$order_data['order_id'];
        }

        $c = new waContact($order_data['customer_contact_id']);

        if (!($email = $c->get('email', 'default'))) {
            $email = $this->getDefaultEmail();
        }

        $args = array(
            'TerminalKey' => trim($this->terminal_key),
            'Amount'      => round($order_data['amount'] * 100),
            'Currency'    => ifset(self::$currencies[$this->currency_id]),
            'OrderId'     => $this->app_id.'_'.$this->merchant_id.'_'.$order_data['order_id'],
            'CustomerKey' => $c->getId(),
            'Description' => $order_data['summary'],
            'DATA'        => array('Email' => $email),
        );
        if ($phone = $c->get('phone', 'default')) {
            $args['DATA']['Phone'] = $phone;
        }
        if (!empty($order_data['recurrent'])) {
            $args['Recurrent'] .= 'Y';
        }

        if ($this->getSettings('atolonline_on')) {
            $args['Receipt'] = $this->getReceiptData($order_data);
            if (!$args['Receipt']) {
                return _w('Данный вариант платежа недоступен. Воспользуйтесь другим способом оплаты.');
            }
        }

        $this->buildQuery('Init', $args);

        if (!$this->payment_url) {
            return null;
        }

        $view = wa()->getView();

        $view->assign('plugin', $this);
        $view->assign('form_url', $this->payment_url);
        $view->assign('auto_submit', $auto_submit);

        return $view->fetch($this->path.'/templates/payment.html');
    }


    /**
     * Builds a query string and call sendRequest method.
     * Could be used to custom API call method.
     *
     * @param string $path API method name
     * @param mixed $args query params
     *
     * @return mixed
     * @throws HttpException
     */
    public function buildQuery($path, $args)
    {
        $url = $this->getEndpointUrl();
        if (is_array($args)) {
            if (!array_key_exists('TerminalKey', $args)) {
                $args['TerminalKey'] = $this->terminal_key;
            }
            if (!array_key_exists('Token', $args)) {
                $args['Token'] = $this->genToken($args);
            }
        }
        $url = $this->combineUrl($url, $path);

        return $this->sendRequest($url, $args);
    }

    /**
     * Combines parts of URL. Simply gets all parameters and puts '/' between
     *
     * @return string
     */
    private function combineUrl()
    {
        $args = func_get_args();
        $url = '';
        foreach ($args as $arg) {
            if (is_string($arg)) {
                if ($arg[strlen($arg) - 1] !== '/') {
                    $arg .= '/';
                }
                $url .= $arg;
            } else {
                continue;
            }
        }
        return $url;
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

    private function checkToken($args)
    {
        if (!$this->getSettings('terminal_password')) {
            return false;
        }
        $inp_token = $token = '';
        if (!empty($args['Token'])) {
            $inp_token = $args['Token'];
            unset($args['Token']);
        }
        $args['Password'] = $this->getSettings('terminal_password');

        ksort($args);

        $args = array_map(wa_lambda('$el', 'return is_bool($el) ? ($el ? "true" : "false") : $el;'), $args);

        $token = implode('', $args);
        $token = hash('sha256', $token);

        if ($inp_token && $inp_token === $token) {
            return true;
        }
        return false;
    }

    /**
     * Main method. Call API with params
     *
     * @param string $api_url API Url
     * @param array $args API params
     *
     * @return mixed
     * @throws HttpException
     */
    private function sendRequest($api_url, $args)
    {
        $this->error = '';
        //todo add string $args support
        //$proxy = 'http://192.168.5.22:8080';
        //$proxyAuth = '';
        if (is_array($args)) {
            $args = json_encode($args);
        }
        //Debug::trace($args);
        if ($curl = curl_init()) {
            curl_setopt($curl, CURLOPT_URL, $api_url);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $args);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            $out = curl_exec($curl);

            $info = curl_getinfo($curl);

            $this->response = $out;
            $json = json_decode($out);

            if ($json) {
                if (@$json->ErrorCode !== '0') {
                    $this->error = @$json->Details;
                } else {
                    $this->payment_url = @$json->PaymentURL;
                    $this->payment_id = @$json->PaymentId;
                    $this->status = @$json->Status;
                }
            }
            curl_close($curl);

            if ($this->testmode || $this->send_log) {
                waLog::log('Sent to: '.$api_url."\n".$args, 'payment/tinkoffSend.log');
                waLog::log('Received: http_code: '.ifset($info['http_code']).'; response: '.$out, 'payment/tinkoffSend.log');
            }
            return $json ? $json : $out;

        } else {
            throw new waException('Cannot create connection to '.$api_url.' with args '.$args);
        }
    }

    protected function callbackInit($request)
    {
        waLog::log("Received:\nREQUEST_URI: ".waRequest::server('REQUEST_URI').";\nphp input: ".file_get_contents("php://input"), 'payment/tinkoffCallback.log');

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
     * IPN (Instant Payment Notification)
     * @throws waPaymentException
     * @param $data - get from gateway
     * @return array
     */
    protected function callbackHandler($data)
    {
        $data = $this->sanitizeRequest($data);

        if (empty($data['Token']) && !empty($data['PaymentId'])) {
            if (!key_exists('Success', $data)) {
                waLog::log('Key not exists "Success"', 'payment/tinkoffCallback.log');
                return;
            }
            $type = $data['Success'] == 'true' ? waAppPayment::URL_SUCCESS : waAppPayment::URL_FAIL;
            $url = $this->getAdapter()->getBackUrl($type, array('order_id' => $this->order_id));
            waLog::log('Redirecter to '.$url, 'payment/tinkoffCallback.log');
            return array('redirect' => $url);
        }
        if (!$this->checkToken($data)) {
            waLog::log('Invalid token', 'payment/tinkoffCallback.log');
            return;
        }
        if (!empty($data['PaymentId'])) {
            $this->getParentTransaction($data['PaymentId']);
        }

        try {
            $transaction_data = $this->formalizeData($data);
        } catch (waException $e) {
            waLog::log('Formalize error: '.$e->getMessage(), 'payment/tinkoffCallback.log');
            return;
        }
        // accept transaction
        if ($this->parent_transaction) {
            $transaction_data['parent_id'] = $this->parent_transaction['id'];
        }
        $supported_operations = $this->supportedOperations();

        // check transaction type
        if (!in_array($transaction_data['type'], $supported_operations)) {
            waLog::log('Unsupported operation: '.$transaction_data['type'], 'payment/tinkoffCallback.log');
            return;
        }
        $tm = new waTransactionModel();
        $old_transaction = $tm->getByFields(array(
            'native_id' => $transaction_data['native_id'],
            'plugin'    => $this->id,
            'type'      => $transaction_data['type']
        ));
        if ($old_transaction) {
            waLog::log('Old transaction found', 'payment/tinkoffCallback.log');
            return; // exclude transactions duplicates
        }
        $transaction_data['recurrent_id'] = ifset($data['RebillId']);

        $transaction_data = $this->saveTransaction($transaction_data, $data);

        switch ($transaction_data['type']) {
            case self::OPERATION_AUTH_ONLY:
                if ($transaction_data['result']) {
                    $app_payment_method = self::CALLBACK_NOTIFY;
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
                $app_payment_method = self::CALLBACK_REFUND;
                break;
            case self::OPERATION_CANCEL:
                $app_payment_method = self::CALLBACK_CANCEL;
                break;
            default:
                return;
        }
        $this->execAppCallback($app_payment_method, $transaction_data);
    }

    public function refund($transaction_raw_data)
    {
        $amount = round($transaction_raw_data['refund_amount'] * 100);

        $args = array(
            'TerminalKey' => $this->terminal_key,
            'PaymentId'   => $transaction_raw_data['transaction']['native_id'],
            'Amount'      => $amount,
            //'Description' => '',
        );

        $res = $this->buildQuery('Cancel', $args);

        if (empty($res->Success) || $res->Success != 'true') {
            return;
        }
        $res = (array)$res;

        $response = array('result' => 0, 'data' => $res, 'description' => '');
        $now = date('Y-m-d H:i:s');

        $amount = $transaction_raw_data['transaction']['amount'];
        if (isset($res['OriginalAmount']) && isset($res['NewAmount'])) {
            $amount = ($res['OriginalAmount'] - $res['NewAmount']) / 100;
        }

        $transaction = array(
            'native_id'       => $transaction_raw_data['transaction']['native_id'],
            'type'            => self::OPERATION_REFUND,
            'result'          => 1,
            'order_id'        => $transaction_raw_data['transaction']['order_id'],
            'customer_id'     => $transaction_raw_data['transaction']['customer_id'],
            'amount'          => $amount,
            'currency_id'     => $transaction_raw_data['transaction']['currency_id'],
            'parent_id'       => $transaction_raw_data['transaction']['id'],
            'create_datetime' => $now,
            'update_datetime' => $now,
        );
        if ($this->status != 'REFUNDED' && $this->status != 'PARTIAL_REFUNDED') {
            $transaction['state'] = 'DECLINED';
            $transaction['result'] = 0;
            $transaction['error'] = ifset($res['Message']); // $this->translateError(isset($res['ErrorCode']))
            $transaction['view_data'] = ifset($res['Details']);
            $response['result'] = -1;
            $response['description'] = $transaction['error'];
        } else {
            $transaction['parent_state'] = self::STATE_REFUNDED;
        }
        if (isset($res['TerminalKey'])) {
            unset($res['TerminalKey']);
        }
        $this->saveTransaction($transaction, $res);

        return $response;
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
            'TerminalKey' => $this->terminal_key,
            'Amount'      => $amount,
            'Currency'    => ifset(self::$currencies[$this->currency_id]),
            'OrderId'     => $this->app_id.'_'.$this->merchant_id.'_'.$order_data['order_id'],
            'CustomerKey' => $c->getId(),
            'Description' => $order_data['summary'],
            'DATA'        => array('Email' => $email),
        );
        if ($phone = $c->get('phone', 'default')) {
            $args['DATA']['Phone'] = $phone;
        }

        $res = $this->buildQuery('Init', $args);

        if (!$this->payment_id) {
            if ($this->error || empty($res->Success) || $res->Success != 'true') {
                $error = array(
                    ifset($res->Message),
                    ifset($res->Details),
                    $this->translateError(ifset($res->ErrorCode))
                );
                return array(
                    'result'      => false,
                    'description' => join(' ', $error),
                );
            }
            return array(
                'result'      => false,
                'description' => 'Empty payment ID',
            );
        }

        $args = array(
            'TerminalKey' => $this->terminal_key,
            'PaymentId'   => $this->payment_id,
            'RebillId'    => $order_data['recurrent_id'],
        );
        if ($this->getSettings('atolonline_on')) {
            $receipt = $this->getReceiptData($order_data);
            if ($receipt) {
                $args['Receipt'] = $receipt;
            }
        }

        $res = $this->buildQuery('Charge', $args);

        if ($this->error || empty($res->Success) || $res->Success != 'true') {
            $error = array(
                ifset($res->Message),
                ifset($res->Details),
                $this->translateError(ifset($res->ErrorCode))
            );
            return array(
                'result'      => false,
                'description' => join(' ', $error),
            );
        }
        return array(
            'result'      => true,
            'description' => '',
        );
    }

    public function cancel($data)
    {
        try {
            $transaction_data = $this->formalizeData($data);
        } catch (waException $e) {
            waLog::log('Formalize error: '.$e->getMessage(), 'payment/tinkoffPayment.log');
            return;
        }
        $transaction_data['order_id'] = $data['transaction']['order_id'];
        $transaction_data['parent_id'] = $data['transaction']['id'];
        $transaction_data['customer_id'] = $data['transaction']['customer_id'];
        $transaction_data['type'] = self::OPERATION_CANCEL;
        $transaction_data['parent_state'] = self::STATE_CANCELED;

        $this->saveTransaction($transaction_data, $data);

        return array('result' => 0);
    }

    public function capture($data)
    {
        $args = array(
            'TerminalKey' => $this->terminal_key,
            'PaymentId'   => $data['transaction']['native_id'],
            'Amount'      => $data['transaction']['amount'] * 100,
            //'Description' => '',
        );
        $res = $this->buildQuery('Confirm', $args);

        if (empty($res->Success) || $res->Success != 'true') {
            return;
        }
        $res = (array)$res;

        $response = array('result' => 0, 'data' => $res, 'description' => '');
        $now = date('Y-m-d H:i:s');

        $this->getParentTransaction($data['PaymentId']);

        $transaction = array(
            'native_id'       => $data['transaction']['native_id'],
            'type'            => self::OPERATION_CAPTURE,
            'result'          => 1,
            'order_id'        => $data['transaction']['order_id'],
            'customer_id'     => $data['transaction']['customer_id'],
            'amount'          => $data['transaction']['amount'],
            'currency_id'     => $data['transaction']['currency_id'],
            'parent_id'       => $data['transaction']['id'],
            'create_datetime' => $now,
            'update_datetime' => $now,
            'state'           => self::STATE_CAPTURED,
        );
        if ($this->status != 'CONFIRMED') {
            $transaction['state'] = self::STATE_DECLINED;
            $transaction['result'] = 0;
            $transaction['error'] = ifset($res['Message']); // $this->translateError(isset($res['ErrorCode']))
            $transaction['view_data'] = ifset($res['Details']);
            $response['result'] = -1;
            $response['description'] = $transaction['error'];
        }
        if (isset($res['TerminalKey'])) {
            unset($res['TerminalKey']);
        }
        $transaction['parent_state'] = $transaction['state'];

        $this->saveTransaction($transaction, $res);

        return $response;
    }

    /**
     * Convert transaction raw data to formatted data
     * @param array $data - transaction raw data
     * @throws waException
     * @return array $transaction_data
     */
    protected function formalizeData($data)
    {
        $transaction_data = parent::formalizeData(null);

        $transaction_data['native_id'] = ifset($data['PaymentId']);
        if (empty($data['Status'])) {
            throw new waException('Invalid transaction status');
        }
        $transaction_data['state'] = null;
        $transaction_data['parent_id'] = null;
        switch ($data['Status']) {
            case 'AUTHORIZED':
                $transaction_data['state'] = self::STATE_AUTH;
                break;
            case 'CONFIRMED':
                $transaction_data['state'] = self::STATE_CAPTURED;
                break;
            case 'REFUNDED':
                $transaction_data['state'] = self::STATE_REFUNDED;
                break;
            case 'REJECTED':
                $transaction_data['state'] = self::STATE_DECLINED;
                break;
            case 'REVERSED':
                $transaction_data['state'] = self::STATE_DECLINED;
                break;
            default:
                throw new waException('Invalid transaction status');
        }
        //if ($this->parent_transaction) {
        //    $transaction_data['parent_state'] = $transaction_data['state'];
        //}
        switch ($data['Status']) {
            case 'AUTHORIZED':
                if ($this->two_steps) {
                    $transaction_data['type'] = self::OPERATION_AUTH_ONLY;
                } else {
                    $transaction_data['type'] = self::OPERATION_CHECK;
                    //$transaction_data['native_id'] = null;
                }
                break;
            case 'CONFIRMED':
                $transaction_data['type'] = self::OPERATION_CAPTURE;
                break;
            case 'REJECTED':
                if ($this->parent_transaction) {
                    $transaction_data['type'] = self::OPERATION_CANCEL;
                } else {
                    if ($this->two_steps) {
                        $transaction_data['type'] = self::OPERATION_AUTH_ONLY;
                    } else {
                        $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
                    }
                }
                break;
            case 'REVERSED':
                $transaction_data['type'] = self::OPERATION_CANCEL;
                break;
            case 'REFUNDED':
                $transaction_data['type'] = self::OPERATION_REFUND;
                break;
            default:
                throw new waException('Invalid transaction status');
        }
        if (!$this->parent_transaction && $data['Status'] == 'CONFIRMED') {
            $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
        }
        if (!empty($data['Pan'])) {
            $transaction_data['view_data'] = 'Card: '.$data['Pan'];
        }
        $transaction_data['amount'] = ifset($data['Amount']) / 100;
        $transaction_data['currency_id'] = $this->currency_id;
        $transaction_data['order_id'] = $this->order_id;
        $transaction_data['result'] = (isset($data['Success']) && $data['Success'] == 'true') ? 1 : 0;
        $error_code = intval(ifset($data['ErrorCode']));
        $transaction_data['error'] = $this->translateError($error_code);
        if (!empty($transaction_data['error'])) {
            $transaction_data['view_data'] = (isset($transaction_data['view_data']) ? ($transaction_data['view_data'].'; ') : '').$transaction_data['error'];
        }
        $transaction_data['recurrent_id'] = ifset($data['RebillId']);

        return $transaction_data;
    }

    private function translateError($error_code)
    {
        $errors = array(
            0    => null,
            99   => 'Воспользуйтесь другой картой — банк, выпустивший карту, отклонил операцию.',
            101  => 'Не пройдена идентификация 3DS.',
            1006 => 'Проверьте реквизиты или воспользуйтесь другой картой.',
            1012 => 'Воспользуйтесь другой картой.',
            1013 => 'Повторите попытку позже.',
            1014 => 'Неверно введены реквизиты карты. Проверьте корректность введенных данных.',
            1030 => 'Повторите попытку позже.',
            1033 => 'Проверьте реквизиты или воспользуйтесь другой картой.',
            1034 => 'Воспользуйтесь другой картой — банк, выпустивший карту, отклонил операцию.',
            1041 => 'Воспользуйтесь другой картой — банк, выпустивший карту, отклонил операцию.',
            1043 => 'Воспользуйтесь другой картой — банк, выпустивший карту, отклонил операцию.',
            1051 => 'Недостаточно средств на карте.',
            1054 => 'Проверьте реквизиты или воспользуйтесь другой картой.',
            1057 => 'Воспользуйтесь другой картой — банк, выпустивший карту, отклонил операцию.',
            1065 => 'Воспользуйтесь другой картой — банк, выпустивший карту, отклонил операцию.',
            1082 => 'Проверьте реквизиты или воспользуйтесь другой картой.',
            1089 => 'Воспользуйтесь другой картой — банк, выпустивший карту, отклонил операцию.',
            1091 => 'Воспользуйтесь другой картой.',
            1096 => 'Повторите попытку позже.',
            9999 => 'Внутренняя ошибка системы.',
        );
        return array_key_exists($error_code, $errors) ? $errors[$error_code] : 'Неизвестная ошибка ('.$error_code.').';
    }

    private function getParentTransaction($native_id)
    {
        $tm = new waTransactionModel();
        $sql = "SELECT * FROM {$tm->getTableName()} WHERE
                native_id = ? AND plugin = ? AND type IN('"
            .self::OPERATION_AUTH_CAPTURE."', '".self::OPERATION_AUTH_ONLY."')";
        $this->parent_transaction = $tm->query($sql, $native_id, $this->id)->fetchAssoc();
    }

    public static function getAtolonlineSnoBlockHtml($name, $value)
    {
        $view = wa()->getView();
        $view->assign(array(
            'options'  => array(
                array('value' => '', 'title' => 'выберите значение'),
                array('value' => 'osn', 'title' => 'общая СН'),
                array('value' => 'usn_income', 'title' => 'упрощенная СН (доходы)'),
                array('value' => 'usn_income_outcome', 'title' => 'упрощенная СН (доходы минус расходы)'),
                array('value' => 'envd', 'title' => 'единый налог на вмененный доход'),
                array('value' => 'esn', 'title' => 'единый сельскохозяйственный налог'),
                array('value' => 'patent', 'title' => 'патентная СН'),
            ),
            'selected' => $value['value'],
        ));
        return $view->fetch(waConfig::get('wa_path_plugins').'/payment/tinkoff/templates/atolonline_sno.html');
    }

    /**
     * @param waOrder $order
     * @return array|null
     */
    private function getReceiptData(waOrder $order)
    {
        if (!$this->receipt) {
            //if (!($email = $order->getContactField('email')) && ($phone = $order->getContactField('phone'))) {
            //    $email = sprintf('+%s', preg_replace('@^8@', '7', $phone));
            //}
            if (!($email = $order->getContactField('email'))) {
                $email = $this->getDefaultEmail();
            }
            $this->receipt = array(
                'Items'    => array(),
                'Taxation' => $this->getSettings('atolonline_sno'),
                'Email'    => $email,
            );
            if ($phone = $order->getContactField('phone')) {
                $this->receipt['Phone'] = sprintf('+%s', preg_replace('/^8/', '7', $phone));
            }
            foreach ($order->items as $item) {
                $item['amount'] = $item['price'] - ifset($item['discount'], 0.0);
                if ($item['price'] > 0) {
                    $this->receipt['Items'][] = array(
                        'Name'     => mb_substr($item['name'], 0, 64),
                        'Price'    => round($item['amount'] * 100),
                        'Quantity' => floatval($item['quantity']),
                        'Amount'   => round($item['amount'] * $item['quantity'] * 100),
                        'Tax'      => $this->getTaxId($item),
                    );
                }

                if (!empty($item['tax_rate']) && (!$item['tax_included'] || !in_array($item['tax_rate'], array(0, 10, 18)))) {
                    return null;
                }
            }
            if ($order->shipping && $order->shipping > 0) {
                $item = array(
                    'tax_rate'     => $order->shipping_tax_rate,
                    'tax_included' => $order->shipping_tax_included,
                );
                $this->receipt['Items'][] = array(
                    'Name'     => mb_substr($order->shipping_name, 0, 128),
                    'Price'    => round($order->shipping * 100),
                    'Quantity' => 1,
                    'Amount'   => round($order->shipping * 100),
                    'Tax'      => $this->getTaxId($item),
                );
                if (!empty($item['tax_rate']) && (!$item['tax_included'] || !in_array($item['tax_rate'], array(0, 10, 18)))) {
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
            } elseif (!$item['tax_included'] && $item['tax_rate'] == 10) {
                $tax = 'vat110';
            } elseif (!$item['tax_included'] && $item['tax_rate'] == 18) {
                $tax = 'vat118';
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
}
