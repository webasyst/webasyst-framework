<?php

return array(
    'name'         => /*_wp*/('Comments'),
    'description'  => /*_wp*/('Allows frontend visitors to comment on photos'),
    'img' => 'img/comments.png',
    'vendor' => 'webasyst',
    'version' => '1.0.0',
    'frontend'    => true,
    'handlers' => array(
        'backend_photo'   => 'backendPhoto',
        'backend_sidebar' => 'backendSidebar',
        'backend_assets'  => 'backendAssets',
        'prepare_photos_backend' => 'preparePhotosBackend',
        'prepare_photos_frontend' => 'preparePhotosFrontend',
        'frontend_assets' => 'frontendAssets',
        'frontend_photo'  => 'frontendPhoto',
        'make_stack'      => 'backendMakeStack',
        'unmake_stack'    => 'backendUnmakeStack',
        'photo_delete'    => 'backendPhotoDelete',
        'contacts_links' => 'contactsLinks',
        'contacts_delete' => 'contactsDelete'
    ),
);
