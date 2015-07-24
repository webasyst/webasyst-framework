<?php

$city = '';
$addresses = wa()->getUser()->get('address:city');
foreach ($addresses as $address) {
    if (!empty($address['value'])) {
        $city = $address['value'];
        break;
    }
}

return array(
    'city' => array(
        'title' => /*_wp*/('City'),
        'control_type' => waHtmlControl::INPUT,
        'placeholder' => $city
    ),
    'unit' => array(
        'title' => /*_wp*/('Format'),
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => array(
            'C' => /*_wp*/('Celsius'),
            'F' => /*_wp*/('Fahrenheit'),
        ),
        'value' => 'C',
    ),
    'source' => array(
        'title' => /*_wp*/('Weather source'),
        'control_type' => waHtmlControl::CUSTOM.' weatherWidget::customFieldLabel',
        'value' => 'OpenWeatherMap.org'
    ),
);