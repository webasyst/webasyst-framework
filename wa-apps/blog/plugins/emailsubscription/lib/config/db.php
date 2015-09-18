<?php

return array(
    'blog_emailsubscription_log' => array(
        'id'         => array('bigint', 20, 'null' => 0, 'autoincrement' => 1),
        'post_id'    => array('int', 11, 'null' => 0),
        'email'      => array('varchar', 255, 'null' => 0),
        'name'       => array('varchar', 255, 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        'datetime'   => array('datetime'),
        'status'     => array('smallint', 6, 'null' => 0, 'default' => '0'),
        'error'      => array('text'),
        ':keys'      => array(
            'PRIMARY'    => 'id',
            'post_email' => array('post_id', 'email', 'unique' => 1),
        ),
    ),
    'blog_emailsubscription'     => array(
        'id'         => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'blog_id'    => array('int', 11, 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        'status'     => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'datetime'   => array('datetime', 'null' => 0),
        ':keys'      => array(
            'PRIMARY'      => 'id',
            'blog_contact' => array('blog_id', 'contact_id', 'unique' => 1),
        ),
    ),
);
