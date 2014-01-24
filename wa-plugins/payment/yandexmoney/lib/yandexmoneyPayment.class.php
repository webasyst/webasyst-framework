<?php
/**
 *
 * @author WebAsyst Team
 * @name YandexMoney
 * @description YandexMoney pament module
 * @property-read string $TESTMODE
 * @property-read string $shopPassword
 * @property-read string $ShopID
 * @property-read string $scid
 * @property-read string $payment_mode
 * @property-read array $paymentType
 */
class yandexmoneyPayment extends waPayment implements waIPayment
{
    /**
     *
     * Success
     * @var int
     */
    const XML_SUCCESS = 0;

    /**
     *
     * Authorization failed
     * @var int
     */
    const XML_AUTH_FAILED = 1;

    /**
     *
     * Payment refused by shop
     * @var int
     */
    const XML_PAYMENT_REFUSED = 100;

    /**
     *
     * Bad request
     * @var int
     */
    const XML_BAD_REQUEST = 200;

    /**
     *
     * Temporary technical problems
     * @var int
     */
    const XML_TEMPORAL_PROBLEMS = 1000;

    private $version = '1.3';
    private $order_id;
    private $request;

    public function allowedCurrency()
    {
        return 'RUB';
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        if ($order_data['currency_id'] != 'RUB') {
            return array(
                'type' => 'error',
                'data' => _w('Оплата на сайте Яндекс.Денег производится в только в рублях (RUB) и в данный момент невозможна, так как эта валюта не определена в настройках.'),
            );
        }
        $hidden_fields = array(
            'scid'           => $this->scid,
            'ShopID'         => $this->ShopID,
            'CustomerNumber' => $order_data['customer_contact_id'],
            'customerNumber' => $order_data['customer_contact_id'],
            'orderNumber'    => $this->app_id.'_'.$this->merchant_id.'_'.$order_data['order_id'],
            'Sum'            => number_format($order_data['amount'], 2, '.', ''),
        );
        $fields = array();
        if ($this->payment_mode) {
            switch ($this->payment_mode) {
                case 'customer':
                    $ways = self::settingsPaymentOptions();
                    $options = array(
                        'title'       => 'Способ оплаты',
                        'description' => '',
                        'options'     => array(),
                    );


                    foreach ($ways as $way => $name) {
                        if (isset($this->paymentType[$way]) && !empty($this->paymentType[$way])) {
                            $options['options'][$way] = $name;
                        }
                    }
                    if (count($options['options']) == 1) {
                        $hidden_fields['paymentType'] = key($options['options']);
                    } elseif (count($options['options']) > 1) {
                        $options['value'] = key($options['options']);
                        $fields['paymentType'] = waHtmlControl::getControl(waHtmlControl::SELECT, 'paymentType', $options);
                        $auto_submit = false;
                    }
                    break;
                default:
                    $hidden_fields['paymentType'] = $this->payment_mode;
                    break;
            }

        }
        $view = wa()->getView();

        $view->assign('hidden_fields', $hidden_fields);
        $view->assign('fields', $fields);
        $view->assign('form_url', $this->getEndpointUrl());

        $view->assign('auto_submit', $auto_submit);

        return $view->fetch($this->path.'/templates/payment.html');
    }

    protected function callbackInit($request)
    {
        $this->request = $request;
        $pattern = '/^([a-z]+)_(.+)_(.+)$/';

        if (!empty($request['orderNumber']) && preg_match($pattern, $request['orderNumber'], $match)) {
            $this->app_id = $match[1];
            $this->merchant_id = $match[2];
            $this->order_id = $match[3];
        }
        return parent::callbackInit($request);
    }

