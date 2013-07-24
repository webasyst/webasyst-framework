<?php
/**
 * @property-read string $login
 * @property-read string $key
 * @property-read boolean $testmode
 */
class authorizenetsimPayment extends waPayment
{
    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);
        $fp_timestamp = time();
        $fp_sequence = $order->id;

        $fp_hash_string = $this->login."^".$fp_sequence."^".$fp_timestamp."^".$order->total."^".$order->currency;

        $form = array(
            'x_login'              => $this->login,
            'x_test_request'       => $this->testmode ? 'TRUE' : 'FALSE',
            'x_show_form'          => 'PAYMENT_FORM',
            'x_fp_sequence'        => $fp_sequence,
            'x_fp_timestamp'       => $fp_timestamp,
            'x_fp_hash'            => $this->hmac($this->key, $fp_hash_string),
            'x_amount'             => $order->total,
            'x_currency_code'      => $order->currency,
            'x_first_name'         => $order->billing_address['firstname'],
            'x_last_name'          => $order->billing_address['lastname'],
            'x_address'            => $order->billing_address['street'],
            'x_city'               => $order->billing_address['city'],
            'x_state'              => $order->billing_address['region_name'],
            'x_zip'                => $order->billing_address['zip'],
            'x_country'            => $order->billing_address['country_name'],
            'x_email'              => $order->contact_email,

            "x_customer_ip"        => waRequest::getIp(),
            'x_invoice_num'        => $order->id_str,
            'x_description'        => $order->description_en,
            'x_ship_to_first_name' => $order->shipping_address['firstname'],
            'x_ship_to_last_name'  => $order->shipping_address['lastname'],
            'x_ship_to_address'    => $order->shipping_address['street'],
            'x_ship_to_city'       => $order->shipping_address['city'],
            'x_ship_to_state'      => $order->shipping_address['region_name'],
            'x_ship_to_zip'        => $order->shipping_address['zip'],
            'x_ship_to_country'    => $order->shipping_address['country_name'],

            'x_relay_response'     => 'FALSE',
        );
        $view = wa()->getView();
        $view->assign('form', $form);
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('auto_submit', $auto_submit);
        return $view->fetch($this->path.'/templates/payment.html');
    }

    public function allowedCurrency()
    {
        return true;
    }

    private function getEndpointUrl()
    {
        return 'https://secure.authorize.net/gateway/transact.dll';
    }

    /**
     * Makes HMAC MD5 hash of the $data
     * @see http://www.php.net/manual/en/function.mhash.php
     * @param $key string
     * @param $data string
     * @return string hashed string
     */
    private function hmac($key, $data)
    {
        // RFC 2104 HMAC implementation for php.
        // Creates an md5 HMAC.
        // Eliminates the need to install mhash to compute a HMAC
        // Hacked by Lance Rushing

        $b = 64; // byte length for md5
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return md5($k_opad.pack("H*", md5($k_ipad.$data)));
    }

}
