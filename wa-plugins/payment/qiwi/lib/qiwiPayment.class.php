<?php

/**
 * @package waPlugins
 * @subpackage Payment
 * @name QIWI
 * @description QIWI payment module
 * @payment-type online
 *
 * @property-read string $login
 * @property-read string $api_login
 * @property-read string $password
 * @property-read string $sign_password
 * @property-read string $prv_name
 * @property-read string $lifetime
 * @property-read string $alarm
 * @property-read string $prefix
 * @property-read string $customer_phone
 * @property-read string $test
 * @property-read string $protocol
 *
 */
class qiwiPayment extends waPayment implements waIPaymentCancel, waIPaymentRefund
{
    const SOAP = 'soap';
    const REST = 'rest';
    private $order_id;
    private $callback_protocol;
    private $callback_test = false;
    private $debug = false;

    /**
     * @var string
     */
    private $txn;
    /**
     * @var string
     */
    private $status;
    /**
     * @var array
     */
    private $post;

    public function allowedCurrency()
    {
        return ($this->protocol == self::SOAP) ? 'RUB' : true;
    }

    public function supportedOperations()
    {
        return array(
            self::OPERATION_AUTH_CAPTURE,
            self::OPERATION_HOSTED_PAYMENT_AFTER_ORDER,
        );
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $result = null;
        switch ($this->protocol) {
            case self::SOAP:
                $result = $this->httpPayment($payment_form_data, $order_data, $auto_submit);
                break;
            case self::REST:
                $result = $this->restPayment($payment_form_data, $order_data, $auto_submit);
                break;
        }
        return $result;
    }

    public function cancel($transaction_raw_data)
    {
        $result = null;
        switch ($this->protocol) {
            case self::SOAP:
                $result = $this->soapCancel($transaction_raw_data);
                break;
            case self::REST:
                $result = $this->restCancel($transaction_raw_data);
                break;
        }
        return $result;
    }

    public function refund($transaction_raw_data)
    {
        $result = null;
        switch ($this->protocol) {
            case self::REST:
                $result = $this->restRefund($transaction_raw_data);
                break;
        }
        return $result;
    }

    protected function init()
    {
        $autoload = waAutoload::getInstance();
        $autoload->add("IShopServerWSService", "wa-plugins/payment/qiwi/vendors/qiwi/IShopServerWSService.php");
        $autoload->add("IShopClientWSService", "wa-plugins/payment/qiwi/vendors/qiwi/IShopClientWSService.php");
        $autoload->add("nusoap_base", "wa-plugins/payment/qiwi/vendors/nusoap/nusoap.php");
        return parent::init();
    }

