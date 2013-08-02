<?php

return array(
    'test_mode' => array(
        'value' => '',
        'title' => /*_wp*/('Test environment'),
        'description' => /*_wp*/(''),
        'control_type' => waHtmlControl::CHECKBOX
    ),
    'customer_number' => array(
        'value' => '',
        'title' => /*_wp*/('Customer number'),
        'description' => '',
        'control_type' => waHtmlControl::INPUT
    ),
    'test_key_number' => array(
        'value' => '',
        'title' => /*_wp*/('Test (development) key number'),
        'description' => /*_wp*/('Use <strong>username:password</strong> format. Do not add spaces around the colon.'),
        'control_type' => waHtmlControl::INPUT
    ),
    'key_number' => array(
        'value' => '',
        'title' => /*_wp*/('Production key number'),
        'description' => /*_wp*/('Use <strong>username:password</strong> format. Do not add spaces around the colon.'),
        'control_type' => waHtmlControl::INPUT
    ),
    'zip' => array(
        'value' => '',
        'title' => /*_wp*/('Origin postal code'),
        'description' => /*_wp*/('Enter sender\'s postal code without spaces.'),
        'control_type' => waHtmlControl::INPUT
    ),
);
