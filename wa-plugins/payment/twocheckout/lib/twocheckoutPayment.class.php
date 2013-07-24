<?php
/**
 * @see www.2checkout.com
 * @property-read string $sid
 * @property-read string $secret
 * @property-read boolean $demo
 */
class twocheckoutPayment extends waPayment implements waIPayment
{

    public function allowedCurrency()
    {
        return 'USD';
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);
        $form = array(
            'sid'                 => $this->sid,
            'total'               => $order->total,
            'cart_order_id'       => $order->id,

            'card_holder_name'    => $order->billing_address['name'],
            'street_address'      => $order->billing_address['street'],
            'city'                => $order->billing_address['city'],
            'state'               => $order->billing_address['region_name'],
            'zip'                 => $order->billing_address['zip'],
            'country'             => $order->billing_address['country_name'],

            'email'               => $order->contact_email,

            'ship_street_address' => $order->shipping_address['street'],
            'ship_city'           => $order->shipping_address['city'],
            'ship_state'          => $order->shipping_address['region_name'],
            'ship_zip'            => $order->shipping_address['zip'],
            'ship_country'        => $order->shipping_address['country_name'],

            'c_prod'              => "ShopScript5 order",
            'id_type'             => 2,
        );
        if ($this->demo) {
            $form['demo'] = 'Y';
        }

        $form['key'] = $this->getSign($form);

        $view = wa()->getView();
        $view->assign('form', $form);
        $view->assign('form_url', $this->getEndpointUrl());
        $view->assign('auto_submit', $auto_submit);
        return $view->fetch($this->path.'/templates/payment.html');
    }

    private function getEndpointUrl()
    {
        return 'https://www.2checkout.com/2co/buyer/purchase';
    }

    private function getSign($form)
    {
        $string = '';
        $string .= $this->secret;
        $string .= $form['sid'];
        $string .= $form['cart_order_id'];
        $string .= $form['total'];
        return strtoupper(md5($string));
    }
}