    /**
     *
     * @param array $request - get from gateway
     * @throws waPaymentException
     * @return mixed
     */
    protected function callbackHandler($request)
    {
        $transaction_data = $this->formalizeData($request);

        $code = ($transaction_data['type'] == self::OPERATION_CHECK) ? self::XML_PAYMENT_REFUSED : self::XML_TEMPORAL_PROBLEMS;

        if (!$this->order_id || !$this->app_id || !$this->merchant_id) {
            throw new waPaymentException('invalid invoice number', $code);
        }
        if (!$this->ShopID) {
            throw new waPaymentException('empty merchant data', $code);
        }
        if (waRequest::get('result') || $request['action'] == 'PaymentFail') {
            $type = $request['action'] == 'PaymentFail' ? waAppPayment::URL_FAIL : waAppPayment::URL_SUCCESS;
            return array(
                'redirect' => $this->getAdapter()->getBackUrl($type, $transaction_data)
            );
        }

        $this->verifySign($request);

        switch ($transaction_data['type']) {
            case self::OPERATION_CHECK:
                $app_payment_method = self::CALLBACK_CONFIRMATION;
                $transaction_data['state'] = self::STATE_AUTH;
                break;

            case self::OPERATION_AUTH_CAPTURE:
                // exclude transactions duplicates
                $tm = new waTransactionModel();
                $fields = array(
                    'native_id' => $transaction_data['native_id'],
                    'plugin'    => $this->id,
                    'type'      => waPayment::OPERATION_AUTH_CAPTURE,
                );
                if ($tm->getByFields($fields)) {
                    throw new waPaymentException('already accepted', self::XML_SUCCESS);
                }

                $app_payment_method = self::CALLBACK_PAYMENT;
                $transaction_data['state'] = self::STATE_CAPTURED;
                break;
            default:
                throw new waPaymentException('unsupported payment operation', self::XML_TEMPORAL_PROBLEMS);
        }

        $transaction_data = $this->saveTransaction($transaction_data, $request);

        $result = $this->execAppCallback($app_payment_method, $transaction_data);
        return $this->getXMLResponse($request, $result['result'] ? self::XML_SUCCESS : self::XML_PAYMENT_REFUSED, $result['error']);
    }

    protected function callbackExceptionHandler(Exception $ex)
    {
        self::log($this->id, $ex->getMessage());
        $message = '';
        if ($ex instanceof waPaymentException) {
            $code = $ex->getCode();
            $message = $ex->getMessage();
        } else {
            $code = self::XML_TEMPORAL_PROBLEMS;
        }
        return $this->getXMLResponse($this->request, $code, $message);
    }

    private function getEndpointUrl()
    {
        if ($this->TESTMODE) {
            return 'https://demomoney.yandex.ru/eshop.xml';
        } else {
            return 'https://money.yandex.ru/eshop.xml';
        }
    }

    /**
     * Check MD5 hash of transfered data
     * @throws waPaymentException
     */
    private function verifySign($request)
    {
        $fields = array(
            'shopId'              => $this->ShopID,
            'scid'                => $this->scid,
            'orderSumBankPaycash' => ($this->TESTMODE) ? 1003 : 1001,
        );
        foreach ($fields as $field => $value) {
            if (empty($request[$field]) || ($request[$field] != $value)) {
                throw new waPaymentException("Invalid value of field {$field}", self::XML_PAYMENT_REFUSED);
            }
        }

        $hash_chunks = array();
        switch ($this->version) {
            case '3.0':
                //action;orderSumAmount;orderSumCurrencyPaycash;orderSumBankPaycash;shopId;invoiceId;customerNumber;shopPassword
                $hash_params = array(
                    'action',
                    'orderSumAmount',
                    'orderSumCurrencyPaycash',
                    'orderSumBankPaycash',
                    'shopId',
                    'invoiceId',
                    'CustomerNumber' => 'customerNumber',
                );
                break;
            default:
                //orderIsPaid;orderSumAmount;orderSumCurrencyPaycash;orderSumBankPaycash;shopId;invoiceId;customerNumber
                //В случае расчета криптографического хэша, в конце описанной выше строки добавляется «;shopPassword»
                $hash_params = array(
                    'orderIsPaid',
                    'orderSumAmount',
                    'orderSumCurrencyPaycash',
                    'orderSumBankPaycash',
                    'shopId',
                    'invoiceId',
                    'CustomerNumber' => 'customerNumber',
                );
                break;
        }

        $missed_fields = array();
        foreach ($hash_params as $id => $field) {
            if (is_int($id)) {
                if (!isset($request[$field])) {
                    $missed_fields[] = $field;
                } else {
                    $hash_chunks[] = $request[$field];
                }
            } else {
                if (!empty($request[$id])) {
                    $hash_chunks[] = $request[$id];
                } elseif (!empty($request[$field])) {
                    $hash_chunks[] = $request[$field];
                } else {
                    $missed_fields[] = $field;
                }
            }

        }
        if ($missed_fields) {
            self::log($this->id, array(
                'method'  => __METHOD__,
                'version' => $this->version,
                'error'   => 'empty required field(s): '.implode(', ', $missed_fields),
            ));
            throw new waPaymentException('Empty required field', self::XML_BAD_REQUEST);
        }

        $hash_chunks[] = $this->shopPassword;

        $hash = strtoupper(md5(implode(';', $hash_chunks)));
        if (empty($request['md5']) || ($hash !== strtoupper($request['md5']))) {
            throw new waPaymentException('invalid hash', self::XML_AUTH_FAILED);
        }
    }

