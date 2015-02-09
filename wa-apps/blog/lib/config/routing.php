<?php
$year = '((19|20)[\d]{2})';
$month = '(0[1-9]|1[0-2])';
$day = '(0[1-9]|[1-2]\d|3[0-1]|\d)';
$blog_prefix = 'blog';
/**
 * key is blog_url_type
 */

return array(
    0 => array(
//timeline
        "<year:{$year}>/<month:{$month}>/<day:{$day}>/"            => 'frontend',
        "<year:{$year}>/<month:{$month}>/"                         => 'frontend',
        "<year:{$year}>/"                                          => 'frontend',
//timeline per blog
        "<blog_url>/<year:{$year}>/<month:{$month}>/<day:{$day}>/" => 'frontend',
        "<blog_url>/<year:{$year}>/<month:{$month}>/"              => 'frontend',
        "<blog_url>/<year:{$year}>/"                               => 'frontend',

        '<blog_url>/<post_url>/comment/'                           => 'frontend/comment',
        '<post_url>/comment/'                                      => 'frontend/comment',
        'my/' => array(
            'module' => 'frontend',
            'action' => 'my',
            'secure' => true,
        ),
        'login/'                                                   => 'login',
        'forgotpassword/'                                          => 'forgotpassword',
        'signup/'                                                  => 'signup',
        'data/regions/'                                            => 'frontend/regions',
        'logout/'                                                  => 'frontend/logout',
        'rss/'                                                     => 'frontend/rss',
        'author/<contact_id>/'                                     => 'frontend',
        '<blog_url>/postpreview/'                                  => 'frontend/previewTemplate',
        '<blog_url>/<post_url>/'                                   => 'frontend/post',
        '<blog_url>/'                                              => 'frontend',
        ''                                                         => 'frontend',
    ),

    1 => array(
        '<post_url>/comment/'                           => 'frontend/comment',
        'logout/'                                       => 'frontend/logout',
        'my/' => array(
            'module' => 'frontend',
            'action' => 'my',
            'secure' => true,
        ),
        'login/'                                        => 'login',
        'forgotpassword/'                               => 'forgotpassword',
        'signup/'                                       => 'signup',
        'data/regions/'                                 => 'frontend/regions',
        'rss/'                                          => 'frontend/rss',
        'author/<contact_id>/'                          => 'frontend',

//timeline
        "<year:{$year}>/<month:{$month}>/<day:{$day}>/" => 'frontend',
        "<year:{$year}>/<month:{$month}>/"              => 'frontend',
        "<year:{$year}>/"                               => 'frontend',
        'postpreview/'                                  => 'frontend/previewTemplate',
        '<post_url>/'                                   => 'frontend/post',
        ''                                              => 'frontend',
    ),
    2 => array(
        '<post_url>/comment/'                                                     => 'frontend/comment',
        'logout/'                                                                 => 'frontend/logout',
        'my/' => array(
            'module' => 'frontend',
            'action' => 'my',
            'secure' => true,
        ),
        'login/'                                                                  => 'login',
        'forgotpassword/'                                                         => 'forgotpassword',
        'signup/'                                                                 => 'signup',
        'data/regions/'                                                           => 'frontend/regions',
        "{$blog_prefix}/rss/"                                                     => 'frontend/rss',
        'rss/'                                                                    => 'frontend/rss',
        'author/<contact_id>/'                                                    => 'frontend',

//timeline per blog
        "{$blog_prefix}/<blog_url>/<year:{$year}>/<month:{$month}>/<day:{$day}>/" => 'frontend',
        "{$blog_prefix}/<blog_url>/<year:{$year}>/<month:{$month}>/"              => 'frontend',
        "{$blog_prefix}/<blog_url>/<year:{$year}>/"                               => 'frontend',

//timeline
        "{$blog_prefix}/<year:{$year}>/<month:{$month}>/<day:{$day}>/"            => 'frontend',
        "{$blog_prefix}/<year:{$year}>/<month:{$month}>/"                         => 'frontend',
        "{$blog_prefix}/<year:{$year}>/"                                          => 'frontend',

        "{$blog_prefix}/<blog_url>/"                                              => 'frontend',
        'postpreview/'                                                            => 'frontend/previewTemplate',
        '<post_url>/'                                                             => 'frontend/post',
        ''                                                                        => 'frontend',
    ),
);