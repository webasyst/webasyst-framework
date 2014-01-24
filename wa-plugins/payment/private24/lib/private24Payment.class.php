<?php
/**
 * @see https://api.privatbank.ua/article/4/
 *
 * @property-read string $merchant
 * @property-read string $pass
 */
class private24Payment extends waPayment implements waIPayment
{
    private $order_id;

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);
        $view = wa()->getView();
        $view->assign('data', $payment_form_data);
        $view->assign('order', $order_data);
        $view->assign('settings', $this->getSettings());
        $form = array();
        $form['amt'] = number_format($order->total, 2, '.', '');
        $form['ccy'] = $order->currency;
        $form['order'] = sprintf('%s_%s_%s', $this->app_id, $this->merchant_id, $order->id);
        $form['merchant'] = $this->merchant;

        $pattern = "@[^\\w\\d".preg_quote("~@#$%^-_(){}'`+=[]:;/\\", '@')."]+@u";
        $description = trim(preg_replace('@\\s{2,}@', ' ', preg_replace($pattern, ' ', $order->description)));
        $form['details'] = mb_substr($description, 0, 130);
        $form['ext_details'] = mb_substr($description, 0, 250);
        $form['pay_way'] = 'privat24';


        $form['return_url'] = $this->getRelayUrl().'?customer=1';
        $form['server_url'] = $this->getRelayUrl();

        $view->assign('form', $form);
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('auto_submit', $auto_submit);
        return $view->fetch($this->path.'/templates/payment.html');
    }

    protected function callbackInit($request)
    {
        $this->request = $request;
        $pattern = '/^([a-z]+)_(.+)_(.+)$/';
        $data = array();
        parse_str(ifset($request['payment']), $data);
        if (!empty($data['order']) && preg_match($pattern, $data['order'], $match)) {
            $this->app_id = $match[1];
            $this->merchant_id = $match[2];
            $this->order_id = $match[3];
        }
        return parent::callbackInit($request);
    }

    protected function callbackHandler($request)
    {
        if (!$this->order_id || !$this->app_id || !$this->merchant_id) {
            throw new waPaymentException('invalid order number', 404);
        }

        if (!$this->verifySign($request)) {
            throw new waPaymentException('invalid signature', 404);
        }

        $result = array();
        $transaction_data = $this->formalizeData($request);
        if (!empty($request['customer'])) {
            switch (ifempty($transaction_data['state'])) {
                case self::STATE_CAPTURED:
                    $result['redirect'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, $transaction_data);
                    break;
                case self::STATE_DECLINED:
                    $result['redirect'] = $this->getAdapter()->getBackUrl(waAppPayment::URL_FAIL, $transaction_data);
            }
        }
        if (ifempty($transaction_data['state']) == self::STATE_CAPTURED) {
            $transaction_data = $this->saveTransaction($transaction_data, $request);

            $this->execAppCallback(self::CALLBACK_PAYMENT, $transaction_data);
        }

        return $result;
    }

    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data = parent::formalizeData($transaction_raw_data);
        $raw = array();
        parse_str(ifset($transaction_raw_data['payment']), $raw);
        $transaction_data = array_merge($transaction_data, array(
            'type'        => null,
            'native_id'   => ifset($raw['order']),
            'amount'      => ifset($raw['amt']),
            'currency_id' => ifset($raw['ccy']),
            'result'      => 1,
            'order_id'    => $this->order_id,
            'view_data'   => 'Phone: '.ifset($raw['sender_phone'], '-'),
        ));
        if ($transaction_raw_data['state'] == 'ok') {
            $transaction_data['state'] = self::STATE_CAPTURED;
        } elseif ($transaction_raw_data['state'] == 'fail') {
            $transaction_data['state'] = self::STATE_DECLINED;

        }

        return $transaction_data;
    }

    private function verifySign($request)
    {
        $sign = ifset($request['signature']);
        return ($sign && ($sign == sha1(md5(ifset($request['payment']).$this->pass))));
    }

    private function getEndpointUrl()
    {
        return 'https://api.privatbank.ua/p24api/ishop';
    }

    public function allowedCurrency()
    {
        return array('UAH', 'USD', 'EUR');
    }

}
