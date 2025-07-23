<?php

return array(
    'name'       => 'Site', // _w('Site')
    'icon'       => 'img/site.svg',
    'sash_color' => '#49a2e0',
    'frontend'   => true,
    'version'    => '3.2.4',
    'critical'   => '3.2.4',
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