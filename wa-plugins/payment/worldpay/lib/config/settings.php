<?php

return array(
    'test_mode' => array(
        'value' => '',
    ),
    'tm_response_type' => array(
        'value' => '',
    ),
    'instid' => array(
        'value' => '',
        'title' => /*_wp*/('Your WorldPay Installation ID'),
        'description' => /*_wp*/('Please specify your WorldPay Installation ID (more information at www.worldpay.com)'),
        'control_type' => waHtmlControl::INPUT,
    ),
    'md5_secret' => array(
        'value' => '',
        'title' => /*_wp*/('Specify your MD5 secret'),
        'description' => /*_wp*/('Enter this value into the MD5 secret for transactions field in the Integration Setup for your installation via the Merchant Interface. If you wish to disable the MD5 functionality at any point simply remove the secret key value from your installation.'),
        'control_type' => waHtmlControl::INPUT,
    ),
    'response_password' => array(
        'value' => '',
        'title' => /*_wp*/('Payment Response password'),
        'description' => /*_wp*/('Set it up and copy to "Payment Response password" the Merchant Interface. IF these passwords NOT equals customers will receive an error'),
        'control_type' => waHtmlControl::INPUT
    ),
);
