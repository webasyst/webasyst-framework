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
    ),

    // for international labels
    'content_type' => array(
        'value' => '',
        'title' => /*_wp*/('Content type'),
        'description' => /*_wp*/('Specify the content of the package or envelope'),
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            'MERCHANDISE' => /*_wp*/('Merchandise'),
            'SAMPLE' => /*_wp*/('Sample'),
            'GIFT' => /*_wp*/('Gift'),
            'DOCUMENTS' => /*_wp*/('Documents'),
            'RETURN' => /*_wp*/('Return'),
            'HUMANITARIAN' => /*_wp*/('Humanitarian'),
            'DANGEROUSGOODS' => /*_wp*/('Dangerous goods'),
            'OTHER' => /*_wp*/('Other'),
        ),
    ),

    // for international labels
    'other_content_type' => array(
        'value' => '',
        'title' => /*_wp*/('Other content type'),
        'description' => /*_wp*/('If chosen other content type, specify what kind of content manually'),
        'control_type' => waHtmlControl::INPUT,
    ),

    'name' => array(
            'value' => '',
            'title' => /*_wp*/('Sender name'),
            'description' => /*_wp*/('Name of sender showed in printing labels'),
            'control_type' => waHtmlControl::INPUT
    ),

    'region_zone' => array(
            'title' => /*_wp*/('Sender region'),
            'control_type' => 'RegionZoneControl',
            'items' => array(
                    'country' => array(
                            'value' => 'usa',
                            'description' => /*_wp*/('Represents the country from which the shipment will be originating')
                    ),
                    'region' => array(
                            'value' => 'NY',
                            'description' => /*_wp*/('Represents the state/province from which the shipment will be originating.<br>Required for printing labels'
                            )
                    ),
                    'city' => array(
                            'value' => 'New York',
                            'description' => /*_wp*/('Enter city name<br>Required for printing labels')
                    ),
            )
    ),

    'address' => array(
            'value' => '',
            'title' => /*_wp*/('Sender address'),
            'description' => /*_wp*/('Enter you street address<br>Required for printing labels'),
            'control_type' => waHtmlControl::INPUT,
    ),

    'zip' => array(
            'value' => '',
            'title' => /*_wp*/('Sender zip code'),
            'description' => '',
            'control_type' => waHtmlControl::INPUT,
    ),

    'phone' => array(
            'value' => '',
            'title' => /*_wp*/('Sender phone'),
            'description' => /*_wp*/('Phone of sender. 10 digits required (including area code), with no punctuation.'),
            'control_type' => waHtmlControl::INPUT,
    ),

    'po_zip' => array(
            'value' => '',
            'title' => /*_wp*/('Zip code of post office'),
            'description' => /*_wp*/('Zip code of post office or collection box where item is mailed.<br>
                    Maybe different than Zip code'),
            'control_type' => waHtmlControl::INPUT
    ),
);
//EOF
