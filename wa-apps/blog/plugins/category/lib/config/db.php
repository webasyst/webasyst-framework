<?php

return array(
    'blog_category'      => array(
        'id'    => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name'  => array('varchar', 255, 'null' => 0),
        'url'   => array('varchar', 255, 'null' => 0),
        'icon'  => array('varchar', 20, 'null' => 0),
        'qty'   => array('int', 11, 'null' => 0, 'default' => '0'),
        'sort'  => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'url'     => array('url', 'unique' => 1),
            'sort'    => 'sort',
        ),
    ),
    'blog_post_category' => array(
        'post_id'     => array('int', 11, 'null' => 0),
        'category_id' => array('int', 11, 'null' => 0),
        ':keys'       => array(
            'PRIMARY' => array('post_id', 'category_id'),
        ),
    ),
);
