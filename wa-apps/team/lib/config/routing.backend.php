<?php

return array(
    'u/<login>/<tab>/?'  => 'profile/',
    'u/<login>/?'        => 'profile/',
    'id/<id>/<tab>/?'    => 'profile/',
    'id/<id>/?'          => 'profile/',
    'calendar/'          => 'schedule/',
    'calendar/external/' => 'calendar/external',
    'access/'            => 'access/',
    'api-tokens/'        => 'apitokens/',
    'group/<id>/manage/' => 'group/manage',
    'group/<id>/access/' => 'group/access',
    'group/<id>/'        => 'group/',
    'online/'            => 'users/online',
    'settings/'          => 'settings/',
    'plugins/'           => 'plugins/',
    'welcome/'           => 'welcome/',
    'invited/'           => 'users/invited',
    'inactive/'          => 'users/inactive',
    'search/<search>/'   => 'users/search',

    'calendar/external/authorize/' => array(
        'url' => 'calendar/external/authorize/<id>',
        'module' => 'calendarExternal',
        'action' => 'authorize',
        'authorize_end' => '1'
    ),

    '' => 'users/',
);
