<?php
/**
 * Shipping method settings
 */
return array(
    'cost'     => array(
        'value'        => 10,
        'title'        => /*_wp*/('Shipping rate'),
        'description'  => /*_wp*/('This fixed shipping rate will be added to the order total'),
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
        'description'  => /*_wp*/('Average order transit time. Estimated delivery date will be calculated automatically and shown to customer'),
        'control_type' => waHtmlControl::SELECT,
        'options'      => array(
            '+1 day'   => /*_wp*/('1 day'),
            '+2 days'  => /*_wp*/('2 days'),
            '+3 days'  => /*_wp*/('3 days'),
            '+7 days'  => /*_wp*/('1 week'),
            '+14 days' => /*_wp*/('2 weeks'),
        ),
    ),
);