    protected function callbackInit($request)
    {
        $pattern = "@^(_TEST_)?([a-z]+)_(\\d+)_(.+)$@";
        $this->post = !empty($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : null;
        /**
         *
         * Request
         * @var SimpleXMLElement
         */
        if ($this->post && empty($request['bill_id'])) {
            $xml = new SimpleXMLElement($this->post);


            if ($txn_xpath = $xml->xpath('/soap:Envelope/soap:Body[1]/*[1]/txn[1]')) {
                $txn = (string)reset($txn_xpath);
                if ($txn && preg_match($pattern, $txn, $match)) {
                    $this->app_id = $match[2];
                    $this->merchant_id = $match[3];
                    $this->order_id = $match[4];
                    $this->callback_test = !empty($match[1]);
                }

                if ($update_bill = $xml->xpath('/soap:Envelope/soap:Body[1]/*[1]')) {
                    self::log($this->id, reset($update_bill)->asXml());
                }
            }
            $this->callback_protocol = self::SOAP;
        } elseif (!empty($request['order']) && preg_match($pattern, $request['order'], $match)) {
            $this->app_id = $match[2];
            $this->merchant_id = $match[3];
            $this->order_id = $match[4];

        } elseif (!empty($request['bill_id']) && preg_match($pattern, $request['bill_id'], $match)) {
            if (empty($match[1]) || $this->test) {
                $this->app_id = $match[2];
                $this->merchant_id = $match[3];
                $this->order_id = $match[4];
                $this->callback_test = !empty($match[1]);
            } else {
                self::log(
                    $this->id,
                        'Ignore test request from QIWI',
                        array(
                            'app_id'      => $match[2],
                            'merchant_id' => $match[3],
                            'order_id'    => $match[4],
                        )
                );
            }
            $this->callback_protocol = self::REST;
        } elseif (isset($request['wsdl'])) {
            $this->callback_protocol = self::SOAP;
        }
        return parent::callbackInit($request);
    }

    /**
     *
     * @param $data - get from gateway
     * @return array
     */
    protected function callbackHandler($data)
    {
        if ($this->prefix) {
            $pattern = wa_make_pattern($this->prefix, '@');
            $pattern = "@^{$pattern}(.+)$@";
            $order_id = null;
            if (preg_match($pattern, $this->order_id, $matches)) {
                $this->order_id = $matches[1];
            }
        }
        $result = null;
        if (!empty($data['result']) && $this->order_id) {
            //handle customer redirection
            $transaction_data = array(
                'order_id' => $this->order_id,
            );
            $type = ($data['result'] == 'success') ? waAppPayment::URL_SUCCESS : waAppPayment::URL_FAIL;
            $result = array();
            $result['url'] = $this->getAdapter()->getBackUrl($type, $transaction_data);
            $result['template'] = $this->path.'/templates/result.html';
        } else {
            switch ($this->callback_protocol) {
                case self::SOAP:
                    $result = $this->soapCallbackHandler($data);
                    break;
                case self::REST:
                    $result = $this->restCallbackHandler($data);
                    break;
            }
        }
        return $result;
    }

    protected function callbackExceptionHandler(Exception $ex)
    {
        switch ($case = $this->callback_protocol) {
            case self::SOAP:
                $result = parent::callbackExceptionHandler($ex);
                break;
            case self::REST:
                $result = array(
                    'template'    => $this->path.'/templates/resultRest.html',
                    'result_code' => $ex->getCode(),
                    'header'      => array(
                        'Content-type' => 'text/xml; charset=utf-8;',
                    ),
                );
                break;
            default:
                $result = array(
                    'error' => 'unknown protocol',
                );
        }
        return $result;
    }


    /**
     * @param array|checkBillResponse $result
     * @return array
     */
    protected function formalizeData($result)
    {
        $transaction_data = parent::formalizeData(null);
        switch (ifempty($this->callback_protocol, $this->protocol)) {
            case self::SOAP:
                $transaction_data['native_id'] = $this->txn;
                $transaction_data['amount'] = is_object($result) && property_exists(get_class($result), 'amount') && !empty($result->amount) ? $result->amount : 0;
                $transaction_data['amount'] = str_replace(',', '.', $transaction_data['amount']);
                $transaction_data['currency_id'] = 'RUB';
                $transaction_data['order_id'] = $this->order_id;
                if (is_object($result) && property_exists(get_class($result), 'user') && !empty($result->user)) {
                    $transaction_data['view_data'] = 'Phone: '.$result->user;
                }
                if (is_object($result) && property_exists(get_class($result), 'status') && !empty($result->status)) {
                    $transaction_data['view_status'] = $this->getBillCodeDescription(intval($result->status));
                }
                break;
            case self::REST:
                $transaction_data['native_id'] = $result['bill_id'];
                $transaction_data['order_id'] = $this->order_id;
                $transaction_data['amount'] = $result['amount'];
                $transaction_data['currency_id'] = $result['ccy'];
                $transaction_data['view_data'] = 'Phone: '.preg_replace('@^tel:@', '', $result['user']);
                break;
        }
        if ($this->callback_test) {
            if (!isset($transaction_data['view_data'])) {
                $transaction_data['view_data'] = '';
            }
            $transaction_data['view_data'] .= ' ТЕСТОВЫЙ ЗАПРОС';
        }

        return $transaction_data;
    }

    protected function getEndpointUrl($type = 'request', $params = array())
    {
        $url = null;
        switch ($this->protocol) {
            case self::SOAP:
                if ($type == 'request') {
                    $url = 'https://ishop.qiwi.ru/services/ishop';
                } else {
                    $url = 'https://w.qiwi.ru/setInetBill_utf.do';
                }
                break;
            case self::REST:
                if ($type == 'request') {
                    $url = "https://w.qiwi.com/api/v2/prv/{$this->login}/bills/".urlencode(ifempty($params['order_id']));
                } else {
                    $url_params = array(
                        'shop'        => $this->login,
                        'transaction' => ifempty($params['native_id']),
                        'successUrl'  => $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $params),
                        'failUrl'     => $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $params),

                    );
                    $url = 'https://w.qiwi.com/order/external/main.action?'.http_build_query($url_params);
                }
                break;
        }
        return $url;
    }

    private function restPayment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order_data = waOrder::factory($order_data);
        $transaction_model = new waTransactionModel();
        $this->order_id = $order_data->id;
        $search = array(
            'app_id'   => $this->app_id,
            'plugin'   => $this->id,
            'order_id' => $this->order_id,
        );
        $transaction_data = $transaction_model->getByField($search, 'id');
        ksort($transaction_data);
        $transaction_data = end($transaction_data);

        $messages = array();
        if (empty($transaction_data)) {

            if (!empty($payment_form_data['customer_phone'])) {
                $payment_form_data['customer_phone'] = preg_replace('@\D+@', '', $payment_form_data['customer_phone']);
                if (preg_match('@\d{11}@', $payment_form_data['customer_phone'])) {
                    try {
                        $transaction_data = $this->restAuth($payment_form_data, $order_data);
                    } catch (waPaymentException $ex) {
                        $messages[] = $ex->getMessage();
                    }
                } else {
                    $messages[] = 'Неверный номер телефона, используйте только цифры без пробелов и разделителей';
                }
            }
        } elseif (($transaction_data['state'] == self::STATE_AUTH) && empty($payment_form_data['customer_phone'])) {
            $time = min(time() - strtotime($transaction_data['create_datetime']), time() - strtotime($transaction_data['update_datetime'])) / 3600;
            if ($time > min($this->lifetime, 2)) {
                try {
                    $transaction_data = $this->restAuth(array(), $order_data);
                } catch (waPaymentException $ex) {
                    if ($ex->getCode() == 210) {
                        unset($transaction_data['native_id']);
                    } else {
                        $messages[] = $ex->getMessage();
                    }
                }
            }
        }

        if (!empty($transaction_data['native_id'])) {
            if ($transaction_data['state'] == self::STATE_CAPTURED) {
                //TODO reload page
                //wa()->getResponse()->redirect('');
                $message = 'Заказ уже оплачен';
            } elseif ($transaction_data['state'] == self::STATE_AUTH) {
                $url = $this->getEndpointUrl('form', array('native_id' => $transaction_data['native_id']));
                if ($auto_submit) {
                    wa()->getResponse()->redirect($url);
                    return null;
                } else {
                    $view = wa()->getView();
                    $view->assign('form_url', $url);
                    $view->assign('messages', $messages);
                    return $view->fetch($this->path.'/templates/paymentRest.html');
                }
            } elseif (in_array($transaction_data['state'], array(self::STATE_DECLINED, self::STATE_CANCELED))) {
                $transaction_data_model = new waTransactionDataModel();
                $search = array(
                    'transaction_id' => $transaction_data['id'],
                    'field_id'       => 'status',
                );
                $raw_status = $transaction_data_model->getByField($search);

                switch (ifempty($raw_status['value'])) {
                    case 'expired':
                        /** Время жизни счета истекло. Счет не оплачен. **/
                        $message = sprintf('Время жизни счета истекло. Счет %s не оплачен.', $order_data->id_str);

                        break;
                    case 'unpaid':
                        /** Ошибка при проведении оплаты. Счет не оплачен. **/
                        $message = sprintf('Ошибка при проведении оплаты. Счет %s не оплачен.', $order_data->id_str);
                        break;
                    case 'rejected':
                        /**Счет отклонен.**/
                        $message = sprintf('Счет %s отклонен', $order_data->id_str);
                        break;
                    default:
                        $message = sprintf('Неизвестное состояние для счета %s', $order_data->id_str);
                        break;
                }
            } else {
                $message = 'Возможно, заказ уже оплачен';
            }
            return $message;
        } else {
            if (!empty($payment_form_data['customer_phone'])) {
                $mobile_phone = $payment_form_data['customer_phone'];
            } else {
                $mobile_phone = preg_replace('/[\D]+/', '', $order_data->getContactField($this->customer_phone, 'default'));
            }
            $view = wa()->getView();
            $view->assign('mobile_phone', $mobile_phone);
            $view->assign('messages', $messages);
            return $view->fetch($this->path.'/templates/paymentRest.html');
        }

    }

    /**
     * @param array $payment_form_data
     * @param waOrder $order_data
     * @return array|null
     * @throws waPaymentException
     */
    private function restAuth($payment_form_data, $order_data)
    {
        $transaction_data = null;
        if (empty($payment_form_data['customer_phone'])) {
            $data = array();
        } else {
            $data = array(
                'user'     => 'tel:+'.$payment_form_data['customer_phone'],
                'amount'   => number_format($order_data->total, 3, '.', ''),
                'ccy'      => $order_data->currency,
                'lifetime' => date('c', time() + 3600 * max(1, (int)$this->lifetime)),
                'comment'  => substr($order_data->description, 0, 255),
                'prv_name' => substr($this->prv_name, 0, 100),
            );
        }
        $params = array(
            'order_id' => $this->getInvoiceId($order_data->id),
        );
        $messages = array();
        $response = $this->restQuery($params, $data);
        $code = 0;
        if ($response) {

            if (!empty($response['response']['bill'])) {
                $bill = $response['response']['bill'];
                $transaction_data = array(
                    'type'     => self::OPERATION_AUTH_ONLY,
                    'state'    => self::STATE_AUTH,
                    'order_id' => $order_data->id,
                );
                $this->restHandleBillStatus($bill);
                $transaction_data += $this->formalizeData($bill);
                switch ($bill['status']) {
                    case 'waiting':
                        /**Счет выставлен, ожидает оплаты.*/

                        $transaction_data = $this->saveTransaction($transaction_data, $bill);
                        $this->execAppCallback(self::CALLBACK_NOTIFY, $transaction_data);
                        break;
                    case 'paid':
                        /**Счет оплачен.*/
                        $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
                        $transaction_data['state'] = self::STATE_CAPTURED;
                        $transaction_data = $this->saveTransaction($transaction_data, $bill);
                        $this->execAppCallback(self::CALLBACK_PAYMENT, $transaction_data);
                        break;
                    case 'rejected':
                        /**Счет отклонен.**/
                        $messages[] = sprintf('Счет %s отклонен', $params['order_id']);
                        break;
                    case 'expired':
                        /** Время жизни счета истекло. Счет не оплачен. **/
                        $messages[] = sprintf('Время жизни счета истекло. Счет %s не оплачен.', $params['order_id']);
                        break;
                    case 'unpaid':
                        /** Ошибка при проведении оплаты. Счет не оплачен. **/
                        $messages[] = sprintf('Ошибка при проведении оплаты. Счет %s не оплачен.', $params['order_id']);
                        break;
                }

                if (!empty($bill['error'])) {
                    $messages[] = $this->getResponseCodeDescription($bill['error']);
                }

            } else {
                if ($data) {
                    $messages[] = sprintf("Произошла ошибка при выставлении счета %s", $params['order_id']);
                } else {
                    $messages[] = sprintf("Произошла ошибка при проверке счета %s", $params['order_id']);
                }
                if (!empty($response['response']['result_code'])) {
                    $messages[] = $this->getResponseCodeDescription($response['response']['result_code']);
                    $code = intval($response['response']['result_code']);
                }
                if (!empty($response['response']['description'])) {
                    $messages[] = $response['response']['description'];
                }
            }
        } else {
            $messages[] = "Произошла ошибка при выставлении счета";
        }


        if ($messages) {
            self::log($this->id, implode("\n", $messages)."\n".$code);
            throw new waPaymentException(implode("\n", $messages), $code);
        }
        return $transaction_data;
    }

    private function restCancel($transaction_raw_data)
    {
        $result = false;
        //check bill status
        $params = array(
            'order_id' => $this->getInvoiceId($this->order_id),
        );
        $response = $this->restQuery($params);
        //if not payed yet - cancel it
        if ($response && $response['status']) {
            $params['status'] = 'rejected';
            $this->restQuery($params);
        }
        //if not exists do nothing
        return $result;
    }

    private function restRefund($transaction_raw_data)
    {
        //TODO
        return null;
    }

    /**
     * @param $data
     * @return array
     * @throws waException
     */
    private function restCallbackHandler($data)
    {
        #verify sign
        $field = 'X-Api-Signature';
        $field = 'HTTP_'.strtoupper(str_replace('-', '_', $field));
        $result_code = 300;
        if (waRequest::server($field) != $this->getSign($data)) {
            $result_code = 151;
        } else {
            if (empty($data['error'])) {
                $transaction_data = $this->restHandleBillStatus($data);
                $result_code = $transaction_data['result_code'];
            } else {
                self::log($this->id, array('method' => __METHOD__, 'error' => ifset($data['description']), 'error_code' => $data['error']));
            }
        }
        return array(
            'template'    => $this->path.'/templates/resultRest.html',
            'result_code' => $result_code,
            'header'      => array(
                'Content-type' => 'text/xml; charset=utf-8;',
            ),
        );
    }

    private function restHandleBillStatus($data)
    {
        $message = null;
        $transaction_data = $this->formalizeData($data);
        switch ($data['status']) {
            case 'waiting':
                /**Счет выставлен, ожидает оплаты.*/
                $transaction_data['type'] = self::OPERATION_AUTH_ONLY;
                $transaction_data['state'] = self::STATE_AUTH;
                $transaction_data['result'] = 1;
                $callback_method = self::CALLBACK_NOTIFY;
                $result_code = 0;
                break;
            case 'paid':
                /**Счет оплачен.*/
                $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
                $transaction_data['state'] = self::STATE_CAPTURED;
                $transaction_data['result'] = 1;

                $callback_method = self::CALLBACK_PAYMENT;
                $result_code = 0;
                break;
            case 'expired':
                /** Время жизни счета истекло. Счет не оплачен. **/
                $message = sprintf('Время жизни счета истекло. Счет %s не оплачен.', $transaction_data['order_id']);
                $transaction_data['type'] = self::OPERATION_CHECK;
                $transaction_data['state'] = self::STATE_DECLINED;
                $transaction_data['result'] = 1;
                $callback_method = self::CALLBACK_DECLINE;
                $result_code = 0;
                break;
            case 'unpaid':
                /** Ошибка при проведении оплаты. Счет не оплачен. **/
            case 'rejected':
                /**Счет отклонен.**/
                $template = ($data['status'] == 'rejected') ? 'Счет %s отклонен' : 'Ошибка при проведении оплаты. Счет %s не оплачен.';
                $message = sprintf($template, $transaction_data['order_id']);
                $transaction_data['type'] = self::OPERATION_CANCEL;
                $transaction_data['state'] = self::STATE_CANCELED;
                $transaction_data['result'] = 1;
                $callback_method = self::CALLBACK_CANCEL;
                $result_code = 0;
                break;
            default:
                $result_code = 5;
                self::log($this->id, array('method' => __METHOD__, 'error' => 'unknown status: '.$data['status']));
                break;
        }

        if ($message) {
            if (empty($transaction_data['view_data'])) {
                $transaction_data['view_data'] = $message;
            } else {
                $transaction_data['view_data'] .= "\n".$message;
            }
        }


        if (!empty($callback_method)) {
            $transaction_data = $this->saveTransaction($transaction_data, $data);
            $this->execAppCallback($callback_method, $transaction_data);
        }
        $transaction_data['result_code'] = $result_code;
        return $transaction_data;
    }

    private function restQuery($params, $data = array())
    {
        $url = $this->getEndpointUrl('request', $params);
        $query = array();
        foreach ($data as $field => $value) {
            $query[] = $field.'='.urlencode($value);
        }
        $putString = stripslashes(implode('&', $query));
        $result = null;
        $timeout = 10;
        $errorstr = '';
        $errorno = '';
        if (function_exists('curl_init')) {
            if ($ch = curl_init()) {
                $headers = array(
                    "Accept: application/json",
                );
                @curl_setopt($ch, CURLOPT_URL, $url);


                @curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                @curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $login = $this->api_login ? $this->api_login : $this->login;
                @curl_setopt($ch, CURLOPT_USERPWD, "{$login}:{$this->password}");

                @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                @curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
                @curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

                if ($query) {
                    if (!empty($data['status'])) {
                        @curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                    } else {
                        @curl_setopt($ch, CURLOPT_PUT, true);
                    }
                    $stream = tmpfile();
                    fwrite($stream, $putString);
                    fseek($stream, 0);
                    curl_setopt($ch, CURLOPT_INFILE, $stream);
                    curl_setopt($ch, CURLOPT_INFILESIZE, strlen($putString));
                }

                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

                $response = curl_exec($ch);
                if (!$response) {
                    $errorstr = curl_error($ch);
                    $errorno = curl_errno($ch);
                    self::log($this->id, "cUrl error: #{$errorno} {$errorstr} @ {$url}");
                } elseif (!($result = json_decode($response, true))) {
                    self::log($this->id, "Error while decode response: ".$response);
                }
                if ($query && !empty($stream)) {
                    fclose($stream);
                }
                curl_close($ch);
            }
        } elseif (function_exists('fsockopen')) {
            $host = parse_url($url, PHP_URL_HOST);
            if ($socket = fsockopen('ssl://'.$host, 443, $errorno, $errorstr, $timeout)) {
                $method = $query ? (empty($data['status']) ? 'PUT' : 'PATCH') : 'GET';
                $path = parse_url($url, PHP_URL_PATH);
                $out = "{$method} {$path} HTTP/1.1\r\n";
                $out .= "Host: {$host}\r\n";
                $out .= "Accept: application/json\r\n";
                $out .= "Authorization: Basic ".urlencode(base64_encode("{$this->api_login}:{$this->password}"))."\r\n";
                $out .= "Content-Type: application/x-www-form-urlencoded; charset=utf-8\r\n";
                $out .= "Content-Length: ".strlen($putString)."\r\n";
                $out .= "Connection: Close\r\n\r\n";
                $out .= $putString;
                fwrite($socket, $out);
                $response = '';
                while (!feof($socket)) {
                    $response .= fgets($socket, 128);
                }
                fclose($socket);
                $body = null;

                if (!$response) {
                    self::log($this->id, "Socket error: #{$errorno} {$errorstr}");
                } else {
                    list($header, $body) = explode("\r\n\r\n", $response);
                    if (!$body || !($result = json_decode($body, true))) {
                        self::log($this->id, "Error while decode response: ".$response);
                    }
                }
            } else {
                self::log($this->id, "Socket error: #{$errorno} {$errorstr}");
            }
        } else {
            throw new waPaymentException("для работы плагина требуется модуль PHP curl или sockets");
        }
        return $result;
    }

    /**
     * @param array $payment_form_data
     * @param waOrder $order_data
     * @return array|null
     * @throws waPaymentException
     */
    private function soapAuth($payment_form_data, $order_data)
    {
        $result = '';
        try {
            $soap_client = $this->getSoapClient();
            $parameters = new createBill();
            if (!empty($payment_form_data['customer_phone'])) {
                $mobile_phone = $payment_form_data['customer_phone'];
            } else {
                $mobile_phone = $order_data->getContactField($this->customer_phone, 'default');
            }

            $mobile_phone = preg_replace('/^\s*\+\s*7/', '', $mobile_phone);
            $mobile_phone = preg_replace('/[\D]+/', '', $mobile_phone);

            $parameters->login = $this->api_login ? $this->api_login : $this->login;
            $parameters->password = $this->password;
            $parameters->user = $mobile_phone;
            $parameters->amount = number_format($order_data->total, 3, '.', '');
            $parameters->comment = $order_data->description;
            $parameters->txn = $this->getInvoiceId($order_data->id);
            $parameters->lifetime = date('d.m.Y H:i:s', time() + 3600 * max(1, (int)$this->lifetime));
            $parameters->alarm = $this->alarm;
            $parameters->create = 1;

            $response = $soap_client->createBill($parameters);
            if ($response->createBillResult) {
                $result = $this->getResponseCodeDescription($response->createBillResult);
                self::log($this->id, array(__METHOD__." #{$order_data->id}\tphone:{$mobile_phone}\t{$result}"));
            }
        } catch (SoapFault $sf) {
            $result = $sf->getMessage();
            self::log($this->id, $sf->getMessage());
            if (!empty($soap_client)) {
                self::log($this->id, $soap_client->getDebug());
            }
        }
        return $result;
    }

    private function httpPayment($payment_form_data, $order_data, $auto_submit = false)
    {
        if (empty($order_data['currency_id']) || ($order_data['currency_id'] != 'RUB')) {
            throw new waPaymentException('Оплата в через платежную систему «QIWI» производится в только в рублях (RUB) и в данный момент невозможна, так как эта валюта не определена в настройках.');
        }

        if (empty($order_data['order_id'])) {
            throw new waPaymentException(_w('Missing order id'));
        }

        if (empty($order_data['amount'])) {
            throw new waPaymentException(_w('Missing order total amount'));
        }
        $mobile_phone = '';
        if (!empty($order_data['customer_contact_id'])) {
            $contact = new waContact($order_data['customer_contact_id']);
            $mobile_phone = preg_replace('/^\s*\+\s*7/', '', $contact->get('phone.mobile', 'default'));
            $mobile_phone = preg_replace('/[^\d]/', '', $mobile_phone);
        }
        $hidden_fields = array(
            'from'      => $this->login,
            'summ'      => number_format($order_data['amount'], 2, '.', ''),
            'com'       => _w('#').$order_data['order_id'],
            'lifetime'  => $this->lifetime,
            'check_agt' => 'false',
            'txn_id'    => $this->getInvoiceId($order_data['order_id']),
        );
        if (!empty($order_data['description'])) {
            $hidden_fields['com'] .= "\n".$order_data['description'];
        }

        $view = wa()->getView();

        $view->assign('mobile_phone', $mobile_phone);
        $view->assign('url', wa()->getRootUrl());
        $view->assign('hidden_fields', $hidden_fields);
        $view->assign('form_url', $this->getEndpointUrl('form'));
        $view->assign('auto_submit', $auto_submit);

        return $view->fetch($this->path.'/templates/payment.html');
    }

    private function soapCancel($transaction_raw_data)
    {
        try {
            $soap_client = $this->getSoapClient();
            $parameters = new cancelBill();

            $parameters->login = $this->api_login ? $this->api_login : $this->login;
            $parameters->password = $this->password;

            $parameters->txn = $this->getInvoiceId($order_id = $transaction_raw_data['order_id']);

            $response = $soap_client->cancelBill($parameters);

            $result = array(
                'result'      => $response->cancelBillResult ? 0 : 1,
                'description' => $this->getResponseCodeDescription($response->cancelBillResult),
            );
            self::log($this->id, array(__METHOD__." #{$order_id}\t{$result}"));
        } catch (SoapFault $sf) {
            $result = array(
                'result'      => -1,
                'description' => $sf->getMessage(),
            );
            self::log($this->id, $sf->getMessage());
            if (!empty($soap_client)) {
                self::log($this->id, $soap_client->getDebug());
            }
        }
        return $result;
    }

    private function soapCallbackHandler($data)
    {
        $s = $this->getSoapServer('soap');
        $handler = & $this;
        $s->setHandler($handler);
        $s->service($this->post);
        if ($this->debug) {
            self::log($this->id, $s->getDebug());
        }
        if (!empty($this->txn) && ($result = $this->checkBill($this->txn))) {
            $transaction_data = $this->formalizeData($result);
            $callback_method = null;

            switch (intval($result->status)) {
                case 60:
                    $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
                    $transaction_data['state'] = self::STATE_CAPTURED;
                    $transaction_data['result'] = 1;
                    $transaction_data = $this->saveTransaction($transaction_data, $data);
                    $callback_method = self::CALLBACK_PAYMENT;
                    break;
                case 150:
                case 161:
                    $transaction_data['type'] = self::OPERATION_CHECK;
                    $transaction_data['state'] = self::STATE_DECLINED;
                    $transaction_data['result'] = 1;
                    $transaction_data = $this->saveTransaction($transaction_data, $data);
                    $callback_method = self::CALLBACK_DECLINE;
                    break;
                case 151:
                case 160:
                    $transaction_data['type'] = self::OPERATION_CANCEL;
                    $transaction_data['state'] = self::STATE_CANCELED;
                    $transaction_data['result'] = 1;
                    $transaction_data = $this->saveTransaction($transaction_data, $data);
                    $callback_method = self::CALLBACK_CANCEL;
                    break;
                default:
                    self::log($this->id, array('method' => __METHOD__, 'error' => 'callbackHandler checkBill status: '.$result->status));
                    break;
            }
            if ($callback_method) {
                $this->execAppCallback($callback_method, $transaction_data);
            }
        }
        return array('template' => false);
    }

    /**
     *
     * @return IShopServerWSService
     */
    private function getSoapClient()
    {
        if (!class_exists('nusoap_base', false)) {
            class_exists('nusoap_base');
        }
        //TODO init proxy settings
        $options = array();
        $options['location'] = $this->getEndpointUrl('request');
        $options['trace'] = 1;
        $instance = new IShopServerWSService($this->path.'/vendors/qiwi/'.'IShopServerWS.wsdl', $options);
        $instance->setDebugLevel(0);
        if ($this->debug) {
            $instance->setDebugLevel(9);
        }
        $instance->soap_defencoding = 'UTF-8';
        return $instance;
    }

    /**
     *
     * @return IShopClientWSService
     */
    private function getSoapServer()
    {
        if (!class_exists('nusoap_base', false)) {
            class_exists('nusoap_base');
        }
        $options = array();
        $options['location'] = $this->getEndpointUrl('request');
        $options['trace'] = 1;
        $instance = new IShopClientWSService($this->path.'/vendors/qiwi/'.'IShopClientWS.wsdl', $options);
        $instance->soap_defencoding = 'UTF-8';
        $instance->setDebugLevel(0);
        if ($this->debug) {
            $instance->setDebugLevel(9);
        }
        return $instance;
    }

    /**
     * SOAP callback method
     * @param string $login логин (id) магазина
     * @param string $password пароль.
     * @param string $txn уникальный идентификатор счета (максимальная длина 30 байт)
     * @param int $status новый статус счета (см. Справочник статусов счетов)
     * @return updateBillResponse
     */
    public function updateBill($login = null, $password = null, $txn = null, $status = null)
    {
        $result = new updateBillResponse();

        $result->updateBillResult = 0;

        if ($this->debug) {
            self::log($this->id, compact('login', 'password', 'txn', 'status'));
        }
        if (!$this->app_id || !$this->merchant_id) {
            $result->updateBillResult = 300;
            self::log($this->id, 'Unknown merchant data');
        } elseif (!$this->login || !$this->password) {
            self::log($this->id, 'Empty merchant data');
            $result->updateBillResult = 298;
        } elseif (($this->api_login ? $this->api_login : $this->login) != $login) {
            self::log(
                $this->id,
                    array(
                        'error'    => 'updateBill: invalid login: '.$login,
                        'expected' => ($this->api_login ? $this->api_login : $this->login),
                        'txn'      => $txn,
                    )
            );
            $result->updateBillResult = 150;
        } elseif ($password != ($pass = $this->getPassword($this->order_id))) {
            self::log(
                $this->id,
                    array(
                        'Invalid password',
                        'test'          => var_export($this->test, true),
                        'callback_test' => var_export($this->callback_test, true),
                        'expected'      => $pass,
                        'password'      => $password,
                    )
            );
            if ($this->test && $this->callback_test) {
                $result->updateBillResult = 0;
            } else {
                $result->updateBillResult = 150;
            }
        }
        if (!$result->updateBillResult) {
            $this->txn = $txn;
            $this->status = $status;
        }

        return $result;
    }

    /**
     * SOAP callback method
     * @param $txn (native transaction ID)
     * @todo update order status and write changelog
     * @return checkBillResponse
     */
    private function checkBill($txn)
    {
        $result = false;
        try {
            $soap_client = $this->getSoapClient();
            $params = new checkBill();
            $params->login = $this->api_login ? $this->api_login : $this->login;
            $params->password = $this->password;
            $params->txn = $this->txn;
            $retry_counter = 0;
            do {
                if ($retry_counter) {
                    sleep(5);
                }
                $result = $soap_client->checkBill($params);
                if ($this->debug) {
                    $log = $params;
                    $log->password = '***hidden***';
                    $res = var_export(get_object_vars($result), true);
                    self::log(
                        $this->id,
                            array(
                                'method'  => __METHOD__,
                                'request' => var_export(get_object_vars($log), true),
                                'code'    => $this->getBillCodeDescription($result->status),
                                'result'  => $res,
                            )
                    );
                    if (empty($res['status'])) {
                        self::log($this->id, $soap_client->getDebug());
                    }
                }
            } while (empty($result->status) && (++$retry_counter < 5));

        } catch (SoapFault $sf) {
            self::log(
                $this->id,
                    array(
                        'method' => __METHOD__,
                        'error'  => $sf->getMessage(),
                    )
            );
        }
        return $result;
    }

    /**
     * SOAP callback method
     * optional future
     * @todo
     * @return void
     */
    public function cancelBill()
    {
        # login – логин (идентификатор) магазина;
        # password – пароль для магазина;
        # txn – уникальный идентификатор счета (максимальная длина 30 байт).
        ;
    }

    private function getInvoiceId($id)
    {
        if ($this->prefix) {
            $id = $this->prefix.$id;
        }
        return $this->app_id.'_'.$this->merchant_id.'_'.$id;
    }

    /**
     * get SOAP password
     * @param $order_id
     * @return string
     */
    private function getPassword($order_id)
    {
        #Данный параметр может быть сформирован 2 способами:
        # − С использованием подписи WSS X.509, когда каждое уведомление подписывается сервером ОСМП. Данный варинт более сложен в реализации, однако намного безопаснее;
        # − С пользованием упрощенного алгоритма. В поле записывается специально вычисленное по следующему алгоритму значение:
        # uppercase(md5(txn + uppercase(md5(пароля))))
        # Все строки, от которых вычисляется функция md5, преобразуются в байты в кодировке windows-1251. Данный вариант в реализации проще, однако, менее надежен.
        # Пример 1. Пример вычисления значения поля password по упрощенному алгоритму
        # Пусть заказ="Заказ1", а пароль="Пароль магазина", тогда функция
        # MD5("Пароль магазина")=936638421CA12C3E15E72FA7B75E03CE.
        # В поле password будет записано следующее значение:
        # MD5("Заказ1"+MD5("Пароль магазина"))=MD5("Заказ1"+"936638421CA12C3E15E72FA7B75E03CE")= EC19350E3051D8A9834E5A2CF25FD0D9
        #
        if (setlocale(
                LC_CTYPE,
                'ru_RU.CP-1251',
                'ru_RU.CP1251',
                'ru_RU.win',
                'ru_RU.1251',
                'Russian_Russia.1251',
                'Russian_Russia.CP-1251',
                'Russian_Russia.CP1251',
                'Russian_Russia.win'
            ) === false
        ) {
            self::log($this->id, __METHOD__."\tsetLocale failed");
        }
        $txn = (($this->callback_test && $this->test) ? '_TEST_' : '').$this->app_id.'_'.$this->merchant_id.'_'.$this->prefix.$order_id;
        $string = $txn.strtoupper(md5(iconv('utf-8', 'cp1251', $this->password)));
        $hash = strtoupper(md5(iconv('utf-8', 'cp1251', $string)));
        return $hash;
    }

    /**
     * HMAC-SHA1(key, {amount}|{bill_id}|{ccy}|{command}|{comment}|{error}|{prv_name}|{status}|{user})
     * @param array $transaction_raw_data
     * @return string
     */
    private function getSign($transaction_raw_data)
    {
        $string = array(
            number_format($transaction_raw_data['amount'], 2, '.', ''),
            $transaction_raw_data['bill_id'],
            $transaction_raw_data['ccy'],
            $transaction_raw_data['command'],
            $transaction_raw_data['comment'],
            $transaction_raw_data['error'],
            $transaction_raw_data['prv_name'],
            $transaction_raw_data['status'],
            $transaction_raw_data['user'],
        );
        return base64_encode(hash_hmac('sha1', implode('|', $string), $this->sign_password, true));
    }

    /**
     *
     * Internal method to describe response codes
     * @param int $response_code
     */
    private function getResponseCodeDescription($response_code)
    {
        $codes = array();
        $codes[-1] = "Неизвестный код ответа [{$response_code}]";
        $codes[0] = 'Успех';
        switch ($this->protocol) {
            case self::SOAP:
                $codes[13] = 'Сервер занят, повторите запрос позже';
                $codes[150] = 'Ошибка авторизации (неверный логин/пароль)';
                $codes[210] = 'Счет не найден';
                $codes[215] = 'Счет с таким txn-id уже существует';
                $codes[241] = 'Сумма слишком мала';
                $codes[242] = 'Превышена максимальная сумма платежа 15 000 руб.';
                $codes[278] = 'Превышен максимальный интервал получения списка счетов';
                $codes[298] = 'Агент не существует в системе';
                $codes[300] = 'Неизвестная ошибка';
                $codes[330] = 'Ошибка шифрования';
                $codes[370] = 'Превышено максимальное кол-во одновременно выполняемых запросов';
                break;
            case self::REST:
                $codes[0] = 'Успех';
                $codes[5] = 'Неверный формат параметров запроса';
                $codes[13] = 'Сервер занят, повторите запрос позже';
                $codes[150] = 'Ошибка авторизации';
                $codes[152] = 'Не подключен или отключен протокол';
                $codes[210] = 'Счет не найден';
                $codes[215] = 'Счет с таким bill_id уже существует';
                $codes[241] = 'Сумма слишком мала';
                $codes[242] = 'Сумма слишком велика';
                $codes[298] = 'Кошелек с таким номером не зарегистрирован';
                $codes[300] = 'Техническая ошибка';
                $codes[303] = 'Неверный номер телефона';
                $codes[316] = 'Попытка авторизации заблокированным мерчантом';
                $codes[319] = 'Нет прав на данную операцию';
                $codes[341] = 'Обязательный параметр указан неверно или отсутствует в запросе';
                break;
        }
        return isset($codes[$response_code]) ? $codes[$response_code] : $codes[-1];
    }

    /**
     *
     * Internal method to describe response codes
     * @param int $response_code
     */
    private function getBillCodeDescription($response_code)
    {
        if ($response_code < 0) {
            return $this->getResponseCodeDescription(-$response_code);
        }
        $codes = array();
        $codes[-1] = "Неизвестный код статуса счета [{$response_code}]";
        $codes[50] = 'Выставлен';
        $codes[52] = 'Проводится';
        $codes[60] = 'Оплачен';
        $codes[150] = 'Отменен (ошибка на терминале)';
        $codes[151] = 'Отменен (ошибка авторизации: недостаточно средств на балансе, отклонен абонентом при оплате с лицевого счета оператора сотовой связи и т. п.).';
        $codes[160] = 'Отменен';
        $codes[161] = 'Отменен (Истекло время)';

        return isset($codes[$response_code]) ? $codes[$response_code] : $codes[-1];
    }
}
