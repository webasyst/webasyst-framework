<?php
return array(
    'rate'     => array(
        'value' => array(
        ),
    ),
    'currency' => array(
        'value' => 'USD',
    ),
    'prompt_address' =>array(
        'value'        => false,
        'title'        => /*_wp*/('Prompt for address'),
        'description'  => /*_wp*/('Request customer to fill in all address fields in case shipping address was not provided yet'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
//EOF