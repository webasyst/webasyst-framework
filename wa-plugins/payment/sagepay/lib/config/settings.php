<?php

return array(
    'test_mode' => array(
        'value' => '',
        'title' => /*_wp*/('Test mode'),
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'vendor_name' => array(
        'value' => '',
        'title' => /*_wp*/('Vendor name'),
        'description' => /*_wp*/('Your unique company identifier assigned to you by SagePay'),
        'control_type' => waHtmlControl::INPUT,
    ),
    'vendor_email' => array(
        'value' => '',
        'title' => /*_wp*/('Vendor email'),
        'description' => /*_wp*/('If specified, a notification will be sent to this email address upon completion (whether successful or not) of each transaction.'),
        'control_type' => waHtmlControl::INPUT,
    ),
    'crypt_password' => array(
        'value' => '',
        'title' => /*_wp*/('Password for data encryption/decryption'),
        'description' => /*_wp*/('This string will be encrypted using the AES/CBC/PCKS#5 algorithm and the pre-registered encryption password, then subsequently base64-encoded to allow safe transport in an HTML form.'),
        'control_type' => waHtmlControl::INPUT
    ),
    'currency' => array(
        'value' => 'GBP',
        'title' => /*_wp*/('Currency'),
        'description' => /*_wp*/('This currency must be supported by one of your Sage Pay merchant accounts, otherwise all transactions will be rejected.'),
        'control_type' => waHtmlControl::SELECT,
    )
);
