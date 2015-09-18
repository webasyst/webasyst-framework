<?php

return array(
    'blog_tag'      => array(
        'id'    => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name'  => array('varchar', 50, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'name'    => array('name', 'unique' => 1),
        ),
    ),
    'blog_post_tag' => array(
        'post_id' => array('int', 11, 'null' => 0),
        'tag_id'  => array('int', 11, 'null' => 0),
        ':keys'   => array(
            'post_id' => 'post_id',
            'tag_id'  => 'tag_id',
        ),
    ),
);
