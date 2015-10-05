<?php

return array(
    'name' => /*_wp*/('Gravatar'),
    'description' => /*_wp*/('Globally Recognized Avatars. All commentators userpics are replaced with gravatar.com’s userpic (frontend only)'),
    'vendor'=>'webasyst',
    'version'=>'1.1',
    'img' => 'img/gravatar.png',
    'handlers' => array(
        'prepare_comments_frontend' => 'commentsPrepare',
        'prepare_comments_backend' => 'commentsPrepare',
    ),
);
