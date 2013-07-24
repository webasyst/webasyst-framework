<?php
/**
 *
 * @author WebAsyst Team
 * @name WebMoney
 * @description WebMoney payment module
 * @property-read string $LMI_MERCHANT_ID
 * @property-read string $LMI_PAYEE_PURSE
 * @property-read string $secret_key
 * @property-read string $LMI_SIM_MODE
 * @property-read string $TESTMODE
 * @property-read string $protocol
 */
class webmoneyPayment extends waPayment implements waIPayment
{

    const PROTOCOL_WEBMONEY = 'webmoney';
    const PROTOCOL_WEBMONEY_LEGACY = 'webmoney_legacy';
    const PROTOCOL_PAYMASTER = 'paymaster';

    public function allowedCurrency()
    {
        return array('RUB','USD');
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        if (!in_array($order_data['currency_id'],$this->allowedCurrency())) {
            throw new waException('Оплата на сайте WebMoney производится только в рублях (RUB) и в данный момент невозможна, так как эта валюта не определена в настройках.');
        }
        if (empty($order_data['description'])) {
            $order_data['description'] = 'Заказ '.$order_data['order_id'];
        }

        $hidden_fields = array(
            'LMI_MERCHANT_ID'        => $this->LMI_MERCHANT_ID,
            'LMI_PAYMENT_AMOUNT'     => number_format($order_data['amount'], 2, '.', ''),
            'LMI_CURRENCY'           => strtoupper($order_data['currency_id']),
            'LMI_PAYMENT_NO'         => $order_data['order_id'],
            'LMI_PAYMENT_DESC'       => $order_data['description'],
            'LMI_RESULT_URL'         => $this->getRelayUrl(),
            'wa_app'                 => $this->app_id,
            'wa_merchant_contact_id' => $this->merchant_id,
        );
        if ($this->LMI_PAYEE_PURSE) {
            $hidden_fields['LMI_PAYEE_PURSE'] = $this->LMI_PAYEE_PURSE;
        }
        if ($this->TESTMODE) {
            $hidden_fields['LMI_SIM_MODE'] = $this->LMI_SIM_MODE;
        }
        if (!empty($order_data['customer_info']['email'])) {
            $hidden_fields['LMI_PAYER_EMAIL'] = $order_data['customer_info']['email'];
        }

        $transaction_data = $this->formalizeData($hidden_fields);
        $hidden_fields['LMI_SUCCESS_URL'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
        $hidden_fields['LMI_FAILURE_URL'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);

        $view = wa()->getView();

        $view->assign('url', wa()->getRootUrl());
        $view->assign('hidden_fields', $hidden_fields);

        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('auto_submit', $auto_submit);

        return $view->fetch($this->path.'/templates/payment.html');
    }

    protected function callbackInit($request)
    {
        if (!empty($request['LMI_PAYMENT_NO']) && !empty($request['wa_app']) && !empty($request['wa_merchant_contact_id'])) {
            $this->app_id = $request['wa_app'];
            $this->merchant_id = $request['wa_merchant_contact_id'];
        } else {
            self::log($this->id, array('error' => 'empty required field(s)'));
            throw new waException('Empty required field(s)');
        }
        return parent::callbackInit($request);
    }

    /**
     *
     * @param array $data - get from gateway
     * @throws waException
     * @return void
     */
    protected function callbackHandler($data)
    {
        $transaction_data = $this->formalizeData($data);

        if (!in_array($transaction_data['type'], $this->supportedOperations())) {
            self::log($this->id, array('error' => 'unsupported payment operation'));
            throw new waException('Unsupported payment operation');
        }
        if (!$this->LMI_MERCHANT_ID) {
            throw new waException('Empty merchant data');
        }

        switch ($transaction_data['type']) {
            case self::OPERATION_CHECK:
                $app_payment_method = self::CALLBACK_CONFIRMATION;
                break;

            case self::OPERATION_AUTH_CAPTURE:
            default:
                $this->verifySign($data);
                //TODO log payer WM ID
                $app_payment_method = self::CALLBACK_PAYMENT;
                break;
        }
        $transaction_data['state'] = self::STATE_CAPTURED;
        $transaction_data = $this->saveTransaction($transaction_data, $data);

        $transaction_data['success_back_url'] = isset($data['wa_success_url']) ? $data['wa_success_url'] : null;

        $result = $this->execAppCallback($app_payment_method, $transaction_data);

        self::addTransactionData($transaction_data['id'], $result);

        if (!empty($result['result'])) {
            self::log($this->id, array('result' => 'success'));
            $message = 'YES';
        } else {
            $message = !empty($result['error']) ? $result['error'] : 'wa transaction error';
            self::log($this->id, array('error' => $message));
            header("HTTP/1.0 403 Forbidden");
        }
        echo $message;
        exit;
    }

    protected function getEndpointUrl()
    {
        switch ($this->protocol) {
            case self::PROTOCOL_WEBMONEY_LEGACY:
            case self::PROTOCOL_PAYMASTER:
                $url = 'https://paymaster.ru/Payment/Init';
                break;
            case self::PROTOCOL_WEBMONEY:
            default:
                $url = 'https://merchant.webmoney.ru/lmi/payment.asp';

                break;
        }
        return $url;
    }

    private function verifySign($data)
    {
        $result = false;
        switch ($this->protocol) {
            case self::PROTOCOL_PAYMASTER:
                /*
                 * Check user sign
                 * base64
                 * md5
                 */
                $fields = array(
                    /*01.Идентификатор Компании (LMI_MERCHANT_ID);*/
                    'LMI_MERCHANT_ID',
                    /*02.Внутренний номер покупки продавца (LMI_PAYMENT_NO);*/
                    'LMI_PAYMENT_NO',
                    /*03.Номер платежа в системе Paymaster (LMI_SYS_PAYMENT_ID);*/
                    'LMI_SYS_PAYMENT_ID',
                    /*04.Дата платежа (LMI_SYS_PAYMENT_DATE);*/
                    'LMI_SYS_PAYMENT_DATE',
                    /*05.Сумма платежа, заказанная Компанией (LMI_PAYMENT_AMOUNT);*/
                    'LMI_PAYMENT_AMOUNT',
                    /* 06.Валюта платежа, заказанная Компанией (LMI_CURRENCY);*/
                    'LMI_CURRENCY',
                    /* 07.Сумма платежа в валюте, в которой покупатель производит платеж (LMI_PAID_AMOUNT);*/
                    'LMI_PAID_AMOUNT',
                    /* 08.Валюта, в которой производится платеж (LMI_PAID_CURRENCY)*/
                    'LMI_PAID_CURRENCY',
                    /* 09.Идентификатор платежной системы, выбранной покупателем (LMI_PAYMENT_SYSTEM)*/
                    'LMI_PAYMENT_SYSTEM',
                    /* 10.Флаг тестового режима (LMI_SIM_MODE)*/
                    'LMI_SIM_MODE',

                );
                $hash_string = '';
                foreach ($fields as $field) {
                    $hash_string .= (isset($data[$field]) ? $data[$field] : '').';';
                }
                /* 11.Secret Key
                 *
                 */
                $hash_string .= $this->secret_key.';';
                $transaction_hash = strtolower(base64_encode(md5($hash_string, true)));
                unset($hash_string);

                break;
            case self::PROTOCOL_WEBMONEY_LEGACY:
            case self::PROTOCOL_WEBMONEY:
            default:
                /*
                 * Check user sign
                 * md5*/
                $fields = array(
                    /* 1.Кошелек продавца (LMI_PAYEE_PURSE);*/
                    'LMI_PAYEE_PURSE',
                    /* 2.Сумма платежа (LMI_PAYMENT_AMOUNT);*/
                    'LMI_PAYMENT_AMOUNT',
                    /* 3.Внутренний номер покупки продавца (LMI_PAYMENT_NO);*/
                    'LMI_PAYMENT_NO',
                    /* 4.Флаг тестового режима (LMI_MODE);*/
                    'LMI_MODE',
                    /* 5.Внутренний номер счета в системе WebMoney Transfer (LMI_SYS_INVS_NO);*/
                    'LMI_SYS_INVS_NO',
                    /* 6.Внутренний номер платежа в системе WebMoney Transfer (LMI_SYS_TRANS_NO);*/
                    'LMI_SYS_TRANS_NO',
                    /* 7.Дата и время выполнения платежа (LMI_SYS_TRANS_DATE);*/
                    'LMI_SYS_TRANS_DATE',
                    /* 8.Secret Key (LMI_SECRET_KEY);*/
                    'LMI_SECRET_KEY',
                    /* 9.Кошелек покупателя (LMI_PAYER_PURSE);*/
                    'LMI_PAYER_PURSE',
                    /* 10.WMId покупателя (LMI_PAYER_WM).*/
                    'LMI_PAYER_WM',
                );
                $data['LMI_SECRET_KEY'] = $this->secret_key;
                $hash_string = '';
                foreach ($fields as $field) {
                    $hash_string .= (isset($data[$field]) ? $data[$field] : '');
                }
                $transaction_hash = strtolower(md5($hash_string));
                unset($data['LMI_SECRET_KEY']);
                unset($hash_string);

                break;
        }

        $transaction_sign = isset($data['LMI_HASH']) ? strtolower($data['LMI_HASH']) : null;

        if (!empty($data['LMI_PREREQUEST']) || ($transaction_sign == $transaction_hash)) {
            $result = true;
        }
        return $result;
    }

    /**
     * Convert transaction raw data to formatted data
     * @param array $transaction_raw_data - transaction raw data
     * @return array $transaction_data
     */
    protected function formalizeData($transaction_raw_data)
    {
        $fields = array(
            'LMI_MERCHANT_ID',
            'LMI_PAYMENT_NO',
            'LMI_PAYMENT_AMOUNT',
            'LMI_CURRENCY',
            'LMI_PAID_AMOUNT',
            'LMI_PAID_CURRENCY',
            'LMI_PAYMENT_SYSTEM',
            'LMI_SYS_INVS_NO',
            'LMI_SYS_TRANS_NO',
            'LMI_SIM_MODE',
            'LMI_PAYMENT_DESC',
            'wa_app',
            'wa_merchant_contact_id',
            'LMI_PREREQUEST',
            'LMI_HASH',
            'LMI_SYS_PAYMENT_ID',
            'LMI_SYS_PAYMENT_DATE',
        );
        foreach ($fields as $f) {
            if (!isset($transaction_raw_data[$f])) {
                $transaction_raw_data[$f] = null;
            }
        }
        $transaction_data = parent::formalizeData($transaction_raw_data);
        $transaction_data['type'] = !empty($transaction_raw_data['LMI_PREREQUEST']) ? self::OPERATION_CHECK : (!empty($transaction_raw_data['LMI_HASH']) ? self::OPERATION_AUTH_CAPTURE : 'N/A');
        if (!$transaction_raw_data['LMI_SYS_PAYMENT_ID'] && ($transaction_raw_data['LMI_SYS_INVS_NO'] || $transaction_raw_data['LMI_SYS_TRANS_NO'])) {
            $transaction_data['native_id'] = $transaction_raw_data['LMI_SYS_INVS_NO'].':'.$transaction_raw_data['LMI_SYS_TRANS_NO'];
        } else {
            $transaction_data['native_id'] = $transaction_raw_data['LMI_SYS_PAYMENT_ID'];
        }
        $transaction_data['order_id'] = $transaction_raw_data['LMI_PAYMENT_NO'];
        $transaction_data['amount'] = $transaction_raw_data['LMI_PAYMENT_AMOUNT'];
        $transaction_data['currency_id'] = $transaction_raw_data['LMI_CURRENCY'];

        return $transaction_data;
    }

    public function supportedOperations()
    {
        return array(
            self::OPERATION_CHECK,
            self::OPERATION_AUTH_CAPTURE,
        );
    }

    public static function _getProtocols()
    {
        $protocols = array();
        $protocols[] = array(
            'title' => 'подключение к WebMoney',
            'value' => self::PROTOCOL_WEBMONEY,
        );
        $protocols[] = array(
            'title' => 'подключение к PayMaster (режим совместимости)',
            'value' => self::PROTOCOL_WEBMONEY_LEGACY,
        );
        $protocols[] = array(
            'title' => 'подключение к PayMaster',
            'value' => self::PROTOCOL_PAYMASTER,
        );
        return $protocols;
    }

}
