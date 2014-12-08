<?php

return array(
    'assign_tag' => array(
        'title' => /*_wp*/('Assign tags on upload'),
        'description' => /*_wp*/('Automatically assign listed tags for all photos uploaded in the frontend'),
        'value' => '',
        'control_type' => waHtmlControl::INPUT
    ),
    'need_moderation' => array(
        'title' => /*_wp*/('Moderation'),
        'description' => /*_wp*/('Define if photos uploaded in the frontend needs to be moderated in the backend or not. In case moderation is disabled, all photos will automatically appear in the frontend after upload'),
        'value' => '1',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options' => array(
            array('value' => '1', 'title' => /*_wp*/('Yes')),
            array('value' => '0', 'title' => /*_wp*/('No'))
        ),
    ),
    'self_vote' => array(
        'title' => /*_wp*/('Self photo voting'),
        'description' => /*_wp*/('Author can vote for self uploaded photos'),
        'value' => 1,
        'control_type' => waHtmlControl::CHECKBOX
    ),
    'min_size' => array(
        'title' => /*_wp*/("Minimal photo size (px)"),
        'description' => /*_wp*/("Limits the minimum width and height for photos uploaded in the frontend (in pixels)"),
        'value' => '0',
        'control_type' => waHtmlControl::INPUT
    ),
    'max_size' => array(
        'title' => /*_wp*/("Maximum photo size (px)"),
        'description' => /*_wp*/("Limits the maximum width and height for photos uploaded in the frontend (in pixels)"),
        'value' => '5000',
        'control_type' => waHtmlControl::INPUT
    ),
);