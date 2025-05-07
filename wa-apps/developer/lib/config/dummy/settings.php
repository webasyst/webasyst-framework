<?php
return [
    /*
    'input' => [
        'value'        => '',
        'title'        => 'Input',
        'control_type' => waHtmlControl::INPUT,
        'description'  => 'Description for long text.',
        'class'        => 'long',
        'style'        => 'font-weight: bold;',
        'maxlength'    => 255,
        'placeholder'  => 'Enter text..',
        'required'     => true,
    ],
    'input_range' => [
        'value'        => 0,
        'title'        => 'Input range',
        'control_type' => waHtmlControl::INPUT,
        'type'         => 'range',
        'min'          => 0,
        'max'          => 100,
        'step'         => 10,
    ],
    'interval' => [
        'value'         => ['from' => 1, 'to' => 10],
        'title'         => 'Interval',
        'control_type'  => waHtmlControl::INTERVAL,
        'control_title' => [
            'from' => ['str_from' => 'from '],
            'to'   => ['str_to' => ' to '],
        ],
    ],
    'password' => [
        'value'        => '',
        'title'        => 'Password',
        'control_type' => waHtmlControl::PASSWORD,
    ],
    'hidden' => [
        'value'        => null,
        'title'        => 'Hidden',
        'control_type' => waHtmlControl::HIDDEN,
    ],
    'textarea' => [
        'value'        => '',
        'title'        => 'Textarea',
        'control_type' => waHtmlControl::TEXTAREA,
        'cols'         => 20,
        'rows'         => 15,
        'wrap'         => true,
    ],
    'textarea_wysiwyg' => [
        'value'        => '',
        'title'        => 'WYSIWYG textarea',
        'control_type' => waHtmlControl::TEXTAREA,
        'wysiwyg'      => ['readOnly' => false],
    ],
    'file' => [
        'value'        => null,
        'title'        => 'File',
        'control_type' => waHtmlControl::FILE,
    ],
    'file_image' => [
        'value'        => 'photo.jpg',
        'title'        => 'Image file',
        'img_path'     => 'plugins/'.$this->id.'/',
        'control_type' => waHtmlControl::FILE,
    ],
    'checkbox' => [
        'value'        => 1,
        'title'        => 'Checkbox',
        'control_type' => waHtmlControl::CHECKBOX,
        'checked'      => true,
    ],
    'groupbox' => [
        'value'        => [],
        'title'        => 'Groupbox',
        'control_type' => waHtmlControl::GROUPBOX,
        'options'      => [
            ['value' => 1, 'title' => 'Checkbox 1'],
            ['value' => 2, 'title' => 'Checkbox 2'],
        ],
        'class'        => 'valign-top',
        'options_wrapper' => ['control_separator' => '</div><div class="value">'],
    ],
    'groupbox_callback' => [
        'value'        => [],
        'title'        => 'Callble groupbox',
        'control_type' => waHtmlControl::GROUPBOX,
        'options_callback' => [$this, 'getGroupboxOptions'],
    ],
    'radiogroup' => [
        'value'        => 1,
        'title'        => 'Radiogroup',
        'control_type' => waHtmlControl::RADIOGROUP,
        'options'      => ['Radiogroup 1', 'Radiogroup 2'],
    ],
    'select' => [
        'value'        => 0,
        'title'        => 'Select',
        'control_type' => waHtmlControl::SELECT,
        'options'      => [0 => ' ', 1 => 'Value 1', 2 => 'Value 2'],
    ],
    'datetime' => [
        'value'        => null,
        'title'        => 'Datetime',
        'control_type' => waHtmlControl::DATETIME,
        'date'         => null,
        'multiple'     => false,
        'intervals'    => [],
    ],
    'contact' => [
        'value'        => 0,
        'title'        => 'Contact',
        'control_type' => waHtmlControl::CONTACT,
    ],
    'contactfield' => [
        'value'        => null,
        'title'        => 'Contact field',
        'control_type' => waHtmlControl::CONTACTFIELD,
    ],
    'help' => [
        'value'        => '',
        'title'        => 'Help',
        'control_type' => waHtmlControl::HELP,
    ],
    'title' => [
        'value'        => '',
        'title'        => 'Title',
        'control_type' => waHtmlControl::TITLE,
    ],
    */
];
