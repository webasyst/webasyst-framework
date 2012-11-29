<?php
return array(
    'dictionaries' => array(
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
    'dictionaries_items' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
	'dictionary_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'name' => array('varchar', 100, 'null' => 0),
        'value' => array('varchar', 255, 'null' => 0),
        'desc' => array('varchar', 1024, 'null' => 0),
        'visible' => array('tinyint', 4, 'null' => 0),
        'sort' => array('DATETIME', 'null' => 1, 'default' => 'NULL'),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'sort' => 'sort',
        ),
    ),
);
