<?php

return array(
    'customer_type' => array(
        'value' => 0,
        'title' => /*_wp*/('Customer classification'),
        'description' => /*_wp*/('Select "default" to use default UPS values'),
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            '0' => /*_wp*/('default'),
            '01' => 'Wholesale',
            '03' => 'Occasional',
            '04' => 'Retail'
        )
    ),

    'package_type' => array(
        'value' => '02',
        'title' => /*_wp*/('Package type'),
        'description' => '',
        'control_type' => waHtmlControl::SELECT.' upsShipping::getPackageTypes'
    ),

    'pickup_type' => array(
        'value' => '01',
        'title' => /*_wp*/('Pickup type'),
        'description' => '',
        'control_type' => waHtmlControl::SELECT.' upsShipping::getPickupTypes'
    ),

    'access_key' => array(
        'value' => '',
        'title' => /*_wp*/('XML Access Key'),
        'description' => /*_wp*/('Please indicate XML Access Key which can be obtained from UPS'),
        'control_type' => waHtmlControl::INPUT
    ),

    'user_id' => array(
        'value' => '',
        'title' => /*_wp*/('UPS User ID'),
        'description' => /*_wp*/('Your UPS login'),
        'control_type' => waHtmlControl::INPUT
    ),

    'password' => array(
        'value' => '',
        'title' => /*_wp*/('UPS Password'),
        'description' => /*_wp*/('Your UPS accont password'),
        'control_type' => waHtmlControl::INPUT
    ),

    'country' => array(
        'value' => 'usa',
        'title' => /*_wp*/('Origin country'),
        'description' => /*_wp*/('Please select an origin country, from where you will ship'),
        'control_type' => waHtmlControl::SELECT.' waShipping::settingCountrySelect'
    ),

    'city' => array(
        'value' => '',
        'title' => /*_wp*/('Origin city'),
        'description' => /*_wp*/('Please enter your city name'),
        'control_type' => waHtmlControl::INPUT
    ),

    'zip' => array(
        'value' => '',
        'title' => /*_wp*/('Origin postal code (Zip)'),
        'description' => /*_wp*/('Enter your origin location Zip code'),
        'control_type' => waHtmlControl::INPUT
    ),

    'weight_dimension' => array(
        'value' => 'kgs',
        'title' => /*_wp*/('Weight dimension unit'),
        'description' => /*_wp*/('Choose proper to origin country weight dimension unit'),
        'control_type' => waHtmlControl::SELECT,
        'options' => array(
            array('title' => 'kg', 'value' => 'kgs'),
            array('title' => 'lbs', 'value' => 'lbs')
        )
    )
);
