<?php
return array(
    'rate_zone'        => array(
        'value' => array(
            'country' => '',
            'region'  => '',
            'city'    => ''
        ),
    ),
    'contact_fields'=>array(
        'value' => array(
            'city'   => 'city',
            'street' => 'street',
        ),
    ),
    'required_fields' => array(
        'value' => false,
    ),
    'rate_by'          => array(
        'value' => 'weight',
    ),
    'rate'             => array(
    ),
    'currency'         => array(
        'value' => 'USD',
    ),
    'weight_dimension' => array(
        'value' => 'kg'
    ),
    'map' => array(
        'value' => 'google',
    ),
    'delivery_time'    => array(
        'value' => '+1 day',
        'title' => /*_wp*/('Estimated delivery time'),
        'description' => /*_wp*/('Average order transit time. Estimated delivery date will be calculated automatically and shown to customer.<br><br>'),
        'control_type' => waHtmlControl::RADIOGROUP,
        'options' => array(
            '+3 hour' => /*_wp*/('Same day'),
            '+1 day'  => /*_wp*/('Next day'),
            '+2 day, +3 day' => /*_wp*/('2-3 days'),
            '+1 week' => /*_wp*/('1 week'),
            ''        => /*_wp*/('Undefined')
        ),
    ),
    'customer_interval'=>array(
        'title'=>/*_wp*/('Preferred delivery time'),
        'control_type'=>'DeliveryIntervalControl',
        'minutes'=>true,
    ),
);
