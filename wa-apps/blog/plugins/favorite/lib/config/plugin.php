<?php

return array(
    'name'        => /*_wp*/('Favorites'),
    'description' => /*_wp*/('Backend favorite posts filtering'),
    'vendor'      => 'webasyst',
    'version'     => '1.1',
    'img'         => 'img/star.png',
    'icons'       => array(
        16 => 'img/star.png',
    ),
    'handlers'    => array(
        'search_posts_backend'  => 'postSearch',
        'backend_sidebar'       => 'backendSidebar',
        'prepare_posts_backend' => 'postsPrepareView',
        'post_delete'           => 'postDelete',
    ),
);
