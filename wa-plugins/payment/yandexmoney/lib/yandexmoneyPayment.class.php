<?php

/**
 *
 * @author Webasyst
 * @name YandexMoney
 * @description YandexMoney payment module
 * @property-read string $integration_type
 * @property-read string $account
 * @property-read string $TESTMODE
 * @property-read string $shopPassword
 * @property-read string $ShopID
 * @property-read string $scid
 * @property-read string $payment_mode
 * @property-read array $paymentType
 * @property-read boolean $receipt
 * @property-read string $payment_subject_type_product
 * @property-read string $payment_subject_type_service
 * @property-read string $payment_subject_type_shipping
 * @property-read string $payment_method_type
 * @property-read int $taxSystem
 * @property-read string $taxes
 * @property-read string $merchant_currency
 *
 *
 * @see https://money.yandex.ru/doc.xml?id=526537
 * @see https://tech.yandex.ru/money/doc/payment-solution/About-docpage/
 * @see https://kassa.yandex.ru/docs/checkout-api/
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

    private static $currencies = array(
        'RUB',
        'EUR',
        'USD',
    );

    public function allowedCurrency()
    {
        $currency = $this->merchant_currency ? $this->merchant_currency : reset(self::$currencies);
        if (!in_array($currency, self::$currencies)) {
            $currency = reset(self::$currencies);
        }
        return $currency;
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        if ($auto_submit && $this->TESTMODE) {
            $auto_submit = false;
        }
        $order_data = waOrder::factory($order_data);
        $currency = $this->allowedCurrency();
        if ($order_data['currency_id'] != $currency) {
            $template = 'Оплата на сайте «Яндекс.Денег» производится в только в %s и в данный момент невозможна, потому что эта валюта не определена в настройках.';
            throw new waPaymentException(sprintf($template, $currency));
        }
        $view = wa()->getView();
        $view->assign('plugin', $this);
        switch ($this->integration_type) {

            case 'personal':

                $view->assign('order', $order_data);
                $view->assign('return_url', $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS));
                $view->assign('label', $this->app_id.'_'.$this->account.'_'.$order_data['order_id']);
                break;
            case 'kassa':
                $hidden_fields = array(
                    'scid'           => $this->scid,
                    'ShopID'         => $this->ShopID,
                    'CustomerNumber' => $order_data['customer_contact_id'],
                    //'customerNumber' => $order_data['customer_contact_id'],
                    'orderNumber'    => $this->app_id.'_'.$this->merchant_id.'_'.$order_data['order_id'],
                    'Sum'            => number_format($order_data->total, 2, '.', ''),
                );
                if ($order_data->recurrent === true) {
                    //пользователь не сможет отказаться от повторных списаний (но сможет прервать оплату)
                    $hidden_fields['rebillingOn'] = 'true';
                } elseif ($order_data->recurrent === false) {
                    // пользователь увидит галочку Запомнить карту и сможет отказаться от повторных списаний.
                    $hidden_fields['rebillingOn'] = 'false';
                }
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

                if ($receipt = $this->getReceiptData($order_data)) {
                    $json_options = 0;
                    if (defined('JSON_UNESCAPED_UNICODE')) {
                        $json_options |= constant('JSON_UNESCAPED_UNICODE');
                    }

                    $hidden_fields['ym_merchant_receipt'] = $json_options ? json_encode($receipt, $json_options) : json_encode($receipt);
                }

                $view->assign('hidden_fields', $hidden_fields);
                $view->assign('fields', $fields);
                $view->assign('form_url', $this->getEndpointUrl());

                $view->assign('auto_submit', $auto_submit);
                break;

            case 'mpos':
                //TODO use custom form
                break;
        }

        $view->assign('integration_type', $this->integration_type);

        return $view->fetch($this->path.'/templates/payment.html');
    }

    public function getSettingsHTML($params = array())
    {
        $html = parent::getSettingsHTML($params);

        $js = file_get_contents($this->path.'/js/settings.js');
        $html .= sprintf('<script type="text/javascript">%s</script>', $js);
        return $html;
    }

    /**
     * @see https://tech.yandex.ru/money/doc/payment-solution/payment-form/payment-form-receipt-docpage/
     * @param waOrder $order
     * @return array|null
     */
    private function getReceiptData(waOrder $order)
    {
        $receipt = null;
        if ($this->receipt) {
            $contact = $order->getContactField('email');
            if (empty($contact)) {
                if ($contact = $order->getContactField('phone')) {
                    $contact = sprintf('+%s', preg_replace('@^8@', '7', $contact));
                } else {
                    $model = new waAppSettingsModel();
                    $contact = $model->get('webasyst', 'email');
                }
            }

            if (!empty($contact)) {
                $receipt = array(
                    'customerContact' => $contact,
                    'items'           => array(),
                );
                if ($this->taxSystem) {
                    $receipt['taxSystem'] = $this->taxSystem;
                }

                foreach ($order->items as $item) {
                    $item['amount'] = round($item['price'], 2) - round(ifset($item['discount'], 0.0), 2);
                    $receipt['items'][] = $this->formatReceiptItem($item);
                    unset($item);
                }

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
                    $receipt['items'][] = $this->formatReceiptItem($item);
                }
            }
        }
        return $receipt;
    }

    private function formatReceiptItem($item)
    {
        if (isset($item['tax_included']) && empty($item['tax_included']) && !empty($item['tax_rate'])) {
            $item['amount'] += round(floatval($item['tax_rate']) * $item['amount'] / 100.0, 2);
        }
        switch (ifset($item['type'])) {
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
        return array(
            'quantity'           => $item['quantity'],
            'price'              => array(
                'amount' => number_format(round($item['amount'], 2), 2, '.', ''),
            ),
            'tax'                => $this->getTaxId($item),
            'text'               => mb_substr($item['name'], 0, 128),
            'paymentSubjectType' => $item['payment_subject_type'],
            'paymentMethodType'  => $this->payment_method_type,
        );
    }

    private function getTaxId($item)
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
                    throw new waPaymentException('Фискализация товаров с налогом не включенном в стоимость не поддерживается. Обратитесь к администратору магазина');
                }

                switch ($rate) {
                    case 18:
                    case 20:
                        if ($tax_included) {
                            # 4 — НДС чека по ставке 18% до 31.12.2018;
                            # 4 — НДС чека по ставке 20% после 01.01.2019;
                            $id = 4;
                        } else {
                            #6 — НДС чека по расчетной ставке 18/118 до 31.12.2018;
                            #6 — НДС чека по расчетной ставке 18/118 после 01.01.2019;
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

    protected function callbackInit($request)
    {
        $this->request = $request;
        $pattern = '/^([a-z]+)_(.+)_(.+)$/';
        $merchant_pattern = '/^([a-z]+)_([^_]+)_([^_]+)/';

        if (!empty($request['orderNumber']) && preg_match($pattern, $request['orderNumber'], $match)) {
            $this->app_id = $match[1];
            $this->merchant_id = $match[2];
            $this->order_id = $match[3];
        } elseif (!empty($request['merchant_order_id']) && preg_match($merchant_pattern, $request['merchant_order_id'], $match)) {
            $this->app_id = $match[1];
            $this->merchant_id = $match[2];
            $this->order_id = $match[3];
        } elseif (!empty($request['orderDetails'])) {
            /**
             * @see https://tech.yandex.ru/money/doc/payment-solution/payment-process/payments-mpos-docpage/
             * mobile terminal — detect app automatically/parse string
             * shop:100500 #order_id new
             */
            if (preg_match('@^(\w+):(\d+)(\s+|$)@', $request['orderDetails'], $match)) {
                $this->app_id = $match[1];
                $this->merchant_id = $match[2];
                $comment = trim($match[3]);
                if (preg_match('@^(\d+)(\s+|$)@', $comment, $match)) {
                    $this->order_id = $match[1];
                } else {
                    $this->order_id = 'offline';
                }
            } elseif (preg_match('@^#?(\d+)(\s+|$)@', $request['orderDetails'], $match)) {
                $this->order_id = $match[1];
            } else {
                $this->order_id = 'offline';
            }
            if (empty($this->merchant_id)) {
                $this->merchant_id = array($this, 'callbackMatchSettings');
            }
        } elseif (isset($request['paymentType']) && ($request['paymentType'] == 'MP')) {
            $this->order_id = 'offline';
            $this->merchant_id = array($this, 'callbackMatchSettings');
        }
        return parent::callbackInit($request);
    }

    public function callbackMatchSettings($settings)
    {
        $result = !empty($settings['ShopID']) && ($settings['ShopID'] == ifset($this->request['shopId']));
        if ($result) {
            $result = intval($result);
            if (!empty($settings['scid']) && ($settings['scid'] == ifset($this->request['scid']))) {
                $result += 2;
            }

            if ($settings['payment_mode'] == 'MP') {
                $result += 1;
            }
        }
        return $result;
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
        if (waRequest::get('result') || (ifset($request['action']) == 'PaymentFail')) {
            if ((ifset($request['action']) == 'PaymentFail') || (waRequest::get('result') == 'fail')) {
                $type = waAppPayment::URL_FAIL;
            } else {
                $type = waAppPayment::URL_SUCCESS;
            }
            return array(
                'redirect' => $this->getAdapter()->getBackUrl($type, $transaction_data),
            );
        }

        $this->verifySign($request);

        if (!$this->TESTMODE) {
            if (ifset($request['orderSumCurrencyPaycash']) != 643) {
                throw new waPaymentException('Invalid currency code', self::XML_PAYMENT_REFUSED);
            }
        }


        if (($this->order_id === 'offline') || (ifset($request['paymentType']) == 'MP')) {
            $transaction_data['unsettled'] = true;
            $fields = array(
                'native_id' => $transaction_data['native_id'],
                'plugin'    => $this->id,
                'type'      => array(waPayment::OPERATION_CHECK, waPayment::OPERATION_AUTH_CAPTURE),
            );
            $tm = new waTransactionModel();
            $check = $tm->getByField($fields);
            if ($check && !empty($check['order_id'])) {
                if ($transaction_data['order_id'] != $check['order_id']) {
                    if (($transaction_data['order_id'] !== 'offline') && ($transaction_data['order_id'] != $check['order_id'])) {
                        $message = ' Внимание: номер переданного заказа %s не совпадает с сопоставленным';
                        $transaction_data['view_data'] .= sprintf($message, htmlentities($transaction_data['order_id'], ENT_NOQUOTES, 'utf-8'));
                    }
                    $transaction_data['order_id'] = $check['order_id'];
                }
            }
        }

        switch ($transaction_data['type']) {
            case self::OPERATION_CHECK:
                $app_payment_method = self::CALLBACK_CONFIRMATION;
                $transaction_data['state'] = '';
                break;

            case self::OPERATION_AUTH_CAPTURE:
                //XXX rebillingOn workaround needed
                if (empty($tm)) {
                    $tm = new waTransactionModel();
                }
                $fields = array(
                    'native_id' => $transaction_data['native_id'],
                    'plugin'    => $this->id,
                    'type'      => waPayment::OPERATION_AUTH_CAPTURE,
                );
                if ($tm->getByFields($fields)) {
                    // exclude transactions duplicates
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

        return $this->getXMLResponse($request, !empty($result['result']) ? self::XML_SUCCESS : self::XML_PAYMENT_REFUSED, ifset($result['error']));
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
     * Check MD5 hash of transferred data
     * @throws waPaymentException
     * @param array $request
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
            self::log(
                $this->id,
                array(
                    'method'  => __METHOD__,
                    'version' => $this->version,
                    'error'   => 'empty required field(s): '.implode(', ', $missed_fields),
                )
            );
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
     * @throws waPaymentException
     */
    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data = parent::formalizeData($transaction_raw_data);

        $view_data = '';
        if (ifset($transaction_raw_data['paymentPayerCode'])) {
            $view_data .= 'Account: '.$transaction_raw_data['paymentPayerCode'];
        }

        if (!empty($transaction_raw_data['cps_provider'])) {
            switch ($transaction_raw_data['cps_provider']) {
                case 'wm':
                    $view_data .= 'Оплачено: WebMoney';
                    break;
                default:
                    $view_data .= 'Оплачено: '.$transaction_raw_data['cps_provider'];
                    break;
            }
        }

        if ($this->TESTMODE) {
            if (ifset($transaction_raw_data['orderSumCurrencyPaycash']) != 10643) {
                $view_data .= ' Реальная оплата в тестовом режиме;';
            } else {
                $view_data .= ' Тестовый режим;';
            }
        }

        if (!empty($transaction_raw_data['paymentType'])) {
            $types = self::settingsPaymentOptions();
            if (isset($types[$transaction_raw_data['paymentType']])) {
                $view_data .= ' '.$types[$transaction_raw_data['paymentType']];
            }
            switch ($transaction_raw_data['paymentType']) {
                case 'AC':
                    if (!empty($transaction_raw_data['cdd_pan_mask']) && !empty($transaction_raw_data['cdd_exp_date'])) {
                        $number = str_replace('|', str_repeat('*', 6), $transaction_raw_data['cdd_pan_mask']);
                        $view_data .= preg_replace('@([\d*]{4})@', ' $1', $number);
                        $view_data .= preg_replace('@(\d{2})(\d{2})@', ' $1/20$2', $transaction_raw_data['cdd_exp_date']);
                    }
                    break;
            }
        }


        $transaction_data = array_merge(
            $transaction_data,
            array(
                'type'        => null,
                'native_id'   => ifset($transaction_raw_data['invoiceId']),
                'amount'      => ifset($transaction_raw_data['orderSumAmount']),
                'currency_id' => $this->allowedCurrency(),
                'customer_id' => ifempty($transaction_raw_data['customerNumber'], ifset($transaction_raw_data['CustomerNumber'])),
                'result'      => 1,
                'order_id'    => $this->order_id,
                'view_data'   => trim($view_data),
            )
        );

        switch (ifset($transaction_raw_data['action'])) {
            case 'checkOrder': //Проверка заказа
                $this->version = '3.0';
                $transaction_data['type'] = self::OPERATION_CHECK;
                if ($this->order_id === 'offline') {

                } else {
                    $transaction_data['view_data'] .= ' Проверка актуальности заказа;';
                }
                break;
            case 'paymentAviso': //Уведомления об оплате
                $this->version = '3.0';
                $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
                break;

            case 'Check': //Проверка заказа
                $transaction_data['type'] = self::OPERATION_CHECK;
                break;
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
     * @return array
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

    /**
     * @link https://tech.yandex.ru/money/doc/payment-solution/reference/payment-type-codes-docpage/
     * @return array
     */
    public static function settingsPaymentOptions()
    {
        return array(
            'PC' => 'Оплата со счета в Яндекс.Деньгах',
            'AC' => 'Оплата с банковской карты',
            'GP' => 'Оплата по коду через терминал',
            'MC' => 'Оплата со счета мобильного телефона',
            'WM' => 'Оплата со счета WebMoney',
            'SB' => 'Оплата через Сбербанк Онлайн',
            'AB' => 'Оплата в Альфа-Клик',
            'MP' => 'Оплата через мобильный терминал (mPOS)',
            'MA' => 'Оплата через MasterPass',
            'PB' => 'Оплата через интернет-банк Промсвязьбанка',
            'QW' => 'Оплата через QIWI Wallet',
            'KV' => 'Оплата через КупиВкредит (Тинькофф Банк)',
            'QP' => 'Оплата через Доверительный платеж («Куппи.ру»)',
        );
    }

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
            'payment'               => 'платеж',
            'agent_commission'      => 'агентское вознаграждение',
            'composite'             => 'несколько вариантов',
            'another'               => 'другое',
        );
    }

    public static function settingsPaymentModeOptions()
    {
        return array(
                'customer' => 'На выбор покупателя после перехода на сайт Яндекса (рекомендуется)',
                ''         => 'Не задан (определяется Яндексом)',
            ) + self::settingsPaymentOptions();
    }


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
                'title'    => 'Общая СН',
                'disabled' => $disabled,
            ),
            array(
                'value'    => 2,
                'title'    => 'Упрощенная СН (доходы)',
                'disabled' => $disabled,
            ),
            array(
                'value'    => 3,
                'title'    => 'Упрощенная СН (доходы минус расходы)',
                'disabled' => $disabled,
            ),
            array(
                'value'    => 4,
                'title'    => 'Единый налог на вмененный доход',
                'disabled' => $disabled,
            ),
            array(
                'value'    => 5,
                'title'    => 'Единый сельскохозяйственный налог',
                'disabled' => $disabled,
            ),
            array(
                'value'    => 6,
                'title'    => 'Патентная СН',
                'disabled' => $disabled,
            ),
        );
    }

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

    public function settingsCurrencyOptions()
    {
        $available = array();
        $app_config = wa()->getConfig();
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
}
