<?php
/**
 * @see www.2checkout.com
 * @property-read string $sid
 * @property-read string $secret
 * @property-read boolean $demo
 */

/** Plugin was heavily modified by vrs // xvrs.net
 * ===== 2CheckOut Pass Through Products: API and examples =====
 * https://www.2checkout.com/documentation/checkout/parameter-sets/pass-through-products
 * https://www.2checkout.com/documentation/checkout/dynamic-checkout
 *
 * ========================== Contacts ==========================
 * www.webasyst.com/developers/docs/features/contacts-app-integration/
 */

class twocheckoutPayment extends waPayment implements waIPayment
{

    public function allowedCurrency()
    {
        //return 'USD';
	// Currencies supported by 2CO
	return array('ARS', 'AUD', 'BRL', 'GBP', 'CAD', 'DKK', 'EUR', 'HKD', 'INR', 'ILS', 'JPY', 
	'LTL', 'MYR', 'MXN', 'NZD', 'NOK', 'PHP', 'RON', 'RUB', 'SGD', 'ZAR', 'SEK', 'CHF', 'TRY', 'AED', 'USD');
    }

    public function payment($payment_form_data, $order_data, $auto_submit = false)
    {
        $order = waOrder::factory($order_data);
        $form = array(
	    'sid'                 => $this->sid,
	    'mode' 		  => "2CO",

	    'li_0_type'		  => "product",
	    'li_0_name'		  => "Order #0140" . $order->id, // TODO: order number's prefix to be not hardcoded!
	    'li_0_quantity'	  => "1",
	    'li_0_price'	  => number_format(($order->total - $order->shipping), 2, '.', ''), 
	    'li_0_tangible'	  => "Y",

	    'li_1_type'		  => "shipping",
	    'li_1_name'		  => $order->shipping_name, //"Selected Shipping",
	    'li_1_quantity'	  => "1",
	    'li_1_price'	  => number_format($order->shipping, 2, '.', ''),  //"0",
	    'li_1_tangible'	  => "Y",
	    'purchase_step'	  => 'billing-information', // procede to Billing Information page 
	    			     			    // to review Card Holder's billing details

	    'currency_code'	  => $order->currency,

	    'card_holder_name'	    => waLocale::transliterate($order->getContact()->get('name', 'default')),
            'street_address'      => $order->billing_address['street'],
            'street_address2'	  => "NA",
            'city'                => $order->billing_address['city'],
            'state'               => $order->billing_address['region_name'],
            'zip'                 => $order->billing_address['zip'],
            //'country'             => $order->billing_address['country_name'],
	    //'country'             => (array_key_exists($order->billing_address['country_name'], $ru_en) 
	    //			      ?  $ru_en[$order->billing_address['country_name']] : $ru_en['Неизвестно']),
	    'country'             => $this->_w($order->billing_address['country_name']),

	    'email'               => $order->contact_email,
	    'phone'		  => $order->contact_phone,

	    'ship_name'		  => waLocale::transliterate($order->getContact()->get('name', 'default')),
            'ship_street_address' => $order->shipping_address['street'],
	    'ship_street_address2' => "NA", // 2CO's mandatory field. Since substringing is not possible, let it be `Not Available'
            'ship_city'           => $order->shipping_address['city'],
            'ship_state'          => $order->shipping_address['region_name'],
            'ship_zip'            => $order->shipping_address['zip'],
            'ship_country'        => $this->_w($order->shipping_address['country_name']),
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
	return 'https://www.2checkout.com/checkout/purchase';
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
