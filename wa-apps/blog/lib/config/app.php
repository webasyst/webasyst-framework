<?php

return array(
    'name' => 'Blog',
    'icon' => array(
        16 => 'img/blog16.png',
        24 => 'img/blog24.png',
        48 => 'img/blog.png',
        96 => 'img/blog96.png',
    ),
    'sash_color' => '#f0b100',
    'rights' => true,
    'frontend' => true,
    'auth' => true,
    'themes' => true,
    'plugins' => true,
    'pages' => true,
    'mobile' => true,
    'version' => '1.3.3',
    'critical' => '1.3.3',
    'vendor' => 'webasyst',
    'csrf' => true,
    'my_account' => true,
    'routing_params' => array(
        'blog_url_type' => 1,
    ),
);
