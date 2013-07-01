<?php

return array(
    'test_mode' => array(
        'value'        => false,
        'title'        => /*_wp*/('Test environment'),
        'description'  => /*_wp*/('Enable to run FedEx module in test environment; and disable when moving to production'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'account_number' => array(
        'value' => '',
        'title' => /*_wp*/('Account number'),
        'description' => /*_wp*/('Please enter your FedEx account number'),
        'control_type' => waHtmlControl::INPUT,
    ),
    'meter_number' => array(
        'value' => '',
        'title' => /*_wp*/('Meter number'),
        'description' => /*_wp*/('If you do not have a meter number simply leave this field blank. It will be generated automatically.'),
        'control_type' => waHtmlControl::INPUT,
    ),
    'developer_key' => array(
        'value' => '',
        'title' => /*_wp*/('Developer Key'),
        'description' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'developer_password' => array(
        'value' => '',
        'title' => /*_wp*/('Developer Password'),
        'description' => '',
        'control_type' => waHtmlControl::INPUT,
    ),
    'packaging' => array(
        'value' => 'FEDEX_ENVELOPE',
        'title' => /*_wp*/('Packaging'),
        'description' => /*_wp*/("For 'FedEx Ground' must be 'Your packaging' only"),
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                'title' => /*_wp*/('FedEx envelope'),
                'value' => 'FEDEX_ENVELOPE',
            ),
            array(
                'title' => /*_wp*/('FedEx pak'),
                'value' => 'FEDEX_PAK',
            ),
            array(
                'title' => /*_wp*/('FedEx box'),
                'value' => 'FEDEX_BOX',
            ),
            array(
                'title' => /*_wp*/('FedEx tube'),
                'value' => 'FEDEX_TUBE',
            ),
            array(
                'title' => /*_wp*/('FedEx 10 kg box'),
                'value' => 'FEDEX_10KG_BOX',
            ),
            array(
                'title' => /*_wp*/('FedEx 25 kg box'),
                'value' => 'FEDEX_25KG_BOX',
            ),
            array(
                'title' => /*_wp*/('Your packaging'),
                'value' => 'YOUR_PACKAGING',
            ),
            array(
                'title' => /*_wp*/('Individual packages'),
                'value' => 'INDIVIDUAL_PACKAGES',
            ),
        ),
    ),
    'carrier' => array(
        'value' => 'ALL',
        'title' => '',
        'description' => /*_wp*/('Please select FedEx service'),
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array(
                'title' => /*_wp*/('All'),
                'value'=>'ALL'
            ),
            array(
                'title' => /*_wp*/('FedEx Express'),
                'value'=>'FDXE'
            ),
            array(
                'title' => /*_wp*/('FedEx Ground'),
                'value'=> 'FDXG'
            ),
        ),
    ),
    'country' => array(
        'value' => 'usa'
    ),
    'region' => array(
        'value' => 'NY'
    ),
    'city' => array(
        'value' => ''
    ),
    'address' => array(
        'value' => ''
    ),
    'zip' => array(
        'value' => ''
    ),
);