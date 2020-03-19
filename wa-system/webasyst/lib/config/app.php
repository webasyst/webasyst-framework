<?php

return array(
    'name'         => 'Webasyst',
    'prefix'       => 'webasyst',
    'version'      => '1.13.7',
    'critical'     => '1.13.7',
    'vendor'       => 'webasyst',
    'csrf'         => true,
    'header_items' => array(
        'settings' => array(
            'icon'   => array(
                24  => 'img/wa-settings/settings-24.png',
                48  => 'img/wa-settings/settings-48.png', // Main
                96  => 'img/wa-settings/settings-96.png', // Ratio icon
                384 => 'img/wa-settings/settings-384.png', // Site icon
            ),
            'name'   => 'Settings',  // _w('Settings')
            'link'   => 'settings',
            'rights' => 'backend'
        ),
    ),
);