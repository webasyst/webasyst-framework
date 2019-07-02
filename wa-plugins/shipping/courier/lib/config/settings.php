<?php
return array(
    'rate_zone'           => array(
        'value' => array(
            'country' => '',
            'region'  => '',
            'city'    => '',
        ),
    ),
    'contact_fields'      => array(
        'value' => array(
            'city'   => 'city',
            'street' => 'street',
            'zip'    => 'zip',
        ),
    ),
    'required_fields'     => array(
        'value' => false,
    ),
    'rate_by'             => array(
        'value' => 'weight',
    ),
    'rate'                => array(),
    'currency'            => array(
        'value' => 'USD',
    ),
    'weight_dimension'    => array(
        'value' => 'kg',
    ),
    'map'                 => array(
        'value' => 'google',
    ),
    'delivery_time'       => array(
        'value'        => '+1 day',
        'title'        => /*_wp*/('Delivery time'),
        'control_type' => waHtmlControl::RADIOGROUP,
        'options' => array(
            ''                    => /*_wp*/('Undefined'),
            '+3 hour'             => /*_wp*/('Same day'),
            '+1 day'              => /*_wp*/('Next day'),
            '+1 day, +2 day'      => /*_wp*/('1-2 days'),
            '+2 day, +3 day'      => /*_wp*/('2-3 days'),
            '+1 week'             => /*_wp*/('1 week'),
            'exact_delivery_time' => /*_wp*/('Specified time in hours'),
        ),
    ),
    'exact_delivery_time' => array(
        'value'        => 2,
        'title'        => /*_wp*/('Average delivery time in hours'),
        'description'  => /*_wp*/('Average time in hours required for a courier to deliver an order. It will be added to the order ready time with the account of values in “Delivery intervals” table with extra days off and workdays.'),
        'control_type' => waHtmlControl::INPUT,
    ),
    'customer_interval'   => array(
        'title'          =>/*_wp*/('Preferred delivery time &amp; working schedule'),
        'control_type'   => 'DeliveryIntervalControl',
        'minutes'        => true,
        'extra_holidays' => true,
        'extra_workdays' => true,
    ),
    'extra_holidays'      => array(
        'value'        => '',
        'title'        => /*_wp*/('Extra days off'),
        'description'  => '<i class="icon16 color" style="background-color: #FCC !important;"></i>'.
            '<style>td.courier-ui-datepicker-selected-holiday a { background-color: #FCC !important;}</style>' . _wp('Extra days off'),
        'description_date' => /*_wp*/('Select dates of extra days off and enable at least one checkbox in “Extra days off” column of the “Delivery intervals” table. Delivery intervals are valid for all extra days off.'),
        'control_type' => waHtmlControl::DATETIME,
        'multiple'     => true,
        'params'       => array(
            'date'     => 0,
            'selected' => 'courier-ui-datepicker-selected-holiday',
        ),
    ),
    'extra_workdays'      => array(
        'value'        => '',
        'title'        => /*_wp*/('Extra workdays'),
        'description'  => '<i class="icon16 color" style="background-color: #CFC !important;"></i>'.
            '<style>td.courier-ui-datepicker-selected-workday a { background-color: #CFC !important;}</style>' . _wp('Extra workdays'),
        'description_date' => /*_wp*/('Select dates of extra workdays and enable at least one checkbox in “Extra workdays” column of the “Delivery intervals” table. Delivery intervals are valid for all extra workdays.'),
        'control_type' => waHtmlControl::DATETIME,
        'multiple'     => true,
        'params'       => array(
            'date'     => 0,
            'selected' => 'courier-ui-datepicker-selected-workday',
        ),
    ),
);
