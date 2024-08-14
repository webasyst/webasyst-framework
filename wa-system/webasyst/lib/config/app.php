<?php

return array(
    'name'         => 'Webasyst',
    'prefix'       => 'webasyst',
    'version'      => '3.1.0',
    'critical'     => '3.1.0',
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
    'ui'           => '1.3,2.0'
);
