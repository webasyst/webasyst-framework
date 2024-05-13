<?php

return array(
    'name' => 'Blog',
    'icon' => 'img/blog.svg',
    'sash_color' => '#f0b100',
    'rights' => true,
    'frontend' => true,
    'auth' => true,
    'themes' => true,
    'plugins' => true,
    'pages' => true,
    'mobile' => true,
    'version' => '2.0.0',
    'critical' => '2.0.0',
    'vendor' => 'webasyst',
    'csrf' => true,
    'my_account' => true,
    'routing_params' => array(
        'blog_url_type' => 1,
    ),
    'ui' => '1.3,2.0',
);
