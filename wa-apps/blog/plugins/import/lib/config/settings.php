<?php

return array(
    'mode'    => array(
        'title'                  => /*_wp*/('Filter duplicate posts'),
        'description'            => '',
        'value'                  => 'none',
        'options'                => array(
            array(
                'value'       => 'title',
                'title'       => /*_wp*/('By post title'),
                'description' => /*_wp*/('If a blog post with the same name exists in the target WebAsyst blog, the post will not be imported'),
            ),
            array(
                'value'       => 'none',
                'title'       => /*_wp*/('Don’t filter duplicates'),
                'description' => /*_wp*/('All posts will be imported'),
            ),
        ),
        'control_type' => waHtmlControl::RADIOGROUP,
    ),
    'blog'    => array(
        'title'                  => /*_wp*/('Blog'),
        'description'            => /*_wp*/('Target blog where all posts should be imported'),
        'value'                  => 0,
        'control_type' => 'select blogHelper::getAvailable 0',
    ),
    'contact' => array(
        'title'                  => /*_wp*/('Author'),
        'description'            => /*_wp*/("All imported posts will be authored by this user"),
        'value'                  => 0,
        'control_type'     => waHtmlControl::SELECT,
        'options_callback' => array('blogHelper', 'getAuthors'),
    ),
    'replace' => array(
        'title'                  => /*_wp*/('Find and replace'),
        'value'                  => array(),
        'control_type' => 'ReplaceMap',
    ),
);
