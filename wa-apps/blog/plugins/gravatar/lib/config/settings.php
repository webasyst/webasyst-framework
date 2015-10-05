<?php

return array(
    'default' => array(
        'title'                  =>/*_wp*/('Default image type'),
        'description'            =>/*_wp*/("When you include a default image, Gravatar will automatically serve up that image if there is no image associated with the requested email hash."),
        'value'                  => 'custom',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'                => array(
            array(
                'value'       => 'custom',
                'title'       =>/*_wp*/("Use default Blog app userpic"),
                'description' => '',
            ),
            array(
                'value'       => 'mm',
                'title'       =>/*_wp*/("(mystery-man) a simple, cartoon-style silhouetted outline of a person (does not vary by email hash)"),
                'description' => '',
            ),
            array(
                'value'       => 'identicon',
                'title'       =>/*_wp*/("a geometric pattern based on an email hash"),
                'description' => '',
            ),
            array(
                'value'       => 'monsterid',
                'title'       =>/*_wp*/("a generated 'monster' with different colors, faces, etc"),
                'description' => '',
            ),
            array(
                'value'       => 'wavatar',
                'title'       =>/*_wp*/("generated faces with differing features and backgrounds"),
                'description' => '',
            ),
            array(
                'value'       => 'retro',
                'title'       =>/*_wp*/("awesome generated, 8-bit arcade-style pixelated faces"),
                'description' => '',
            ),
        ),
    ),
);
