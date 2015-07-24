<?php

$city = '';
$addresses = wa()->getUser()->get('address');
foreach ($addresses as $address) {
    $city = ifset($address['data']['city'], '');
    if ($city) {
        break;
    }
}

return array(
    'city' => array(
        'title' => /*_w*/('City'),
        'control_type' => waHtmlControl::INPUT,
        'placeholder' => $city
    ),
);