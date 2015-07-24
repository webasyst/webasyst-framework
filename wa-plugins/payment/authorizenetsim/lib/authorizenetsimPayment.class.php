<?php
/**
 * @property-read string $login
 * @property-read string $key
 * @property-read boolean $testmode
 */
class authorizenetsimPayment extends waPayment
{
    protected static $type_trans = array(
        'AUTH_ONLY'    => self::OPERATION_AUTH_ONLY,
        'AUTH_CAPTURE' => self::OPERATION_AUTH_CAPTURE,
    );

    public function allowedCurrency()
    {
        return 'USD';
    }

    public function supportedOperations()
    {
        return array(
            self::OPERATION_AUTH_ONLY,
            self::OPERATION_AUTH_CAPTURE,
            self::OPERATION_HOSTED_PAYMENT_AFTER_ORDER
        );
    }

    public function payment($data, $order_data, $auto_submit = false)
    {
        $data['order_id'] = $order_data['order_id'];

        if ($order_data['currency_id'] != 'USD') {
            throw new waPaymentException(_w('Order currency is not USD but payment gateway provide only USD transactions'));
        }

        $type_trans = array_flip(self::$type_trans);
        if (!empty($data['type']) && !empty($type_trans[$data['type']])) {
            $type = $type_trans[$data['type']];
        } else {
            $type = self::OPERATION_AUTH_ONLY;
        }

        if (empty($order_data['description_en'])) {
            $order_data['description_en'] = 'Order #'.$order_data['order_id'].' ('.gmdate('F, d Y').')';
        }
        $c = new waContact($order_data['contact_id']);
        $locale = $c->getLocale();

        $form_fields = array(
            'x_login'            => $this->login,
            'x_amount'           => number_format($order_data['amount'], 2, '.', ''),
            'x_description'      => $order_data['description_en'],
            'x_invoice_num'      => $order_data['order_id'],
            'x_fp_sequence'      => rand(1, 1000),
            'x_fp_timestamp'     => time(),
            'x_test_request'     => 'false',
            'x_show_form'        => 'PAYMENT_FORM',

            'x_type'             => $type,
            'x_version'          => '3.1',
            'x_method'           => 'CC',
            'x_cust_id'          => $order_data['contact_id'],
            'x_customer_ip'      => wa()->getRequest()->server('REMOTE_ADDR'),

            'x_duplicate_window' => '28800',

            'x_first_name'       => waLocale::transliterate($c->get('firstname'), $locale),
            'x_last_name'        => waLocale::transliterate($c->get('lastname'), $locale),
            'x_company'          => waLocale::transliterate($c->get('company'), $locale),
            'x_address'          => waLocale::transliterate($c->get('address:street', 'default'), $locale),
            'x_city'             => waLocale::transliterate($c->get('address:city', 'default'), $locale),
            'x_state'            => waLocale::transliterate($c->get('address:region', 'default'), $locale),
            'x_zip'              => waLocale::transliterate($c->get('address:zip', 'default'), $locale),
            'x_country'          => waLocale::transliterate($c->get('address:country', 'default'), $locale),
            'x_phone'            => $c->get('phone', 'default'),
            'x_email'            => $c->get('email', 'default'),

            'x_relay_response'   => isset($data['x_relay_response']) ? $data['x_relay_response'] : 'true',
            'x_relay_url'        => $this->getRelayUrl(),
            'wa_success_url'     => $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $data),
            'wa_decline_url'     => $this->getAdapter()->getBackUrl(waAppPayment::URL_DECLINE, $data),
            'wa_cancel_url'      => $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $data),
            'wa_app_id'          => $this->app_id,
            'wa_merchant_id'     => $this->merchant_id
        );
        $form_fields['x_fp_hash'] = ''; // @TODO: get from common 'address' field
        if (phpversion() >= '5.1.2') {
            $form_fields['x_fp_hash'] = hash_hmac('md5', $this->login."^".$form_fields['x_fp_sequence']."^".$form_fields['x_fp_timestamp']."^".$form_fields['x_amount']."^", $this->trans_key);
        } else {
            $form_fields['x_fp_hash'] = bin2hex(mhash(MHASH_MD5, $this->login."^".$form_fields['x_fp_sequence']."^".$form_fields['x_fp_timestamp']."^".$form_fields['x_amount']."^", $this->trans_key));
        }
        if ($this->form_header) {
            $form_fields['x_header_html_payment_form'] = $this->form_header;
        }
        $view = wa()->getView();

        $view->assign('url', wa()->getRootUrl());
        $view->assign('form_fields', $form_fields);

        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('auto_submit', $auto_submit);

        return $view->fetch($this->path.'/templates/payment.html');
    }

    protected function getEndpointUrl()
    {
        if ($this->testmode) {
            return 'https://test.authorize.net/gateway/transact.dll';
        } else {
            return 'https://secure.authorize.net/gateway/transact.dll';
        }
    }

    protected function callbackInit($request)
    {
        if (empty($request['x_invoice_num']) || empty($request['wa_app_id']) || empty($request['wa_merchant_id'])) {
            self::log($this->id, 'Invalid transaction data');
            throw new waException('Invalid transaction data');
        } else {
            $this->app_id = $request['wa_app_id'];
            $this->merchant_id = $request['wa_merchant_id'];
        }
        return parent::callbackInit($request);
    }

    /**
     *
     * @param $data - get from gateway
     * @return void
     */
    protected function callbackHandler($data)
    {
        $transaction_data = $this->formalizeData($data);
        $transaction_data['order_id'] = $data['x_invoice_num'];
        $transaction_data['plugin'] = $this->id;

        $supported_operations = $this->supportedOperations();
        if (!isset($transaction_data['type']) || !in_array($transaction_data['type'], $supported_operations)) {
            self::log($this->id, 'Unsupported payment operation');
            throw new waPaymentException('Unsupported payment operation');
        }
        if (!$this->login) {
            self::log($this->id, 'Empty merchant data');
            throw new waPaymentException('Empty merchant data');
        }
        $error_str = null;

        // Check md5 hash
        //
        if (!isset($data['x_trans_id']) || empty($data['x_amount'])) { //  || empty($data['x_MD5_Hash'])
            $error_str = 'empty fields (trans_id , amount or hash)';
        } else {
            $data['x_amount'] = number_format((float) $data['x_amount'], 2, '.', '');
            $hash = $this->md5_hash.$this->login.$data['x_trans_id'].$data['x_amount'];

            if (strtoupper(md5($hash)) != strtoupper($data['x_MD5_Hash'])) {
                $error_str = 'invalid hash';
            }
        }
        if ($error_str) {
            self::log($this->id, $error_str);
            throw new waPaymentException($error_str);
        }
        switch ($transaction_data['type']) {
            case self::OPERATION_AUTH_CAPTURE:
                $app_payment_method = 'Payment';
                $transaction_data['state'] = self::STATE_CAPTURED;
                break;

            case self::OPERATION_AUTH_ONLY:
                $app_payment_method = 'Payment';
                $transaction_data['state'] = self::STATE_AUTH;
                break;

            default:
                $app_payment_method = 'Payment';
        }
        if ($transaction_data['result'] != 1) {
            $transaction_data['state'] = self::STATE_DECLINED;
        } else {
            $transaction_data['error'] = null;
        }
        $transaction_data = $this->saveTransaction($transaction_data, $data);

        $transaction_data['success_back_url'] = isset($data['wa_success_url']) ? $data['wa_success_url'] : null;

        $result = $this->execAppCallback($app_payment_method, $transaction_data);

        $result['template'] = wa()->getConfig()->getRootPath().'/wa-plugins/payment/'.$this->id.'/templates/callback.html';
        $result['url'] = !empty($transaction_data['success_back_url']) ? $transaction_data['success_back_url'] :
            $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);

        self::log($this->id, 'Transaction added');

        return $result;
    }

    /**
     * Convert transaction raw data to formatted data
     * @param array $data - transaction raw data
     * @return array $transaction_data
     */
    protected function formalizeData($data)
    {
        $fields = array(
            'x_type', 'x_trans_id', 'x_amount', 'x_invoice_num', 'x_cust_id', 'x_response_code', 'x_response_reason_text',
            'x_first_name', 'x_last_name', 'x_company', 'x_address', 'x_city', 'x_state', 'x_zip', 'x_country', 'x_phone', 'x_email'
        );
        foreach ($fields as $f) {
            if (!isset($data[$f])) {
                $data[$f] = null;
            }
        }
        $view_data = '';
        if ($data['x_card_type'] && $data['x_account_number'])
            $view_data .= $data['x_card_type'].': '.$data['x_account_number'].', ';
        if ($data['x_first_name'] || $data['x_last_name'])
            $view_data .= trim($data['x_first_name'].' '.$data['x_last_name']).', ';
        if ($data['x_email'])
            $view_data .= $data['x_email'].', ';
        if ($data['x_phone'] || $data['x_company'] || $data['x_address'] || $data['x_city'] || $data['x_state'] || $data['x_zip'])
            $view_data .= $data['x_phone'].' '.$data['x_company'].' '.$data['x_address'].' '.$data['x_city'].' '.$data['x_state'].' '.$data['x_zip'].', ';
        $view_data = substr($view_data, 0, -2);
        $view_data = preg_replace('/ +/', ' ', $view_data);

        $type = strtoupper($data['x_type']);

        $transaction_data = array(
            'type'        => isset(self::$type_trans[$type]) ? self::$type_trans[$type] : $type,
            'native_id'   => $data['x_trans_id'],
            'amount'      => $data['x_amount'],
            'currency_id' => 'USD',
            'date_time'   => date('Y-m-d H:i:s'),
            'order_id'    => $data['x_invoice_num'],
            'result'      => $data['x_response_code'] == 1,
            'error'       => $data['x_response_reason_text'],
            'view_data'   => $view_data
        );
        return $transaction_data;
    }

}
