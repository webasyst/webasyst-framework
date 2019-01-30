<?php

/**
 *
 * @property-read int $payonline_id
 * @property-read string $secret_key
 * @property-read array $currency
 * @property-read string $gateway
 * @property-read int $valid_until
 * @property-read string $customer_lang
 * @property-read bool $receipt
 *
 * @property-read int $payment_subject_type_product
 * @property-read int $payment_subject_type_service
 * @property-read int $payment_subject_type_shipping
 * @property-read int $payment_method_type
 *
 * @version 1.6
 * @link https://www.payonlinesystem.ru/
 */
class payonlinePayment extends waPayment implements waIPayment
{

    private $url = 'https://secure.payonlinesystem.com/%s/payment/%s';
    private $fiscal_url = 'https://secure.payonlinesystem.com/Services/Fiscal/Request.ashx';
    private $order_id;

    public function allowedCurrency()
    {
        $default = array(
            'RUB',
            'USD',
            'EUR',
        );
        return $this->currency ? array_intersect($default, array_keys($this->currency)) : $default;
    }

    /**
     * @param array $payment_form_data
     * @param waOrder $order_data
     * @param bool $auto_submit
     * @return string
     * @throws waPaymentException
     */
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);
        $allowed = (array)$this->allowedCurrency();
        if (!in_array($order->currency, $allowed)) {
            throw new waPaymentException('Ошибка оплаты. Валюта не поддерживается');
        }

        if ($this->receipt) {
            #check that tax id is valid
            foreach ($order->items as $item) {
                $this->getTaxId($item);
            }

            #shipping
            if ($order->shipping || strlen($order->shipping_name)) {
                $item = array(
                    'name'         => $order->shipping_name,
                    'quantity'     => 1,
                    'price'        => $order->shipping,
                    'tax_rate'     => $order->shipping_tax_rate,
                    'tax_included' => $order->shipping_tax_included,
                );
                $this->getTaxId($item);
            }
        }

        $form_fields = array(
            'MerchantId' => $this->payonline_id,
            'OrderId'    => $this->app_id.'_'.$order_data['order_id'],
            'Amount'     => number_format($order_data['amount'], 2, '.', ''),
            'Currency'   => $order->currency,
        );

        if ($this->valid_until) {
            $order_time = empty($order_data['order_time']) ? time() : strtotime($order_data['order_time']);
            $form_fields['ValidUntil'] = date('Y-m-d H:i:s', $order_time + $this->valid_until * 3600);
        }

        $form_fields['SecurityKey'] = $this->getSecurityKey($form_fields);

        $form_fields['ReturnURL'] = $this->getRelayUrl().'?app_id='.$this->app_id;
        $form_fields['FailURL'] = $this->getRelayUrl().'?transaction_result=failure&app_id='.$this->app_id;

        #custom form field for use at callbackInit method
        $form_fields['wa_merchant_id'] = $this->merchant_id;

        $view = wa()->getView();

        $view->assign('form_fields', $form_fields);
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('auto_submit', $auto_submit);

        return $view->fetch($this->path.'/templates/payment.html');
    }

    public function callbackInit($request)
    {
        $pattern = '/^([a-z]+)_(.+)$/';
        if (!empty($request['OrderId']) && preg_match($pattern, $request['OrderId'], $match)) {
            $this->app_id = $match[1];
            $this->merchant_id = ifempty($request['wa_merchant_id'], '');
            $this->order_id = $match[2];
        } elseif (!empty($request['app_id'])) {
            $this->app_id = $request['app_id'];
        }
        return parent::callbackInit($request);
    }

    /**
     * @param array $request
     * @return array|mixed
     * @throws waException
     * @throws waPaymentException
     */
    public function callbackHandler($request)
    {
        $transaction_data = $this->formalizeData($request);
        $transaction_result = ifempty($request['transaction_result'], 'success');
        $post = waRequest::post();
        if (empty($post)) {
            if (!empty($request['app_id'])) {
                $url = $transaction_result == 'success' ? waAppPayment::URL_SUCCESS : waAppPayment::URL_FAIL;
                return array(
                    'redirect' => $this->getAdapter()->getBackUrl($url, $transaction_data),
                );
            }
        }
        $this->verifySign($request);
        $message = null;
        switch (ifempty($request['ErrorCode'])) {
            case 1:
                $message = 'Возникла техническая ошибка, попробуйте повторить попытку оплаты спустя некоторое время.';
                $transaction_result = 'failure';
                $transaction_data['state'] = self::STATE_DECLINED;
                break;
            case 2:
                $message = 'Оплата банковской картой недоступна. Попробуйте воспользоваться другим способом оплаты.';
                $transaction_result = 'failure';
                $transaction_data['state'] = self::STATE_DECLINED;
                break;
            case 3:
                $message = 'Платеж отклонен банком-эмитентом карты. Обратитесь в банк, выясните причину отказа и повторите попытку оплаты.';
                $transaction_result = 'failure';
                $transaction_data['state'] = self::STATE_DECLINED;
                break;
            default:
                break;
        }

        $url = null;

        if (!empty($post)) {
            $app_payment_method = null;
            switch ($transaction_result) {
                case 'success':
                    $app_payment_method = self::CALLBACK_PAYMENT;
                    $transaction_data['state'] = self::STATE_CAPTURED;
                    $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
                    break;
                case 'failure':
                default:
                    $app_payment_method = self::CALLBACK_DECLINE;
                    $transaction_data['state'] = self::STATE_DECLINED;
                    $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);
                    break;
            }

            $transaction_data = $this->saveTransaction($transaction_data, $request);
            if ($app_payment_method) {
                $this->execAppCallback($app_payment_method, $transaction_data);
                if ($app_payment_method == self::CALLBACK_PAYMENT) {
                    $order = $this->getAdapter()->getOrderData($this->order_id, $this);
                    $this->sendFiscalRequest($order, $transaction_data);
                }
            }
        }

        return array(
            'template' => $this->path.'/templates/callback.html',
            'back_url' => $url,
            'message'  => $message,
        );
    }

    public function getPrintForms(waOrder $order = null)
    {
        $forms = parent::getPrintForms($order);
        if ($this->getParentTransaction($order->id)) {
            $forms['receipt'] = array(
                'name'        => 'Чек',
                'description' => 'Фисальный чек',
            );
        }
        return $forms;
    }

    /**
     * @param string $id
     * @param waOrder $order
     * @param array $params
     * @return null|string
     * @throws waException
     */
    public function displayPrintForm($id, waOrder $order, $params = array())
    {
        $html = null;
        switch ($id) {
            case 'receipt':
                //заказ оплачен
                $transaction_model = new waTransactionModel();
                $search = array(
                    'plugin'      => $this->id,
                    'app_id'      => $this->app_id,
                    'merchant_id' => $this->key,
                    'order_id'    => $order->id,
                    'parent_id'   => null,
                );
                $transaction_data = $transaction_model->getByField($search);
                $native_id = $transaction_data ? $transaction_data['native_id'] : null;

                if (!$native_id) {
                    throw new waException('Transaction not found', 404);
                } elseif ($transaction_data['state'] != self::STATE_CAPTURED) {
                    throw new waException('Transaction not paid yet');
                }

                $search['parent_id'] = $transaction_data['id'];
                $receipt_transaction_data = $transaction_model->getByField($search);

                if ($receipt_transaction_data) {
                    //чек зарегестрирован
                    $response = array(
                        'status' => array('code' => -1),
                    );
                    $transaction_data_model = new waTransactionDataModel();
                    $response['payload'] = $transaction_data_model
                        ->select('field_id, value')
                        ->where('transaction_id=:i', $receipt_transaction_data['id'])
                        ->fetchAll('field_id', true);
                    $response['data'] = $this->getFiscalData($order, $native_id);
                } else {
                    //иначе зарегестрировать чек
                    $response = $this->sendFiscalRequest($order, $transaction_data);
                }
                $view = wa()->getView();
                $view->assign('receipt', $response['payload']);
                unset($response['payload']);
                foreach ($response['data']['goods'] as &$item) {
                    $item['tax_rate'] = intval(preg_replace('@\D@', '', $item['tax']));
                    $item['total'] = $item['amount'] * $item['quantity'];
                    $item['tax_total'] = $item['total'] * $item['tax_rate'] / (100 + $item['tax_rate']);
                    unset($item);
                }

                $view->assign('data', $response['data']);
                unset($response['data']);
                $view->assign('response', $response);

                //показать чек
                $html = $view->fetch($this->path.'/templates/receipt.html');
                break;
            default:
                throw new waException('Form not found', 404);
                break;
        }
        return $html;
    }

    private function getParentTransaction($id)
    {
        $transaction_model = new waTransactionModel();

        $search = array();
        $search['plugin'] = $this->id;
        $search['app_id'] = $this->app_id;
        $search['merchant_id'] = $this->key;
        $search['order_id'] = $id;
        $search['parent_id'] = null;

        return $transaction_model->getByField($search);
    }

    private function getFiscalUrl($body)
    {
        $fields = array(
            'RequestBody' => $body,
            'MerchantId'  => $this->payonline_id,
        );

        $query = array(
            'MerchantId'  => $this->payonline_id,
            'SecurityKey' => $this->getSecurityKey($fields),
        );

        return $this->fiscal_url.'?'.http_build_query($query);
    }

    /**
     * @param $order
     * @param $transaction_data
     * @return array|bool|SimpleXMLElement|string
     * @throws waException
     */
    private function sendFiscalRequest($order, $transaction_data)
    {
        if ($this->receipt) {

            $body = $this->getFiscalData(waOrder::factory($order), $transaction_data['native_id']);

            $options = array(
                'format' => waNet::FORMAT_JSON,
            );

            $net = new waNet($options);

            try {
                $response = $net->query($this->getFiscalUrl($body), $body, waNet::METHOD_POST);

                if (ifset($response['status']['code']) == -1) {
                    $transaction_data = $this->saveFiscalData($transaction_data, $response);
                    $app_data = $this->execAppCallback(self::CALLBACK_NOTIFY, $transaction_data);
                } else {
                    $log = sprintf(
                        "Error occurred during create fiscal document.\nData: %s\nResponse: %s\n",
                        var_export($body, true),
                        var_export($response, true)
                    );
                    self::log($this->id, $log);
                }

            } catch (waException $ex) {
                $log = array(
                    'message'      => $ex->getMessage(),
                    'code'         => $ex->getCode(),
                    'raw_response' => $net->getResponse(true),
                );
                self::log($this->id, $log);
                throw $ex;
            }
            $response['data'] = $body;
            return $response;
        }
        return false;
    }

    private function saveFiscalData($transaction_data, $response)
    {
        $transaction_data['view_data'] = null;
        $transaction_data['parent_id'] = $transaction_data['id'];
        unset($transaction_data['id']);
        $this->saveTransaction($transaction_data, $response['payload']);
        $transaction_data['view_data'] = array();
        $map = array(
            'fiscal_document_number' => 'Фискальный номер документа',
            'name_document'          => 'Наименование документа',
            'recipient'              => 'Получатель',
            'fiscal_receipt_number'  => 'Номер чека в смене',
            'shift_number'           => 'Номер смены',
            'receipt_datetime'       => 'Дата и время документа из ФН',
        );
        foreach ($map as $field => $name) {
            if (!empty($response['payload'])) {
                $value = htmlentities($response['payload'][$field], ENT_NOQUOTES, 'utf-8');
                $transaction_data['view_data'][] = sprintf('%s: %s', $name, $value);
            }
        }
        $message = 'Создан кассовый чек (%s)';
        $transaction_data['view_data'] = sprintf($message, implode(';', $transaction_data['view_data']));

        return $transaction_data;
    }

    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data = parent::formalizeData($transaction_raw_data);
        $transaction_data['native_id'] = ifempty($transaction_raw_data['TransactionID']);

        $transaction_data['order_id'] = $this->order_id;
        $transaction_data['amount'] = ifset($transaction_raw_data['Amount']);
        $transaction_data['currency_id'] = ifset($transaction_raw_data['PaymentCurrency']);

        $details = '';
        $fields = array();
        $fields['TransactionID'] = 'Уникальный идентификатор транзакции или счета QIWI/WebMoney/Яндекс.Деньги';
        switch ($provider = ifempty($transaction_raw_data['Provider'])) {
            case 'Card':
                $fields['CardHolder'] = 'Имя держателя карты';
                $fields['CardNumber'] = 'Номер карты';
                $fields['Country'] = 'Страна';
                $fields['BinCountry'] = 'Код страны, определенный по BIN эмитента карты';
                $fields['City'] = 'Город';
                $fields['Address'] = 'Адрес';
                break;
            case 'Qiwi':
                $fields['Phone'] = 'Номер телефона';
                break;
            case 'WebMoney':
            case 'PayMaster':
                $fields['WmTranId'] = 'Служебный номер счета в системе учета PayMaster';
                $fields['WmInvId'] = 'Уникальный номер счета в системе учета PayMaster';
                $fields['WmId'] = 'WMID плательщика';
                $fields['WmPurse'] = 'WM-кошелек плательщика';
                break;
            default:
                $details .= "Unknown payment provider {$provider}";
                break;

        }

        $fields['IpAddress'] = 'IP-адрес';
        $fields['IpCountry'] = 'Код страны, определенный по IP-адресу';
        foreach ($fields as $field => $description) {
            if (!empty($transaction_raw_data[$field])) {
                $details .= "\n{$description}: {$transaction_raw_data[$field]}";
            }
        }

        $transaction_data['view_data'] = $details;

        return $transaction_data;
    }

    /**
     * @param $request
     * @throws waPaymentException
     */
    private function verifySign($request)
    {
        if ($this->secret_key) {
            $fields = array(
                'DateTime',
                'TransactionID',
                'OrderId',
                'Amount',
                'Currency',
            );
            $string = '';
            foreach ($fields as $field) {
                $string .= $field.'='.ifempty($request[$field]).'&';
            }

            $signature = strtolower(md5($string.'PrivateSecurityKey='.$this->secret_key));
            $server_signature = strtolower(ifempty($request['SecurityKey']));
            if (!$server_signature || ($server_signature != $signature)) {
                throw new waPaymentException('invalid post data sign');
            }
        }
    }

    private function getSecurityKey($form_fields)
    {
        $hash = '';
        foreach ($form_fields as $field => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $hash .= "{$field}={$value}&";
        }

        return md5($hash.'PrivateSecurityKey='.$this->secret_key);
    }


    private function getEndpointUrl()
    {
        return sprintf($this->url, 'ru', $this->gateway);
    }

    /**
     * @param waOrder $order
     * @param  int $native_id
     * @return array
     * @throws waPaymentException
     */
    private function getFiscalData(waOrder $order, $native_id)
    {
        $data = null;
        if ($this->receipt) {
            $data = array(
                'operation'         => 'Benefit',
                'transactionId'     => $native_id,
                'paymentSystemType' => 'custom',
                'totalAmount'       => number_format($order->total, 2, '.', ''),
                'goods'             => array(),
                'email'             => $order->contact_email,
            );
            if (empty($data['email'])) {
                $model = new waAppSettingsModel();
                $data['email'] = $model->get('webasyst', 'email');
            }

            foreach ($order->items as $item) {
                $data['goods'][] = $this->formatFiscalItem($item);
            }

            #shipping
            if ($order->shipping || strlen($order->shipping_name)) {
                $item = array(
                    'name'     => mb_substr($order->shipping_name, 0, 128),
                    'quantity' => 1,
                    'price'    => $order->shipping,
                    'tax_rate' => $order->shipping_tax_rate,
                    'type'     => 'shipping',
                );
                $data['goods'][] = $this->formatFiscalItem($item);
            }
        }

        return $data;
    }

    /**
     * @param $item
     * @return array
     * @throws waPaymentException
     */
    private function formatFiscalItem($item)
    {
        $item['amount'] = ifset($item['price'], 0.0) - ifset($item['discount'], 0.0);

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
            'description'         => mb_substr($item['name'], 0, 128),
            'quantity'            => $item['quantity'], //decimal 8.3
            'amount'              => number_format($item['amount'], 2, '.', ''), //decimal 8.2
            'tax'                 => $this->getTaxId($item),
            'paymentMethodType'   => $this->payment_method_type,
            'paymentSubjectType ' => $item['payment_subject_type'],
        );
    }

    /**
     * @param $item
     * @return string
     * @throws waPaymentException
     */
    private function getTaxId($item)
    {
        if (!isset($item['tax_rate'])) {
            $tax = 'none';
        } else {
            $tax_included = !isset($item['tax_included']) ? true : $item['tax_included'];
            $rate = ifset($item['tax_rate']);
            if (in_array($rate, array(null, false, ''), true)) {
                $rate = -1;
            }

            if (!$tax_included && $rate > 0) {
                throw new waPaymentException('Фискализация товаров с налогом не включенном в стоимость не поддерживается. Обратитесь к администратору магазина');
            }

            switch ($rate) {
                case 0:
                    $tax = 'vat0';
                    break;
                case 10:
                    if ($tax_included) {
                        $tax = 'vat10';
                    } else {
                        $tax = 'vat110';
                    }
                    break;
                case 18:
                    if ($tax_included) {
                        $tax = 'vat18';
                    } else {
                        $tax = 'vat118';
                    }
                    break;
                case 20:
                    if ($tax_included) {
                        $tax = 'vat20';
                    } else {
                        $tax = 'vat120';
                    }
                    break;
                default:
                    $tax = 'none';
                    break;
            }
        }
        return $tax;
    }
}
