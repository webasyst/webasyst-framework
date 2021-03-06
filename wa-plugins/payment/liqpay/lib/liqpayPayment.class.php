<?php

/**
 * Checkout 3.0 & Callback 3.0 support
 *
 * @link https://www.liqpay.ua/ru
 *
 * @link https://www.liqpay.ua/documentation/api/aquiring/checkout/
 * @link https://www.liqpay.ua/documentation/api/callback
 *
 * @property string $public_key Публичный ключ - идентификатор магазина. Получить ключ можно в настройках магазина
 * @property string $secret_key Приватный ключ
 * @property boolean $sandbox
 */
class liqpayPayment extends waPayment
{
    private $pattern = '/^(\w[\w\d]+)\.([^_]+)_(.+)$/';

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);

        if (!in_array($order->currency, $this->allowedCurrency())) {
            throw new waException('Unsupported currency');
        }

        $params = array(
            'version'     => 3,
            'public_key'  => $this->public_key,
            'action'      => 'pay',
            'amount'      => $order->total,
            'currency'    => $order->currency,
            'description' => $order->description,
            'order_id'    => $this->getOrderId($order->id),
            'server_url'  => $this->getRelayUrl(),
            'result_url'  => $this->getRelayUrl().'?'.http_build_query([
                'order_id'    => $order->id,
                'app_id'      => $this->app_id,
                'merchant_id' => $this->merchant_id,
            ])
        );

        if ($this->sandbox) {
            $params['sandbox'] = 1;
        }

        $data = base64_encode(json_encode($params));
        $hidden_fields = array(
            'data'      => $data,
            'signature' => $this->getSignature($data),
        );
        $view = wa()->getView();
        $view->assign('hidden_fields', $hidden_fields);
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('auto_submit', $auto_submit);
        return $view->fetch($this->path.'/templates/payment.html');
    }

    public function allowedCurrency()
    {
        return array(
            'UAH',
            'RUB',
            'RUR',
            'USD',
            'EUR',
        );
    }

    protected function callbackInit($request)
    {
        $data = json_decode(base64_decode(ifempty($request['data'], 'W10=')), true);
        if ($data && isset($data['order_id'])) {
            if (preg_match($this->pattern, $data['order_id'], $matches)) {
                $this->app_id = $matches[1];
                $this->merchant_id = $matches[2];
            }
        } else {
            $this->app_id = ifset($request['app_id']);
            $this->merchant_id = ifset($request['merchant_id']);
        }
        return parent::callbackInit($request);
    }

    protected function callbackHandler($request)
    {
        if (!empty($request['result'])) {
            return array(
                'redirect' => $this->getAdapter()->getBackUrl()
            );
        }
        $data = ifempty($request['data'], 'W10=');
        $signature = ifempty($request['signature']);
        $order_id = ifempty($request['order_id']);
        $merchant_id = ifempty($request['merchant_id'], 0);

        if (!empty($signature)) {
            if ($signature != $this->getSignature($data)) {
                throw new waException("Invalid signature");
            }

            $data = json_decode(base64_decode($data), true);
            $transaction_data = $this->formalizeData($data);
            switch (ifset($transaction_data['state'])) {
                case self::STATE_CAPTURED:
                    $callback_method = self::CALLBACK_PAYMENT;
                    break;
                case self::STATE_DECLINED:
                    $callback_method = self::CALLBACK_DECLINE;
                    break;
                default:
                    $callback_method = self::CALLBACK_NOTIFY;
                    break;
            }

            $transaction_data = $this->saveTransaction($transaction_data, $request);
            $this->execAppCallback($callback_method, $transaction_data);
        }

        /** response processing to result_url */
        if (!empty($order_id) && $merchant_id > 0) {
            /** если в запросе есть параметры order_id и merchant_id
                то будем считать что покупатель пришел с ПС LiqPay */
            $payment_status = $this->getPaymentInfo($order_id);
            if ('success' == ifset($payment_status['status']) || 'sandbox' == ifset($payment_status['status'])) {
                $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, array('order_id' => $order_id));
            } elseif (in_array(ifset($payment_status['status']), ['failure', 'error'])) {
                $transaction_id = ifempty($transaction_data, 'id', 0);
                $url  = $this->getAdapter()->getBackUrl(waAppPayment::URL_DECLINE, ['order_id' => $order_id]);
                $url .= (empty($transaction_id) ? '' : '&'.http_build_query(['transaction_id' => $transaction_id]));
            } else {
                $url = $this->getAdapter()->getBackUrl(waAppPayment::URL_DECLINE);
            }

            return ['redirect' => $url];
        } elseif (empty($signature)) {
            throw new waException('Invalid request');
        }
    }

    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data = parent::formalizeData($transaction_raw_data);
        $transaction_data['native_id'] = $transaction_raw_data['liqpay_order_id'];
        $transaction_data['amount'] = $transaction_raw_data['amount'];
        $transaction_data['currency_id'] = $transaction_raw_data['currency'];
        $order_id = null;
        if (preg_match($this->pattern, $transaction_raw_data['order_id'], $matches)) {
            $order_id = $matches[3];
        }

        $transaction_data['order_id'] = $order_id;
        $view_data = array();

        if (!empty($transaction_raw_data['transaction_id'])) {
            $view_data[] = $this->_w('Transaction number').': '.$transaction_raw_data['transaction_id'];
        }

        $status_descriptions = array(
            'otp_verify'       => 'Требуется OTP подтверждение клиента. OTP пароль отправлен на номер телефона Клиента.',
            //Для завершения платежа, требуется выполнить otp_verify.
            '3ds_verify'       => 'Требуется 3DS верификация.',
            //Для завершения платежа, требуется выполнить 3ds_verify.
            'cvv_verify'       => 'Требуется ввод CVV карты отправителя.',
            //Заполните параметр card_cvv и повторите запрос.
            'sender_verify'    => 'Требуется ввод данных отправителя.',
            //Заполните параметры sender_first_name, sender_last_name, sender_country_code, sender_city, sender_address, sender_postal_code и повторите запрос.
            'receiver_verify'  => 'Требуется ввод данных получателя.',
            //Заполните параметры receiver_first_name, receiver_last_name и повторите запрос.
            'phone_verify'     => 'Ожидается ввод телефона клиентом',
            'ivr_verify'       => 'Ожидается подтверждение звонком ivr',
            'pin_verify'       => 'Ожидается подтверждение pin-code',
            'captcha_verify'   => 'Ожидается подтверждение captcha',
            'password_verify'  => 'Ожидается подтверждение пароля приложения Приват24',
            'senderapp_verify' => 'Ожидается подтверждение в приложении Sender',

            'processing'        => 'Платеж обрабатывается',
            'prepared'          => 'Платеж создан, ожидается его завершение отправителем',
            'wait_bitcoin'      => 'Ожидается перевод bitcoin от клиента',
            'wait_secure'       => 'Платеж на проверке',
            'wait_accept'       => 'Деньги с клиента списаны, но магазин еще не прошел проверку',
            'wait_lc'           => 'Аккредитив. Деньги с клиента списаны, ожидается подтверждение доставки товара',
            'hold_wait'         => 'Сумма успешно заблокирована на счету отправителя',
            'cash_wait'         => 'Ожидается оплата наличными в ТСО.',
            'wait_qr'           => 'Ожидается сканирование QR-кода клиентом.',
            'wait_sender'       => 'Ожидается подтверждение оплаты клиентом в приложении Privat24/Sender.',
            'wait_card'         => 'Не установлен способ возмещения у получателя',
            'wait_compensation' => 'Платеж успешный, будет зачислен в ежесуточной проводке',
            'invoice_wait'      => 'Инвойс создан успешно, ожидается оплата',
            'wait_reserve'      => 'Средства по платежу зарезервированы для проведения возврата по ранее поданной заявке',
        );
        switch ($status = $transaction_raw_data['status']) {
            case 'success':
            case 'sandbox':
                /** покупка совершена */
                $transaction_data['state'] = self::STATE_CAPTURED;
                $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
                $transaction_data['result'] = 1;
                if ('sandbox' === $status) {
                    $view_data[] = 'Тестовый платеж';
                }
                break;
            case 'failure': /*покупка отклонена*/
            case 'error':
                $transaction_data['state'] = self::STATE_DECLINED;
                $transaction_data['type'] = self::OPERATION_CANCEL;
                $transaction_data['result'] = 1;
                $reason = '';
                if ($transaction_raw_data['err_code']) {
                    $reason = sprintf('%s — %s', $transaction_raw_data['err_code'], ifset($transaction_raw_data['err_description'], $transaction_raw_data['err_code']));
                }

                $view_data[] = $this->_w('Transaction declined').": ".htmlentities($reason, ENT_NOQUOTES, 'utf-8');
                break;
            case 'wait_secure': /*платеж находится на проверке*/
                $view_data[] = $this->_w('Transaction requires confirmation');
                break;
            default:
                $d = ifset($status_descriptions[$status]);
                if ($d) {
                    $view_data[] = $d;
                } else {
                    $view_data[] = sprintf($this->_w("Unknown status “%s”"), htmlentities($status, ENT_NOQUOTES, 'utf-8'));
                }
                break;
        }

        if ($view_data) {
            $transaction_data['view_data'] = implode("\n", $view_data);
        }
        return $transaction_data;
    }

    private function getSignature($data)
    {
        return base64_encode(sha1($this->secret_key.$data.$this->secret_key, 1));
    }

    private function getEndpointUrl()
    {
        return 'https://www.liqpay.ua/api/3/checkout';
    }

    private function getApiUrl()
    {
        return 'https://www.liqpay.ua/api/request';
    }

    private function getOrderId($id)
    {
        return sprintf('%s.%s_%s', $this->app_id, $this->merchant_id, $id);
    }

    /**
     * Запросы к ПС по API для получения информации
     * о конкретном платеже
     *
     * https://www.liqpay.ua/documentation/api/information/status/doc
     * @throws waPaymentException
     */
    protected function getPaymentInfo($order_id)
    {
        $params = [
            'action'     => 'status',
            'order_id'   => $this->getOrderId($order_id),
            'public_key' => $this->public_key,
            'version'    => 3
        ];
        if ($this->sandbox) {
            $params['sandbox'] = 1;
        }
        $data       = base64_encode(json_encode($params));
        $postfields = http_build_query([
            'data'      => $data,
            'signature' => $this->getSignature($data)
        ]);

        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->getApiUrl());
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec($ch);
            //$this->server_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            throw new waPaymentException('Для работы плагина требуется модуль PHP curl');
        }

        return json_decode($server_output, true);
    }
}
