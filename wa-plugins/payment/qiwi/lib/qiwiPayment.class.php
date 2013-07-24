<?php
/**
 * @version draft
 * @package waPlugins
 * @subpackage Payment
 * @name QIWI
 * @description QIWI payment module
 * @type ???????
 * @apps shop,orders
 *
 * @property-read string $login
 * @property-read string $password
 * @property-read string $lifetime
 * @property-read string $alarm
 * @property-read string $prefix
 * @property-read string $customer_phone
 * @property-read string $TESTMODE
 *
 */
class qiwiPayment extends waPayment implements waIPayment, waIPaymentCapture, waIPaymentCancel
{
    private $url = 'https://ishop.qiwi.ru/services/ishop';
    private $http_url = 'https://w.qiwi.ru/setInetBill_utf.do';
    private $order_id;

    private $txn;
    private $post;

    public function allowedCurrency()
    {
        return 'RUB';
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
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
        $view->assign('form_url', $this->getEndpointUrl('html'));

        return $view->fetch($this->path.'/templates/payment.html');
    }

    /**
     * @todo test and complete code
     */
    public function capture($transaction_raw_data)
    {
        $result = '';
        try {
            //$order_id, $amount, $phone_number, $description;
            $soap_client = $this->getQiwiSoapClient();

            $parameters = new createBill();

            $contact = new waContact($order_data['customer_id']);
            $mobile_phone = preg_replace('/^\s*\+\s*7/', '', $contact->get('phone.mobile', 'default'));
            //TODO verify phone
            $mobile_phone = preg_replace('/[\D]+/', '', $mobile_phone);

            $parameters->login = $this->login;
            $parameters->password = $this->password;
            $parameters->user = $phone_number;
            $parameters->amount = $amount;
            $parameters->comment = $description;
            $parameters->txn = $this->getInvoiceId($transaction_raw_data['order_id']);
            $parameters->lifetime = date('d.m.Y H:i:s', time() + 3600 * max(1, (int)$this->lifetime));
            $parameters->alarm = $this->alarm;
            $parameters->create = 1;

            $response = $soap_client->createBill($parameters);
            self::log($this->id, $soap_client->getDebug());
            if ($response->createBillResult) {
                $result = $this->getResponseCodeDescription($response->createBillResult);
                self::log($this->id, array(__METHOD__." #{$order_id}\tphone:{$phone_number}\t{$result}"));
            }
        } catch (SoapFault $sf) {
            $result = $sf->getMessage();
            self::log($this->id, $sf->getMessage());
            self::log($this->id, $soap_client->getDebug());
        }
        return $result;
    }

    /**
     * @todo test it
     */
    public function cancel($transaction_raw_data)
    {
        try {
            $soap_client = $this->getQiwiSoapClient();
            $order_id = null;

            $parameters = new cancelBill();

            $parameters->login = $this->login;
            $parameters->password = $this->password;

            $parameters->txn = $this->getInvoiceId($transaction_raw_data['order_id']); #

            $response = $soap_client->cancelBill($parameters);

            $result = array(
                'result'      => $response->cancelBillResult ? 0 : 1,
                'description' => $this->getResponseCodeDescription($response->cancelBillResult),
            );
            self::log($this->id, array(__METHOD__." #{$order_id}\tphone:{$phone_number}\t{$result}"));
        } catch (SoapFault $sf) {
            $result = array(
                'result'      => -1,
                'description' => $sf->getMessage(),
            );
            self::log($this->id, $sf->getMessage());
            self::log($this->id, $soap_client->getDebug());
        }
        return $result;
    }

