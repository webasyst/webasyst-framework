<?php
return array(
    'checklists_list' => array(
        'id' => array('int', 10, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        'color_class' => array('varchar', 32, 'null' => 0, 'default' => 'c-white'),
        'icon' => array('varchar', 255, 'null' => 0, 'default' => 'notebook'),
        'count' => array('int', 11, 'null' => 0, 'default' => '0'),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'sort' => 'sort',
        ),
    ),
    'checklists_list_items' => array(
        'id' => array('bigint', 20, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'list_id' => array('int', 10, 'unsigned' => 1, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'done' => array('datetime'),
        'contact_id' => array('bigint', 20, 'unsigned' => 1),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'list_id' => 'list_id',
            'sort' => 'sort',
        ),
    ),
);