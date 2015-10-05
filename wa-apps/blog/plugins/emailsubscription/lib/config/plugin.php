<?php

return array(
    'name'            => 'Email subscription',
    'description'     => 'Allows backend users to subscribe for blogs by email',
    'img'             => 'img/emailsubscription.png',
    'version'         => '1.1',
    'custom_settings' => true,
    'vendor'          => 'webasyst',
    'handlers'        => array(
        'post_publish'      => 'postPublishAction',
        'cron_action'       => 'cronAction',
        'backend_stream'    => 'blogAction',
        'backend_blog_edit' => 'settingsAction',
        'contacts_delete'   => 'contactsDelete'
    ),
);
