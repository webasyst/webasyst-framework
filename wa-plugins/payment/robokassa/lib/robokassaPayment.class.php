<?php

/**
 * @property-read string $merchant_login
 * @property-read string $merchant_pass1
 * @property-read string $merchant_pass2
 * @property-read string $locale
 * @property-read string $testmode
 * @property-read string $gateway_currency
 * @property-read string $merchant_currency
 *
 *
 */
class robokassaPayment extends waPayment implements waIPayment
{
    private $url = 'https://auth.robokassa.ru/Merchant/Index.aspx';
    private $test_url = 'http://test.robokassa.ru/Index.aspx';

    private $order_id;
    private $request_testmode = 1;

    protected function initControls()
    {
        $this->registerControl('GatewayCurrency');
    }

    public function allowedCurrency()
    {

        return $this->merchant_currency ? $this->merchant_currency : 'RUB';
    }

    public static function settingGatewayCurrency($name, $params = array())
    {
        $default = array(
            'title_wrapper'       => false,
            'description_wrapper' => false,
            'control_wrapper'     => '%s%s%s',
        );
        $params = array_merge($params, $default);
        $options = array();

        $data = array();
        $data['Language'] = 'ru';

        if (!empty($params['instance']) && ($params['instance'] instanceof self)) {
            $data['MerchantLogin'] = $params['instance']->merchant_login;
            $test = !!$params['instance']->testmode;
        } else {
            $test = false;
        }
        if ($test || empty($data['MerchantLogin'])) {
            $url = 'http://test.robokassa.ru/Webservice/Service.asmx/GetCurrencies?';
        } else {
            $url = 'http://merchant.roboxchange.com/WebService/Service.asmx/GetCurrencies?';
        }

        if (empty($data['MerchantLogin'])) {
            $data['MerchantLogin'] = 'demo';
        }

        $url .= 'MerchantLogin='.$data['MerchantLogin'];
        $url .= '&Language='.$data['Language'];

        if (extension_loaded('curl') && ($ch = @curl_init())) {

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            if ((version_compare(PHP_VERSION, '5.4', '>=') || !ini_get('safe_mode')) && !ini_get('open_basedir')) {
                //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                ;
            }

            $http_response = curl_exec($ch);

            if (!$http_response) {
                self::log(
                    preg_replace('/payment$/', '', strtolower(__CLASS__)),
                    array(
                        'error' => curl_errno($ch).':'.curl_error($ch),
                    )
                );
                curl_close($ch);
            } else {

                if ($content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE)) {
                    if (preg_match('/charset=[\'"]?([a-z\-0-9]+)[\'"]?/i', $content_type, $matches)) {
                        $charset = strtolower($matches[1]);
                        if (!in_array($charset, array('utf-8', 'utf8'))) {
                            $http_response = iconv($charset, 'utf-8', $http_response);
                        }
                    }
                }
                curl_close($ch);
                $options = self::parse($http_response, $url);
            }

        } elseif (ini_get('allow_url_fopen')) {
            $old_timeout = @ini_set('default_socket_timeout', 15);
            $http_response = '';
            if ($stream = fopen($url, 'rb')) {
                while (!feof($stream)) {
                    $http_response .= fread($stream, 4096);
                }
                $meta = stream_get_meta_data($stream);
                fclose($stream);


                if ($http_response) {
                    foreach (ifset($meta['wrapper_data'], array()) as $meta_data) {
                        if (strpos($meta_data, ':')) {
                            list($meta_name, $content_type) = explode(':', $meta_data, 2);
                            if (strtolower($meta_name) == 'content-type') {
                                if (preg_match('/charset=[\'"]?([a-z\-0-9]+)[\'"]?/i', $content_type, $matches)) {
                                    $charset = strtolower($matches[1]);
                                    if (!in_array($charset, array('utf-8', 'utf8'))) {
                                        $http_response = iconv($charset, 'utf-8', $http_response);
                                    }
                                }
                                break;
                            }
                        }
                    }
                    $options = self::parse($http_response, $url);
                }
            }
            @ini_set('default_socket_timeout', $old_timeout);
        }
        if (empty($options)) {
            $message = '<span class="errormsg">Произошла ошибка при получении списка доступных способов оплаты шлюза (Детали в логе платежного плагина).</span>';
            if (!empty($params['value'])) {
                $message .= sprintf(
                    '<span class="hint">Текущее значение настройки: <b>%s</b>.</span>',
                    htmlentities($params['value'], ENT_NOQUOTES, waHtmlControl::$default_charset)
                );
            } else {
                $message .= '<span class="hint">Покупателю будет предложено самостоятельно выбрать способ оплаты на сайте платежного шлюза.</span>';
            }
            return waHtmlControl::getControl(waHtmlControl::HIDDEN, $name, $params).$message;
        } else {
            array_unshift(
                $options,
                array(
                    'title' => 'Покупателю будет предложен выбор на сайте шлюза',
                    'value' => '',
                    'group' => 'Пользовательский',
                )
            );
            $params['options'] = $options;
            return waHtmlControl::getControl(waHtmlControl::SELECT, $name, $params);
        }
    }

    private static function parse($http_response, $url = null)
    {
        $options = array();
        if ($xml = @simplexml_load_string($http_response)) {
            if ($code = (int)$xml->Result->Code) {
                self::log(
                    preg_replace('/payment$/', '', strtolower(__CLASS__)),
                    array(
                        'url'   => $url,
                        'error' => $code.': '.(string)$xml->Result->Description,
                        'xml'   => $http_response,
                    )
                );
                $options[] = array(
                    'title' => (string)$xml->Result->Description,
                    'value' => null,
                    'group' => 'Ошибка получения списка валют',
                );
            } else {
                foreach ($xml->Groups as $xml_group) {
                    foreach ($xml_group->Group as $xml_group_item) {
                        foreach ($xml_group_item->Items as $xml_items) {
                            foreach ($xml_items as $xml_item) {
                                $options[] = array(
                                    'title' => (string)$xml_group_item['Description'].' — '.(string)$xml_item['Name'],
                                    'value' => (string)$xml_item['Label'],
                                    'group' => (string)$xml_group_item['Code'],
                                );
                            }
                        }
                    }
                }
            }
        } else {
            self::log(
                preg_replace('/payment$/', '', strtolower(__CLASS__)),
                array(
                    'url'   => $url,
                    'error' => 'Invalid service response',
                    'xml'   => $http_response,
                )
            );
        }
        return $options;
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);
        $description = preg_replace('/[^\.\?,\[]\(\):;"@\\%\s\w\d]+/', ' ', $order->description);
        $description = preg_replace('/[\s]{2,}/', ' ', $description);
        $form_fields = array();
        $form_fields['MrchLogin'] = $this->merchant_login;
        $form_fields['OutSum'] = number_format($order->total, 2, '.', '');
        $form_fields['InvId'] = $order->id;
        $hash_string = implode(':', $form_fields).':'.$this->merchant_pass1;
        $hash_string .= ':shp_wa_app_id='.$this->app_id;
        $hash_string .= ':shp_wa_merchant_id='.$this->merchant_id;

        $hash_string .= sprintf(':shp_wa_testmode=%d', $this->testmode);


        $form_fields['SignatureValue'] = md5($hash_string);
        $form_fields['Desc'] = mb_substr($description, 0, 100, "UTF-8");
        $form_fields['IncCurrLabel'] = $this->gateway_currency;
        $form_fields['Culture'] = $this->locale;

        $form_fields['shp_wa_app_id'] = $this->app_id;
        $form_fields['shp_wa_merchant_id'] = $this->merchant_id;

        $form_fields['shp_wa_testmode'] = intval($this->testmode);

        $view = wa()->getView();

        $view->assign('form_fields', $form_fields);
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('auto_submit', $auto_submit);

        return $view->fetch($this->path.'/templates/payment.html');
    }

    protected function callbackInit($request)
    {
        if (!empty($request['InvId']) && intval($request['InvId'])) {
            $this->app_id = ifempty($request['shp_wa_app_id']);
            $this->merchant_id = ifempty($request['shp_wa_merchant_id']);
            $this->request_testmode = ifempty($request['shp_wa_testmode']);
            $this->order_id = intval($request['InvId']);
        } elseif (!empty($request['app_id'])) {
            $this->app_id = $request['app_id'];
        }
        return parent::callbackInit($request);
    }

    public function callbackHandler($request)
    {
        $transaction_data = $this->formalizeData($request);
        $transaction_result = ifempty($request['transaction_result'], 'success');

        $url = null;
        $app_payment_method = null;

        switch ($transaction_result) {
            case 'result':
                $this->verifySign($request);
                $app_payment_method = self::CALLBACK_PAYMENT;
                $transaction_data['state'] = self::STATE_CAPTURED;
                break;
            case 'success':
                $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
                break;
            case 'failure':
                if ($this->order_id && $this->app_id) {
                    $app_payment_method = self::CALLBACK_CANCEL;
                    $transaction_data['state'] = self::STATE_CANCELED;
                }
                $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);
                break;
            default:
                $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);
                break;
        }


        if ($app_payment_method) {
            $transaction_data = $this->saveTransaction($transaction_data, $request);
            $this->execAppCallback($app_payment_method, $transaction_data);
        }
        if ($transaction_result == 'result') {
            echo 'OK'.$this->order_id;
            return array(
                'template' => false,
            );
        } else {
            if ($url) {
                return array(
                    'redirect' => $url,
                );
            } else {
                return array(
                    'template' => $this->path.'/templates/callback.html',
                    'back_url' => $url,
                );
            }
        }
    }

    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data = parent::formalizeData($transaction_raw_data);
        $transaction_data['native_id'] = $this->order_id;
        $transaction_data['order_id'] = $this->order_id;
        $transaction_data['amount'] = ifempty($transaction_raw_data['OutSum'], '');
        $transaction_data['currency_id'] = $this->merchant_currency;
        if ($this->testmode || $this->request_testmode) {
            $transaction_data['view_data'] = 'Тестовый режим';
        }
        return $transaction_data;
    }

    private function getEndpointUrl()
    {
        return $this->testmode ? $this->test_url : $this->url;
    }

    private function verifySign($request)
    {
        if ($this->merchant_pass2) {
            $hash_string = ifempty($request['OutSum'], '').':'.ifempty($request['InvId'], '').':'.$this->merchant_pass2;
            $hash_string .= ':shp_wa_app_id='.$this->app_id;
            $hash_string .= ':shp_wa_merchant_id='.$this->merchant_id;
            if (isset($request['shp_wa_testmode']) || $this->testmode) {
                if ((int)$this->testmode != ifset($request['shp_wa_testmode'], '0')) {
                    throw new waPaymentException('Invalid test mode request');
                }
            }
            $hash_string .= sprintf(':shp_wa_testmode=%d', $this->testmode);

            $sign = strtolower(md5($hash_string));
            $server_sign = strtolower(ifempty($request['SignatureValue'], ''));
            if (empty($server_sign) || ($server_sign != $sign)) {
                throw new waPaymentException('Invalid sign');
            }
        } else {
            throw new waPaymentException('Empty payment password');
        }
    }
}
