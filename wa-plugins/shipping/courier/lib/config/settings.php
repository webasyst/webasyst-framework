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
        'value' => '',
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
            array(
                'value' => '',
                'title' => /*_wp*/('Undefined'),
                'description' => /*_wp*/('Recommended for multi-step checkout'),
            ),
            array(
                'value' => '+3 hour',
                'title' => /*_wp*/('Same workday, with the order ready time taken into account'),
                'description' => /*_wp*/('Recommended for multi-step checkout'),
            ),
            array(
                'value' => '+1 day',
                'title' => /*_wp*/('Add 1 day to the order ready time'),
            ),
            array(
                'value' => '+1 day, +2 day',
                'title' => /*_wp*/('Add 1–2 days to the order ready time'),
                'description' => /*_wp*/('Recommended for multi-step checkout'),
            ),
            array(
                'value' => '+2 day, +3 day',
                'title' => /*_wp*/('Add 2–3 days to the order ready time'),
                'description' => /*_wp*/('Recommended for multi-step checkout'),
            ),
            array(
                'value' => '+1 week',
                'title' => /*_wp*/('Add 7 days to the order ready time'),
            ),
            array(
                'value' => 'exact_delivery_time',
                'title' => /*_wp*/('Add specified time in hours to the order ready time'),
                'description' => /*_wp*/('Recommended for in-cart checkout'),
            ),
        ),
    ),
    'exact_delivery_time' => array(
        'value'        => 2,
        'title'        => /*_wp*/('Average delivery time in hours'),
        'description'  => /*_wp*/('Average time in hours required for a courier to deliver an order. It will be added to the order ready time with the account of values in “Delivery intervals” table with extra days off and workdays.'),
        'control_type' => waHtmlControl::INPUT,
    ),
    'customer_interval'   => array(
        'title'          =>/*_wp*/('Preferred delivery time & working schedule'),
        'control_type'   => 'DeliveryIntervalControl',
        'minutes'        => true,
        'extra_holidays' => true,
        'extra_workdays' => true,
    ),
    /**
     * Столбцы "Доп. выходной" и "Доп. рабочий день" в таблице "Интервалы доставки" нужны для того,
     * чтобы можно было настраивать в одной таблице и график работы и интервалы доставки с учетом
     * дополнительных рабочих и выходных дней. Список интервалов единый, но у каждого интервала
     * есть настройки по дням неделям и по дополнительным рабочим и выходным дням.
     */
    'extra_holidays'      => array(
        'value'        => '',
        'title'        => /*_wp*/('Extra days off'),
        'description'  => '<i class="icon16 color" style="background-color: #FCC !important;"></i>'.
            '<style>td.courier-ui-datepicker-selected-holiday a { background-color: #FCC !important;}</style>' . _wp('Extra days off'),
        'description_date' => /*_wp*/('Select dates of extra days off and enable at least one checkbox in “Extra days off” column of the “Delivery intervals” table. Delivery intervals are valid for all extra days off.<br>Extra days off are taken into account only if “Specified time in hours” option is selected for “Delivery time” setting.'),
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
        'description_date' => /*_wp*/('Select dates of extra workdays and enable at least one checkbox in “Extra workdays” column of the “Delivery intervals” table. Delivery intervals are valid for all extra workdays.<br>Extra workdays are taken into account only if “Specified time in hours” option is selected for “Delivery time” setting.'),
        'control_type' => waHtmlControl::DATETIME,
        'multiple'     => true,
        'params'       => array(
            'date'     => 0,
            'selected' => 'courier-ui-datepicker-selected-workday',
        ),
    ),
);
