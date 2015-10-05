<?php

return array(
    'blog_favorite' => array(
        'contact_id' => array('int', 11, 'null' => 0),
        'post_id'    => array('int', 11, 'null' => 0),
        'datetime'   => array('datetime', 'null' => 0),
        ':keys'      => array(
            'PRIMARY' => array('contact_id', 'post_id'),
        ),
    ),
);
