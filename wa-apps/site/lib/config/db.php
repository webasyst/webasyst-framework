<?php
return array(
    'site_block' => array(
        'id' => array('varchar', 64, 'null' => 0),
        'content' => array('text', 'null' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        'description' => array('text', 'null' => 0),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
        ':options' => array('engine' => 'MyISAM')
    ),
    'site_domain' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        'title' => array('varchar', 128, 'null' => 0, 'default' => ''),
        'style' => array('varchar', 255, 'null' => 0, 'default' => ''),
        ':keys' => array(
            'PRIMARY' => 'id',
            'name' => array('name', 'unique' => 1),
        ),
    ),
    'site_page' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'domain_id' => array('int', 11, 'null' => 0),
        'route' => array('varchar', 64, 'null' => 0, 'default' => ''),
        'name' => array('varchar', 255, 'null' => 0),
        'title' => array('varchar', 255, 'null' => 0, 'default' => ''),
        'url' => array('varchar', 255),
        'full_url' => array('varchar', 255),
        'content' => array('longtext', 'null' => 0),
        'create_datetime' => array('datetime', 'null' => 0),
        'update_datetime' => array('datetime', 'null' => 0),
        'create_contact_id' => array('int', 11, 'null' => 0),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        'status' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'parent_id' => array('int', 11),
        ':keys' => array(
            'PRIMARY' => 'id',
            'url' => array('domain_id', 'route', 'full_url'),
            'parent_id' => 'parent_id',
        ),
    ),
    'site_page_params' => array(
        'page_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'value' => array('text', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('page_id', 'name'),
        ),
    ),
);