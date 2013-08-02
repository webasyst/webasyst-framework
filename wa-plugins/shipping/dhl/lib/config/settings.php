<?php

return array(
    'test_mode' => array(
        'value' => '',
        'title' => /*_wp*/('Test environment'),
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'login' => array(
        'value' => '',
        'title' => /*_wp*/('API System ID'),
        'description' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'password' => array(
        'value' => '',
        'title' => /*_wp*/('API Password'),
        'description' => '',
        'control_type' => waHtmlControl::INPUT,
    ),

    'region_zone' => array(
            'title' => /*_wp*/('Sender\'s region'),
            'control_type' => 'RegionZoneControl',
            'items' => array(
                    'country' => array(
                            'value' => 'usa',
                            'description' => /*_wp*/('Select sender\'s country from which shipments will be sent.')
                    ),
                    'region' => array(
                            'value' => 'NY',
                            'description' => /*_wp*/('Sender\'s state/province'
                            )
                    ),
                    'city' => array(
                            'value' => 'New York',
                            'description' => /*_wp*/('Sender\'s city name')
                    ),
            )
    ),
    
    'zip' => array(
        'value' => '',
        'title' => /*_wp*/('Sender\'s ZIP code'),
        'description' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    
    'package_type' => array(
        'value' => '',
        'title' => /*_wp*/('Package type'),
        'description' => '',
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                'value' => 'EE',
                'title' => 'DHL Express Envelope'
            ),
            array(
                'value' => 'OD',
                'title' => 'Other DHL Packaging'
            ),
            array(
                'value' => 'CP',
                'title' => 'Customer-provided'
            )
        ),
    ),
    
    'product_code' => array(
        'value' => 'D',
        'title' => /*_wp*/('DHL Product'),
        'description' => '',
        'control_type' => waHtmlControl::SELECT,
    ),
);