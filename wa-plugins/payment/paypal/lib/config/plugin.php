<?php
/**
 * Payment plugin general description
 */
return array(
    'name'        => /*_wp*/('PayPal'),
    'description' => /*_wp*/('PayPal Payments Standard Integration'),

    # plugin icon
    'icon'        => 'img/paypal16.png',

    # default payment gateway logo
    'logo'        => 'img/paypal.png',

    # plugin vendor ID (for 3rd parties vendors it's a number)
    'vendor'      => 'webasyst',
    # plugin version
    'version'     => '1.0.6',
    'type'        => waPayment::TYPE_ONLINE,
);
