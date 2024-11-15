<?php

return array(
    'name'       => 'Site', // _w('Site')
    'icon'       => 'img/site512.png',
    'sash_color' => '#49a2e0',
    'frontend'   => true,
    'version'    => '3.0.0', // developer preview
    'critical'   => '3.0.0',
    'vendor'     => 'webasyst',
    'system'     => true,
    'rights'     => true,
    'plugins'    => true,
    'themes'     => true,
    'pages'      => true,
    'auth'       => true,
    'csrf'       => true,
    'my_account' => true,
    'routing_params'   => array(
        'priority_settlement' => true,
    ),
    'ui'         => '2.0,1.3',
);
