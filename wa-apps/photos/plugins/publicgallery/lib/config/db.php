<?php
return array(
    'photos_publicgallery_vote' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'photo_id' => array('int', 11, 'null' => 0),
        'contact_id' => array('int', 11, 'null' => 0),
        'rate' => array('tinyint', 1, 'null' => 0),
        'datetime' => array('datetime', 'null' => 0),
        'ip' => array('int', 11),
        ':keys' => array(
            'PRIMARY' => 'id',
            'photo_id' => 'photo_id',
            'contact_id' => 'contact_id',
        ),
    ),
);