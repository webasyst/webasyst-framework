<?php
/**
 *
 * @link http://www.liqpay.ru/
 * @link https://liqpay.com/?do=pages&p=cnb12
 *
 * @property string $merchant_id Merchant ID
 * @property string $secret_key Signature
 * @property string $gateway Payment method
 * @property string $order_prefix Invoice number prefix
 * @property boolean $bugfix Добавлять случайное число к номеру счета
 * @property string $customer_phone Customer telephone number
 */
class liqpayPayment extends waPayment
{
    /**
     *
     * @var SimpleXMLElement
     */
    private $xml;
    /**
     *
     * @var string
     */
    private $raw_xml;
    private $pattern = '/^(\w[\w\d]+)\.([^_]+)_(.+)$/';

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);

        if (!in_array($order->currency, $this->allowedCurrency())) {
            throw new waException('Unsupported currency');
        }

        $customer_phone = '';
        if ($this->customer_phone) {
            $customer_phone = $order->getContactField($this->customer_phone);
        }

        $description = htmlentities($order->description_en, ENT_QUOTES, 'utf-8');
        $method = $this->gateway;
        if (!$method) {
            $method = 'card, liqpay, delayed';
        }

        $customer_url = $this->getAdapter()->getBackUrl(waAppPayment::URL_SUCCESS, array('order_id' => $order->id));
        $result_url = $this->getRelayUrl();

        $suffix = '';
        if ($this->bugfix) {
            $suffix = sprintf('_%04d', rand(1000, 9999));
        }

        $order_id = $this->order_prefix.$order->id.$suffix;

        $amount = str_replace(',', '.', round($order->total, 2));
        $currency = $order->currency;
        if ($currency == 'RUB') {
            $currency = 'RUR';
        }
        $order_id = htmlentities(sprintf('%s.%d_%s', $this->app_id, $this->merchant_id, $order_id), ENT_QUOTES, 'utf-8');

        $this->raw_xml = "<request>
<version>1.2</version>
<result_url>{$customer_url}</result_url>
<server_url>{$result_url}</server_url>
<merchant_id>{$this->getSettings('merchant_id')}</merchant_id>
<order_id>{$order_id}</order_id>
<amount>{$amount}</amount>
<currency>{$currency}</currency>
<description>{$description}</description>
<default_phone>{$customer_phone}</default_phone>
<pay_way>{$method}</pay_way>
</request>";

        $hidden_fields = array(
            'operation_xml' => base64_encode($this->raw_xml),
            'signature'     => $this->getSignature(),
        );
        $view = wa()->getView();
        $view->assign('hidden_fields', $hidden_fields);
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('auto_submit', $auto_submit);
        return $view->fetch($this->path.'/templates/payment.html');
    }

    private function getSignature()
    {
        return base64_encode(sha1($this->secret_key.$this->raw_xml.$this->secret_key, 1));
    }

    private function getEndpointUrl()
    {
        return 'https://www.liqpay.com/?do=clickNbuy';
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
        $this->raw_xml = base64_decode(ifempty($request['operation_xml'], 'PHJlc3BvbnNlLz4='));
        if ($this->raw_xml && ($this->xml = @simplexml_load_string($this->raw_xml))) {
            if (preg_match($this->pattern, (string)$this->xml->order_id, $matches)) {
                $this->app_id = $matches[1];
                $this->merchant_id = $matches[2];
            }
        }
        return parent::callbackInit($request);
    }

    protected function callbackHandler($request)
    {
        $signature = ifempty($request['signature']);
        if (empty($signature) || ($signature != $this->getSignature())) {
            throw new waException("Invalid signature");
        }

        $transaction_data = $this->formalizeData($request);
        $callback_method = null;
        switch (ifset($transaction_data['state'])) {
            case self::STATE_CAPTURED:
                $callback_method = self::CALLBACK_PAYMENT;
                break;
            case self::STATE_DECLINED:
                $callback_method = self::CALLBACK_DECLINE;
                break;
        }

        if ($callback_method) {
            $transaction_data = $this->saveTransaction($transaction_data, $request);
            $this->execAppCallback($callback_method, $transaction_data);
        }
    }

    protected function formalizeData($transaction_raw_data)
    {
        $transaction_data = parent::formalizeData($transaction_raw_data);
        $transaction_data['native_id'] = (string)$this->xml->transaction_id;
        $transaction_data['amount'] = (string)$this->xml->amount;
        $transaction_data['currency_id'] = (string)$this->xml->currency;
        $order_id = null;
        if (preg_match($this->pattern, (string)$this->xml->order_id, $matches)) {
            $order_id = $matches[3];
        }

        if ($this->bugfix) {
            $order_id = preg_replace('/_\d{1,4}$/', '', $order_id);
        }

        if ($this->order_prefix) {
            $pattern = wa_make_pattern($this->order_prefix, '@');
            $pattern = "@^{$pattern}(.+)$@";
            $order_id = null;
            if (preg_match($pattern, $order_id, $matches)) {
                $order_id = $matches[1];
            }
        }

        $transaction_data['order_id'] = $order_id;
        $view_data = array();
        if ((string)$this->xml->transaction_id) {
            $view_data[] = $this->_w('Transaction number').': '.(string)$this->xml->transaction_id;
        }
        if ((string)$this->xml->pay_way) {
            $view_data[] = $this->_w('Pay way').': '.(string)$this->xml->pay_way;
        }

        switch ($status = (string)$this->xml->status) {
            case 'success': /*покупка совершена*/
                $transaction_data['state'] = self::STATE_CAPTURED;
                $transaction_data['type'] = self::OPERATION_AUTH_CAPTURE;
                $transaction_data['result'] = 1;
                break;
            case 'failure': /*покупка отклонена*/
                $transaction_data['state'] = self::STATE_DECLINED;
                $transaction_data['type'] = self::OPERATION_CANCEL;
                $transaction_data['result'] = 1;
                $view_data[] = $this->_w('Transaction declined').": ".(string)$this->xml->code;
                break;
            case 'wait_secure': /*платеж находится на проверке*/
                $view_data[] = $this->_w('Transaction requires confirmation');
                break;
            default:
                $view_data[] = sprintf($this->_w("Unknown status %s"), htmlentities($status, ENT_QUOTES, 'utf-8'));
                break;
        }

        if ((string)$this->xml->sender_phone) {
            $view_data[] = $this->_w('Phone number').': '.(string)$this->xml->sender_phone;
        }
        if ($view_data) {
            $transaction_data['view_data'] = implode("\n", $view_data);
        }
        return $transaction_data;
    }
}
