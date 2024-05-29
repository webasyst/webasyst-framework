<?php

return array(
    'name'      => 'Contacts',
    'icon'      => array(
        48 => 'img/contacts.png',
        96 => 'img/contacts96.png',
    ),
    'rights'    => true,
    'analytics' => true,
    'version'   => '1.1.7',
    'critical'  => '1.1.0',
    'vendor'    => 'webasyst',
    'system'    => false,
    'csrf'      => true,
    'plugins'   => true,
    'frontend'  => true,
    'routing_params' => array(
        'private' => true,
    ),
);
