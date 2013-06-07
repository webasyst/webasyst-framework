<?php
return array(
    'test_mode' => array(
        'value'        => false,
        'title'        => /*_wp*/('Test environment'),
        'description'  => /*_wp*/('Enable to run USPS module in test environment; and disable when moving to production'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'user_id' => array(
        'value' => '',
        'title' => /*_wp*/('USPS User ID'),
        'description' => /*_wp*/('Your USPS.com account User ID. Required.'),
        'control_type' => waHtmlControl::INPUT,
    ),

    'zip' => array(
        'value' => '',
        'title' => /*_wp*/('Origin ZIP code'),
        'description' => /*_wp*/('Enter ZIP in the United States from where shipments will be sent. Required for shipping rates estimation.'),
        'control_type' => waHtmlControl::INPUT,
    ),

    'package_size' => array(
        'value' => '',
        'title' => /*_wp*/('Default package size'),
        'description' => /*_wp*/('For domestic (US only) shipments.'),
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            'Regular'  => /*_wp*/('Regular'),
            'Large'   => /*_wp*/('Large'),
            'Oversize'  => /*_wp*/('Oversize'),
        ),
    ),

    'services_domestic' => array(
        'value' => array(),
        'title' => /*_wp*/('Domestic shipments'),
        'description' => /*_wp*/('Shipping rates will be calculated for all selected options.'),
        'control_type' => waHtmlControl::GROUPBOX
    ),

    'services_international' => array(
        'value' => array(),
        'title' => /*_wp*/('International shipments'),
        'description' => /*_wp*/('Shipping rates will be calculated for all selected options.'),
        'control_type' => waHtmlControl::GROUPBOX
    )

);
//EOF