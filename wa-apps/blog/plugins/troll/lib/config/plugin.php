<?php

return array(
    'name'        => /*_wp*/('Troll'),
    'description' => /*_wp*/("Mark selected users with a troll face"),
    'vendor'      => 'webasyst',
    'version'     => '1.1',
    'img'         => 'img/troll.png',
    'handlers'    => array(
        'backend_comments'          => 'addControls',
        'backend_post'              => 'addControls',
        'prepare_comments_backend'  => 'prepareView',
        'prepare_comments_frontend' => 'prepareView',
        'frontend_action_post'      => 'postView',
    ),
);
