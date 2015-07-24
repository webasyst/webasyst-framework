<?php
return array(

    'login'    => array(
        'value'         => '',
        'title'         => /*_wp*/('Authorize.Net Login ID'),
        'description'   => /*_wp*/('Please input your merchant login ID'),
        'control_type' => waHtmlControl::INPUT,
    ),

    'trans_key'      => array(
        'value'         => '',
        'title'         => /*_wp*/('Transaction Key'),
        'description'   => /*_wp*/('Please input your transaction key (this can be found in your Authorize.Net account panel).<br>This information is stored in crypted way (secure)'),
        'control_type' => waHtmlControl::PASSWORD,
    ),

    'testmode' => array(
        'value'         => '',
        'title'         => /*_wp*/('Test mode'),
        'description'   => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),

);