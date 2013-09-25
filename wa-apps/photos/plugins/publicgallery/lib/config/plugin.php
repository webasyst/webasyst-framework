<?php

return array(
    'name' => /*_wp*/('Public Gallery'),
    'description' => /*_wp*/('Enables the ability to upload and vote for photos in the app frontend'),
    'img' => 'img/publicgallery.png',
    'version' => '1.0.0',
    'vendor' => 'webasyst',
    'rights' => false,
    'frontend' => true,
    'handlers' => array(
        'backend_assets'  => 'backendAssets',
        'backend_sidebar' => 'backendSidebar',
        'backend_photo' => 'backendPhoto',
        'frontend_photo' => 'frontendPhoto',
        'frontend_sidebar' => 'frontendSidebar',
        'frontend_assets' => 'frontendAssets',
        'extra_prepare_collection' => 'extraPrepareCollection',
        'prepare_photos_backend' => 'preparePhotosBackend',
        'prepare_photos_frontend' => 'preparePhotosFrontend',
        'backend_photo_toolbar' => 'backendPhotoToolbar',
        'collection' => 'prepareCollection',
        'front_controller' => 'fronteController',
        'before_save_field' => 'beforeSaveField',
        'search_frontend_link' => 'searchFrontendLink'
    ),
);