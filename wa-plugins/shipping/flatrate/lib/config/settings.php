<?php
/**
 * Shipping method settings
 */
return array(
    'cost'     => array(
        'value'        => 10,
        'title'        => /*_wp*/('Shipping rate'),
        'description'  => /*_wp*/('Enter shipping rate as a flat rate, as a percent of cart total, or as a sum of both. Examples: <b>20</b>, <b>10%</b>, <b>20+10%</b>'),
        'control_type' => waHtmlControl::INPUT,
    ),

    'currency' => array(
        'value'        => 'USD',
        'title'        => /*_wp*/('Currency'),
        'description'  => /*_wp*/('Currency in which shipping rate is provided'),
        'control_type' => waHtmlControl::SELECT.' waShipping::settingCurrencySelect',
    ),

    'delivery' => array(
        'value'        => '+2 days',
        'title'        => /*_wp*/('Estimated delivery time'),
        'description'  => /*_wp*/('Average order transit time. Estimated delivery date will be calculated automatically and shown to customer.'),
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            '+3 hour'  => /*_wp*/('Same day'),
            '+1 day'   => /*_wp*/('Next day'),
            '+2 days'  => /*_wp*/('2 days'),
            '+3 days'  => /*_wp*/('3 days'),
            '+7 days'  => /*_wp*/('1 week'),
            '+14 days' => /*_wp*/('2 weeks'),
            ''         => /*_wp*/('Undefined')
        ),
    ),
    'prompt_address' =>array(
        'value'        => false,
        'title'        => /*_wp*/('Prompt for address'),
        'description'  => /*_wp*/('Request customer to fill in all address fields in case shipping address was not provided yet'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
