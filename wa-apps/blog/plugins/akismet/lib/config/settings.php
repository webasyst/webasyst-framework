<?php

return  array(
    'api_key'  => array(
        'title'        => /*_wp*/('Akismet API Key'),
        'description'  => array(/*_wp*/('Get an API key for your domain at <a target="_blank" href="%s">Akismet website</a>'),'https://akismet.com/signup/'),
        'value'        => '',
        'control_type'=>waHtmlControl::INPUT,
    ),
    'send_spam' => array(
        'title' => /*_wp*/('Report spam'),
        'label' => /*_wp*/('Send comments marked as spam to Akismet server'),
        'control_type' => waHtmlControl::CHECKBOX,
    ),
);
