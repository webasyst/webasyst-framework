<?php

return array(
    'name'        => /*_wp*/('Categories'),
    'description' => /*_wp*/('Posts filtering by category'),
    'vendor'      => 'webasyst',
    'version'     => '1.2',
    'img'         => 'img/category.png',
    'frontend'    => true,

    'handlers' => array(
        // Event => method name of main plugin class
        'search_posts_frontend'  => 'postSearch',
        'search_posts_backend'   => 'postSearch',
        'post_save'              => 'postUpdate',
        'post_publish'           => 'postUpdate',
        'post_shedule'           => 'postUpdate',
        'post_delete'            => 'postDelete',
        'backend_sidebar'        => 'backendSidebar',
        'backend_post_edit'      => 'backendPostEdit',
        'frontend_action_default'=> 'frontendSidebar',
        'frontend_action_post'   => 'frontendSidebar',
        'frontend_action_page'   => 'frontendSidebar',
    ),
    'custom_settings' => true,
);
