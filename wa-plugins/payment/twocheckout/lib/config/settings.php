<?php
return array(
    'sid'    => array(
        'value'        => '',
        'title'        => /*_wp*/('2checkout merchant ID'),
        'description'  => /*_wp*/('Please input your 2checkout login ID'),
        'control_type' => waHtmlControl::INPUT,
    ),

    'secret' => array(
        'value'        => '',
        'title'        => /*_wp*/('Secret word'),
        'description'  => /*_wp*/('Secret word is a text string appended to the payment credentials, which are sent to merchant together with the payment notification.<br />It is used to enhance the security of the notification identification and should not be disclosed to third parties.'),
        'control_type' => waHtmlControl::PASSWORD,
    ),

    'demo'   => array(
        'value'        => false,
        'title'        => /*_wp*/('Sandbox mode'),
        'description'  => '',
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
