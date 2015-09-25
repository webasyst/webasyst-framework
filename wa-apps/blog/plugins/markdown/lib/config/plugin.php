<?php

return array(
    'name' => /*_wp*/('Markdown'),
    'description' => /*_wp*/('Enables support for editing blog posts using the Markdown syntax'),
    'vendor' => 'webasyst',
    'version' => '1.4',
    'img' => 'img/markdown.png',
    'handlers' => array(
        'backend_post_edit' => 'backendPostEdit',
        'backend_assets'  => 'backendAssets',
        'post_save' => 'postSave',
        'post_publish' => 'postPublish',
        'post_shedule' => 'postSchedule'
    ),
);
