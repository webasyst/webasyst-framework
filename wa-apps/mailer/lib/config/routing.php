<?php

return array(

    'unsubscribe/' => array(
        'url' => 'unsubscribe/?',
        'module' => 'frontend',
        'action' => 'unsubscribe',
        'secure' => false,
    ),

    'unsubscribe/<hash>/<email>/' => array(
        'url' => 'unsubscribe/<hash>/<email>/?',
        'module' => 'frontend',
        'action' => 'unsubscribe',
        'secure' => false,
    ),

    'unsubscribe/<hash>/' => array(
        'url' => 'unsubscribe/<hash>/?',
        'module' => 'frontend',
        'action' => 'unsubscribe',
        'secure' => false,
    ),

    'subscribeform/<id>/' => array(
        'url' => 'subscribeform/<id>/?',
        'module' => 'frontend',
        'action' => 'form',
        'secure' => false
    ),

    'subscribe/?' => 'frontend/subscribe/',

    'confirm/<hash>/' => array(
        'url' => 'confirm/<hash>/?',
        'module' => 'frontend',
        'action' => 'confirm',
        'secure' => false
    ),

    'root' => array(
        'url' => 'my/',
        'module' => 'frontend',
        'action' => 'mySubscriptions',
        'secure' => true,
    )
);