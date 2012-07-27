<?php

return array(
    'opacity' => array(
        'title' => _wp('Opacity'),
        'description' => _wp('0 for full transparency, 1 for no transparency'),
        'value' => '0.3',
        'control_type' => waHtmlControl::INPUT
    ),
    'text' => array(
        'title' => _wp('Text'),
        'value' => '',
        'control_type' => waHtmlControl::INPUT
    ),
    'text_size' => array(
        'title' => _wp('Text size'),
        'value' => '12',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options' => array(
            array('value' => '8', 'title' => _wp('Small'), 'description' => ''),
            array('value' => '10', 'title' => _wp('Medium'), 'description' => ''),
            array('value' => '12', 'title' => _wp('Large'), 'description' => '')
        )
    ),
    'text_color' => array(
        'title' => _wp('Text color'),
        'description' => _wp('Color value in hex format, e.g. #FFFFFF'),
        'value' => '#FFFFFF',
        'control_type' => waHtmlControl::INPUT
    ),
    'text_align' => array(
        'title' => _wp('Text align'),
        'value' => 'br',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options' => array(
            array('value' => 'tl', 'title' => _wp('Top Left')),
            array('value' => 'tr', 'title' => _wp('Top Right')),
            array('value' => 'bl', 'title' => _wp('Bottom Left')),
            array('value' => 'br', 'title' => _wp('Bottom Right')),
        )
    ),
    'text_orientation' => array(
        'title' => _wp('Text orientation'),
        'value' => 'v',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options' => array(
            array('value' => 'h', 'title' => _wp('Horizontal')),
            array('value' => 'v', 'title' => _wp('Vertical (recommended)')),
        )
    ),
    'image' => array(
        'title' => _wp('Image'),
        'value' => '',
        'control_type' => waHtmlControl::CUSTOM.' '.'photosWatermarkPlugin::getFileControl'
    ),
    'image_align' => array(
            'title' => _wp('Image align'),
            'value' => 'bl',
            'control_type' => waHtmlControl::RADIOGROUP,
            'options' => array(
                array('value' => 'tl', 'title' => _wp('Top Left')),
                array('value' => 'tr', 'title' => _wp('Top Right')),
                array('value' => 'bl', 'title' => _wp('Bottom Left')),
                array('value' => 'br', 'title' => _wp('Bottom Right')),
            )
    )

);