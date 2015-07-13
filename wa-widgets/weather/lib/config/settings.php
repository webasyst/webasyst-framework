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
        'title' => 'City',
        'control_type' => waHtmlControl::INPUT,
        'placeholder' => $city
    ),
    'source' => array(
        'title' => 'Weather source',
        'control_type' => waHtmlControl::CUSTOM.' weatherWidget::customFieldLabel',
        'value' => 'OpenWeatherMap.org'
    ),
);