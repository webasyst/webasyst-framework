<?php

return array(
    'name'         => /*_wp*/('Akismet'),
    'description'  => /*_wp*/('Anti-spam comment fitering powered by Akismet.com'),
    'vendor'       => 'webasyst',
    'version'      => '1.1',
    'img'          => 'img/akismet.png',
    'rights'   => false,
    'handlers' => array(
        'comment_validate' => 'commentValidate',
        'backend_post'     => 'addControls',
        'backend_comments' => 'addControls',
    ),
);
