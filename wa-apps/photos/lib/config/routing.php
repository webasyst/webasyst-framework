<?php

return array(
    0 => array(
        '' => 'frontend/',

        'author/<author>/' => 'frontend/',
        'id/<id>/' => 'frontend/',
        'tag/<tag>/' => 'frontend/',
        'search/<search>/' => 'frontend/',
        '<favorites:favorites>/' => 'frontend/',

        'photo/author/<author>/<url:[^\s]+>/loadPhoto' => 'frontend/loadPhoto',
        'photo/author/<author>/<url:[^\s]+>/loadList' => 'frontend/loadList',
        'photo/author/<author>/<url:[^\s]+>' => 'frontend/photo',

        'photo/id/<id>/<url:[^\s]+>/loadPhoto' => 'frontend/loadPhoto',
        'photo/id/<id>/<url:[^\s]+>/loadList' => 'frontend/loadList',
        'photo/id/<id>/<url:[^\s]+>' => 'frontend/photo',

        'photo/tag/<tag>/<url:[^\s]+>/loadPhoto' => 'frontend/loadPhoto',
        'photo/tag/<tag>/<url:[^\s]+>/loadList' => 'frontend/loadList',
        'photo/tag/<tag>/<url:[^\s]+>' => 'frontend/photo',

        'photo/search/<search>/<url:[^\s]+>/loadPhoto' => 'frontend/loadPhoto',
        'photo/search/<search>/<url:[^\s]+>/loadList' => 'frontend/loadList',
        'photo/search/<search>/<url:[^\s]+>' => 'frontend/photo',

        'photo/<favorites:favorites>/<url:[^\s]+>/loadPhoto' => 'frontend/loadPhoto',
        'photo/<favorites:favorites>/<url:[^\s]+>/loadList' => 'frontend/loadList',
        'photo/<favorites:favorites>/<url:[^\s]+>' => 'frontend/photo',

        'photo/<url>/loadPhoto' => 'frontend/loadPhoto',
        'photo/<url>/loadList' => 'frontend/loadList',
        'photo/<url>/' => 'frontend/photo',

        'my/' => array(
            'module' => 'frontend',
            'action' => 'my',
            'secure' => true,
        ),
        'login/' => 'login/',
        'forgotpassword/' => 'forgotpassword/',
        'signup/' => 'signup/',
        'data/regions/' => 'frontend/regions',
        'logout/' => 'frontend/logout',
        '<url>/loadPhoto' => 'frontend/loadPhoto',
        '<url>/' => 'frontend/album',
    ),
    1 => array(
        '' => 'frontend/',

        'author/<author>/<url:[^\s]+>/loadPhoto' => 'frontend/loadPhoto',
        'author/<author>/<url:[^\s]+>/loadList' => 'frontend/loadList',
        'author/<author>/<url:[^\s]+>' => 'frontend/photo',
        'author/<author>/' => 'frontend/',

        'id/<id>/<url:[^\s]+>/loadPhoto' => 'frontend/loadPhoto',
        'id/<id>/<url:[^\s]+>/loadList' => 'frontend/loadList',
        'id/<id>/<url:[^\s]+>' => 'frontend/photo',
        'id/<id>/' => 'frontend/',

        'tag/<tag>/<url:[^\s]+>/loadPhoto' => 'frontend/loadPhoto',
        'tag/<tag>/<url:[^\s]+>/loadList' => 'frontend/loadList',
        'tag/<tag>/<url:[^\s]+>' => 'frontend/photo',
        'tag/<tag>/' => 'frontend/',

        'search/<search>/<url:[^\s]+>/loadPhoto' => 'frontend/loadPhoto',
        'search/<search>/<url:[^\s]+>/loadList' => 'frontend/loadList',
        'search/<search>/<url:[^\s]+>' => 'frontend/photo',
        'search/<search>/' => 'frontend/',

        '<favorites:favorites>/<url:[^\s]+>/loadPhoto' => 'frontend/loadPhoto',
        '<favorites:favorites>/<url:[^\s]+>/loadList' => 'frontend/loadList',
        '<favorites:favorites>/<url:[^\s]+>' => 'frontend/photo',
        '<favorites:favorites>/' => 'frontend/',

        'album/<url>/loadPhoto' => 'frontend/loadPhoto',
        'album/<url>/' => 'frontend/album',

        'my/' => array(
            'module' => 'frontend',
            'action' => 'my',
            'secure' => true,
        ),
        'login/' => 'login/',
        'forgotpassword/' => 'forgotpassword/',
        'signup/' => 'signup/',
        'data/regions/' => 'frontend/regions',
        'logout/' => 'frontend/logout',

        '<url>/loadList' => 'frontend/loadList',
        '<url>/loadPhoto' => 'frontend/loadPhoto',
        '<url>' => 'frontend/photo',
    )
);