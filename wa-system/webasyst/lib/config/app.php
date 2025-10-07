<?php

return array(
    'name'         => 'Webasyst',
    'prefix'       => 'webasyst',
    'version'      => '3.8.4',
    'critical'     => '3.8.4',
    'vendor'       => 'webasyst',
    'csrf'         => true,
    'header_items' => array(
        'settings' => array(
            'icon'   => 'img/wa-settings/settings.svg',
            'name'   => 'Settings',  // _w('Settings')
            'link'   => 'settings',
            'rights' => 'backend'
        ),
    ),
    'ui'           => '2.0'
);
