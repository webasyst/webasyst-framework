<?php
return array(
    'photos_comment' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'left' => array('int', 11),
        'right' => array('int', 11),
        'depth' => array('int', 11, 'null' => 0, 'default' => '0'),
        'parent' => array('int', 11, 'null' => 0, 'default' => '0'),
        'photo_id' => array('int', 11, 'null' => 0),
        'datetime' => array('datetime', 'null' => 0),
        'status' => array('enum', "'approved','deleted'", 'null' => 0, 'default' => 'approved'),
        'text' => array('text', 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 50),
        'email' => array('varchar', 50),
        'site' => array('varchar', 100),
        'auth_provider' => array('varchar', 100),
        'ip' => array('int', 11),
        ':keys' => array(
            'PRIMARY' => 'id',
            'contact_id' => 'contact_id',
            'photo_id' => 'photo_id',
            'parent' => 'parent',
            'status' => 'status',
        ),
    ),
);