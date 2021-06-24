<?php

return array(
    'name'         => 'Webasyst',
    'prefix'       => 'webasyst',
    'version'      => '2.2.0',
    'critical'     => '2.2.0',
    'vendor'       => 'webasyst',
    'csrf'         => true,
    'header_items' => array(
        'settings' => array(
            //'icon'   => 'img/wa-settings/settings.svg',
            'icon'   => 'img/wa-settings/settings-384.png',
            'name'   => 'Settings',  // _w('Settings')
            'link'   => 'settings',
            'rights' => 'backend'
        ),
    ),
    'ui'           => '1.3,2.0'
);