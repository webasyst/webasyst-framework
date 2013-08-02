<?php

return array(
    'test_mode' => array(
        'value'        => false,
        'title'        => /*_wp*/('Test environment'),
        'description'  => /*_wp*/('Enable to run Australia Post module in test environment. Disable when moving to production.'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
    'api_key' => array(
        'value' => '',
        'title' => /*_wp*/('API key'),
        'description' => /*_wp*/('Unique 32 character API key used to authorise inbound requests to the APIs. To obtain an API key, please visit <a href="http://auspost.com.au/devcentre">http://auspost.com.au/devcentre</a> and follow the link to create your Australia Post ID.'),
        'control_type' => waHtmlControl::INPUT
    ),
    'zip' => array(
        'value' => '',
        'title' => /*_wp*/('"From" postcode'),
        'description' => '',
        'control_type' => waHtmlControl::INPUT
    ),
    'length' => array(
        'value' => '',
        'title' => /*_wp*/('Length of parcel box'),
        'description' => /*_wp*/('for domestic shipping'),
        'control_type' => waHtmlControl::INPUT
    ),
    'width' => array(
        'value' => '',
        'title' => /*_wp*/('Width of parcel box'),
        'description' => /*_wp*/('for domestic shipping'),
        'control_type' => waHtmlControl::INPUT
    ),
    'height' => array(
        'value' => '',
        'title' => /*_wp*/('Height of parcel box'),
        'description' => /*_wp*/('for domestic shipping'),
        'control_type' => waHtmlControl::INPUT
    )
);
//EOF
