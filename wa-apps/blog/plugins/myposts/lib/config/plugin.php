<?php

return array(
    'name' => ('My posts'),//_wp('My posts');
    'description' => ('Backend filtering for self-authored posts'),//_wp('Backend filtering for self-authored posts')
    'img' => '/img/myposts.png',
    'vendor'=>'webasyst',
    'version'=>'1.1',
    'handlers' => array(
        'search_posts_backend'=>'postSearch',
        'backend_sidebar' => 'backendSidebar',
    ),
);