    protected function callbackInit($request)
    {
        $pattern = "@^([a-z]+)_(\d+)_(.+)$@";
        $this->post = !empty($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : null;
        /**
         *
         * Request
         * @var SimpleXMLElement
         */
        if ($this->post) {

            $xml = new SimpleXMLElement($this->post);

            if ($txn_xpath = $xml->xpath('/soap:Envelope/soap:Body[1]/*[1]/txn[1]')) {
                $txn = (string)reset($txn_xpath);
                if ($txn && preg_match($pattern, $txn, $match)) {
                    $this->app_id = $match[1];
                    $this->merchant_id = $match[2];
                    $this->order_id = $match[3];
                }

                if ($update_bill = $xml->xpath('/soap:Envelope/soap:Body[1]/*[1]')) {
                    self::log($this->id, reset($update_bill)->asXml());
                }
            }
        } elseif (!empty($request['order']) && preg_match($pattern, $request['order'], $match)) {
            $this->app_id = $match[1];
            $this->merchant_id = $match[2];
            $this->order_id = $match[3];

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
        if ($this->prefix) {
            $pattern = wa_make_pattern($this->prefix, '@');
            $pattern = "@^{$pattern}(.+)$@";
            $order_id = null;
            if (preg_match($pattern, $this->order_id, $matches)) {
                $this->order_id = $matches[1];
            }
        }
        $result = array();
        if (!empty($data['result']) && $this->order_id) {
            //handle customer redirection
            $transaction_data = array(
                'order_id' => $this->order_id,
            );
            $type = ($data['result'] == 'success') ? waAppPayment::URL_SUCCESS : waAppPayment::URL_FAIL;
            $result['url'] = $this->getAdapter()->getBackUrl($type, $transaction_data);
            $result['template'] = $this->path.'/templates/result.html';
        } else {
            $s = $this->getQiwiSoapServer('soap');
            $s->setHandler($this);
            $s->service($this->post);
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
            $result['template'] = false;
        }
        return $result;
    }

    private function getInvoiceId($id)
    {
        if ($this->prefix) {
            $id = $this->prefix.$id;
        }
        return $this->app_id.'_'.$this->merchant_id.'_'.$id;
    }

    protected function formalizeData($result)
    {
        $transaction_data = parent::formalizeData(null);
        $transaction_data['native_id'] = $this->txn;
        $transaction_data['amount'] = is_object($result) && property_exists(get_class($result), 'amount') && !empty($result->amount) ? str_replace(',', '.', $result->amount) : 0;
        $transaction_data['currency_id'] = 'RUB';
        $transaction_data['order_id'] = $this->order_id;
        if (is_object($result) && property_exists(get_class($result), 'user') && !empty($result->user)) {
            $data['phone'] = $result->user;
            $transaction_data['view_data'] = 'Phone: '.$result->user;
        }
        if (is_object($result) && property_exists(get_class($result), 'status') && !empty($result->status)) {
            $transaction_data['view_status'] = $this->getBillCodeDescription(intval($result->status));
        }
        return $transaction_data;
    }

    protected function init()
    {
        $autload = waAutoload::getInstance();
        $autload->add("IShopServerWSService", "wa-plugins/payment/qiwi/vendors/qiwi/IShopServerWSService.php");
        $autload->add("IShopClientWSService", "wa-plugins/payment/qiwi/vendors/qiwi/IShopClientWSService.php");
        $autload->add("nusoap_base", "wa-plugins/payment/qiwi/vendors/nusoap/nusoap.php");
        return parent::init();
    }

    protected function getEndpointUrl($type = 'soap')
    {
        return ($type == 'soap') ? $this->url : $this->http_url;
    }

    public function supportedOperations()
    {
        return array(
            self::OPERATION_AUTH_CAPTURE,
            self::OPERATION_HOSTED_PAYMENT_AFTER_ORDER,
        );
    }

    public static function _getAlarmVariants()
    {
        $alarms = array();
        $alarms[] = array('title' => 'не оповещать', 'value' => 0);
        $alarms[] = array('title' => 'уведомление SMS-сообщением', 'value' => 1);
        $alarms[] = array('title' => 'уведомление звонком', 'value' => 2);
        return $alarms;
    }

    /**
     *
     * @return IShopServerWSService
     */
    private function getQiwiSoapClient($type = 'soap')
    {
        if (!class_exists('nusoap_base', false)) {
            class_exists('nusoap_base');
        }
        //TODO init proxy settings
        $options = array();
        $options['location'] = $this->getEndpointUrl($type);
        $options['trace'] = 1;
        $instance = new IShopServerWSService($this->path.'/vendors/qiwi/'.'IShopServerWS.wsdl', $options);
        //        $instance->setDebugLevel(9);
        $instance->soap_defencoding = 'UTF-8';
        return $instance;
    }

    /**
     *
     * @return IShopClientWSService
     */
    private function getQiwiSoapServer($type = 'soap')
    {
        if (!class_exists('nusoap_base', false)) {
            class_exists('nusoap_base');
        }
        $options = array();
        $options['location'] = $this->getEndpointUrl($type);
        $options['trace'] = 1;
        $instance = new IShopClientWSService($this->path.'/vendors/qiwi/'.'IShopClientWS.wsdl', $options);
        $instance->soap_defencoding = 'UTF-8';
        return $instance;
    }

    /**
     *
     *
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

        if ($this->TESTMODE) {
            self::log($this->id, array($login, $password, $txn, $status));
        }
        if (!$this->app_id || !$this->merchant_id) {
            $result->updateBillResult = 300;
            self::log($this->id, 'Unknown merchant data');
        } elseif (!$this->login || !$this->password) {
            self::log($this->id, 'Empty merchant data');
            $result->updateBillResult = 298;
        } elseif ($this->login != $login) {
            self::log($this->id, array('error' => 'updateBill: invalid login: '.$login.', expected: '.$this->login, 'txn' => $txn));
            $result->updateBillResult = 150;
        } elseif ($password != ($pass = $this->getPassword($this->order_id))) {
            self::log($this->id, 'Invalid password');
            if ($this->TESTMODE) {
                //TODO add data info
                $result->updateBillResult = 150;
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
            $soap_client = $this->getQiwiSoapClient();
            $params = new checkBill();
            $params->login = $this->login;
            $params->password = $this->password;
            $params->txn = $this->txn;
            $result = $soap_client->checkBill($params);
            $params->password = '***hidden***';
            self::log($this->id, array(
                'method'  => __METHOD__,
                'request' => var_export(get_object_vars($params), true),
                'code'    => $this->getBillCodeDescription($result->status),
                'result'  => var_export(get_object_vars($result), true),

            ));
        } catch (SoapFault $sf) {
            self::log($this->id, array(
                'method' => __METHOD__,
                'error'  => $sf->getMessage(),
            ));
        }
        return $result;
    }

    /**
     * SOAP callback method
     * optional future
     * @todo
     * @return void
     */
    private function cancelBill()
    {
        # login – логин (идентификатор) магазина;
        # password – пароль для магазина;
        # txn – уникальный идентификатор счета (максимальная длина 30 байт).
        ;
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
        if (setlocale(LC_CTYPE, 'ru_RU.CP-1251', 'ru_RU.CP1251', 'ru_RU.win', 'ru_RU.1251', 'Russian_Russia.1251', 'Russian_Russia.CP-1251', 'Russian_Russia.CP1251', 'Russian_Russia.win') === false) {
            self::log($this->id, __METHOD__."\tsetLocale failed");
        }
        $txn = $this->app_id.'_'.$this->merchant_id.'_'.$this->prefix.$order_id;
        $string = $txn.strtoupper(md5(iconv('utf-8', 'cp1251', $this->password)));
        $hash = strtoupper(md5(iconv('utf-8', 'cp1251', $string)));
        return $hash;
    }

}
