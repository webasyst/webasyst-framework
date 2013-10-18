<?php

return array(
    'name'          => /*_wp*/('Import posts'),
    'description'   => /*_wp*/('Transfer posts from popular blog platforms such as Wordpress and LiveJournal'),
    'img'           => 'img/import.png',
    'vendor'        => 'webasyst',
    'version'       => '1.0.0',
    'rights'        => false,
    'handlers'      => array(),
    'settings'      => array(
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
            'settings_html_function' => 'radiogroup',
        ),
        'blog'    => array(
            'title'                  => /*_wp*/('Blog'),
            'description'            => /*_wp*/('Target blog where all posts should be imported'),
            'value'                  => 0,
            'settings_html_function' => 'select blogHelper::getAvailable 0',
        ),
        'contact' => array(
            'title'                  => /*_wp*/('Author'),
            'description'            => /*_wp*/("All imported posts will be authored by this user"),
            'value'                  => 0,
            'settings_html_function' => 'select blogHelper::getAuthors',
        ),
        'replace' => array(
            'title'                  => /*_wp*/('Find and replace'),
            'value'                  => array(),
            'settings_html_function' => 'ReplaceMap',
        ),
    ),
    'blog_settings' => true,
);

