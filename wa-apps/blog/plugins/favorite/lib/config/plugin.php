<?php

return array(
    'name' => 'Favorites',//_wp('Favorites');
    'description' => 'Favorite posts',//_wp('Favorite posts');
    'vendor'=>'webasyst',
    'version'=>'1.0.0',
	'img'=>'img/star.png',
	'icons'=>array(
		16 => 'img/star.png',
	),
    'handlers' => array(
        'search_posts_backend'=>'postSearch',
        'backend_sidebar' => 'backendSidebar',
        'prepare_posts_backend' => 'postsPrepareView',
        'post_delete' => 'postDelete',
    ),
);
//EOF
