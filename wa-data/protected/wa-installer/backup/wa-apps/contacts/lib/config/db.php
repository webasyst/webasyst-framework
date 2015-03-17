<?php
return array(
    'contacts_history' => array(
        'id' => array('bigint', 20, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'type' => array('varchar', 20, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'hash' => array('text', 'null' => 0),
        'contact_id' => array('bigint', 20, 'unsigned' => 1, 'null' => 0),
        'position' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'default' => '0'),
        'accessed' => array('datetime'),
        'cnt' => array('int', 11, 'null' => 0, 'default' => '-1'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'contact_id' => 'contact_id',
            'accessed' => array('contact_id', 'accessed'),
            'hash' => array('contact_id', array('hash', '24')),
            'position' => array('contact_id', 'position'),
        ),
    ),
    'contacts_rights' => array(
        'group_id' => array('int', 11, 'null' => 0),
        'category_id' => array('int', 11, 'null' => 0),
        'writable' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => array('group_id', 'category_id'),
            'list_id' => 'category_id',
        ),
    ),
);