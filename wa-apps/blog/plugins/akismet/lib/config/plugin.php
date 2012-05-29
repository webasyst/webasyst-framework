<?php

return array(
    'name'         => /*_wp*/('Akismet'),
    'description'  => /*_wp*/('Anti-spam comment fitering powered by Akismet.com'),
    'vendor'       => 'webasyst',
    'version'      => '1.0.0',
    'img'          => 'img/akismet.png',
    'settings'     => array(
        'api_key'  => array(
            'title'        => /*_wp*/('Akismet API Key'),
            'description'  => array(/*_wp*/('Get an API key for your domain at <a target="_blank" href="%s">Akismet website</a>'),'https://akismet.com/signup/'),
            'value'        => '',
            'settings_html_function'=>'input',
        ),
        'send_spam' => array(
            'title' => /*_wp*/('Report spam'),
            'label' => /*_wp*/('Send comments marked as spam to Akismet server'),
            'settings_html_function' => 'checkbox',
        ),
    ),
    'rights'   => false,
    'handlers' => array(
        'comment_validate' => 'commentValidate',
        'backend_post' => 'addControls',
        'backend_comments' => 'addControls',
    ),
);