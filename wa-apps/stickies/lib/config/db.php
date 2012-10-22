<?php
return array(
    'stickies_sheet' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 50, 'null' => 0),
        'sort' => array('int', 11, 'null' => 0),
        'background_id' => array('varchar', 10, 'default' => ''),
        'create_datetime' => array('datetime', 'null' => 0),
        'creator_contact_id' => array('int', 11),
        'qty' => array('int', 11, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'stickies_sticky' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'sheet_id' => array('int', 11, 'null' => 0),
        'content' => array('text'),
        'creator_contact_id' => array('int', 11),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime', 'null' => 0),
        'size_width' => array('int', 11, 'null' => 0, 'default' => '0'),
        'size_height' => array('int', 11, 'null' => 0, 'default' => '0'),
        'position_top' => array('int', 11, 'null' => 0, 'default' => '0'),
        'position_left' => array('int', 11, 'null' => 0, 'default' => '0'),
        'color' => array('varchar', 16, 'null' => 0, 'default' => ''),
        'font_size' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'sheet_id' => 'sheet_id',
        ),
    ),
);