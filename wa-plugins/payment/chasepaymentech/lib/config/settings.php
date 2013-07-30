<?php
return array(
    'test_mode' => array(
        'value' => '',
        'title' => /*_wp*/('Test mode'),
        'description' => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),

    'hosted_secure_id' => array(
        'value' => '',
        'title' => /*_wp*/('Hosted Payment ID'),
        'description' => /*_wp*/('Unique value identifying the merchant’s Hosted Payment account'),
        'control_type' => waHtmlControl::INPUT
    ),
);