    /**
     * Convert transaction raw data to formatted data
     * @param array $transaction_raw_data
     * @return array $transaction_data
     */
    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data = parent::formalizeData($transaction_raw_data);

        $view_data = '';
        if (ifset($transaction_raw_data['paymentPayerCode'])) {
            $view_data .= 'Account: '.$transaction_raw_data['paymentPayerCode'];
        }

        $transaction_data = array_merge($transaction_data, array(
            'type'        => null,
            'native_id'   => ifset($transaction_raw_data['invoiceId']),
            'amount'      => ifset($transaction_raw_data['orderSumAmount']),
            'currency_id' => ifset($transaction_raw_data['orderSumCurrencyPaycash']) == 643 ? 'RUB' : 'N/A',
            'customer_id' => ifempty($transaction_raw_data['customerNumber'], ifset($transaction_raw_data['CustomerNumber'])),
            'result'      => 1,
            'order_id'    => $this->order_id,
            'view_data'   => $view_data
        ));

        switch ($transaction_raw_data['action']) {
            case 'checkOrder':
                $this->version = '3.0';
            case 'Check':
                //Проверка заказа
                $transaction_data['type'] = self::OPERATION_CHECK;
                break;
            case 'paymentAviso':
                $this->version = '3.0';
            case 'PaymentSuccess': //Уведомления об оплате
                $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
                break;
            case 'PaymentFail': //после неуспешного платежа.
                break;
        }
        return $transaction_data;
    }

    public function supportedOperations()
    {
        return array(
            self::OPERATION_CHECK,
            self::OPERATION_AUTH_CAPTURE,
        );
    }

    /**
     * @param $request
     * @param $code
     * @param string $message
     * @return string XML response
     */
    private function getXMLResponse($request, $code, $message = '')
    {
        $response = array();
        $response['action'] = ifempty($request['action'], 'dummy');
        $response['code'] = $code;
        $response['performedDatetime'] = date('c');

        $message = preg_replace('@[\s\n]+@', ' ', $message);
        $message = htmlentities($message, ENT_QUOTES, 'utf-8');
        if ($this->version == '1.3') {
            $message = iconv('utf-8', 'cp1251', $message);
        }
        if (strlen($message) > 64) {
            $message = substr($message, 0, 64);
        }
        $response['techMessage'] = $message;
        $response['shopId'] = $this->ShopID;
        $response['invoiceId'] = ifempty($request['invoiceId'], '');
        return array(
            'template' => $this->path.'/templates/response.'.$this->version.'.xml',
            'data'     => $response,
            'header'   => array(
                'Content-type' => ($this->version == '1.3') ? 'text/xml; charset=windows-1251;' : 'text/xml; charset=utf-8;',
            ),
        );
    }

    public static function settingsPaymentOptions()
    {
        return array(
            'PC' => 'платеж со счета в Яндекс.Деньгах.',
            'AC' => 'платеж с банковской карты.',
            'GP' => 'платеж по коду через терминал.',
            'MC' => 'оплата со счета мобильного телефона.',
        );
    }
}
