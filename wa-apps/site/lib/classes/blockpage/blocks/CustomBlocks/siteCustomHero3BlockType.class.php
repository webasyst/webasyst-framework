<?php

class siteCustomHero3BlockType extends siteBlockType {
    /** @var array Элементы основного блока */
    public array $elements = [
        'main'    => 'site-block-columns',
        'wrapper' => 'site-block-columns-wrapper',
    ];

    /** @var array Элементы колонок */
    public array $column_elements = [
        'main'    => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
    ];

    public string $image_url;

    public function __construct(array $options = []) {
        $this->image_url = wa()->getAppStaticUrl('site') . 'img/blocks/hero/';

        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = 2;
        }
        $options['type'] = 'site.CustomHero3';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        // Добавляем последовательности в основной блок
        $hseq->addChild($this->getBigColumn());
        $hseq->addChild($this->getSmallColumn());

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['main']    => [
                    'padding-top'    => "p-t-16",
                    'padding-bottom' => "p-b-16",
                ],
                $this->elements['wrapper'] => [
                    'padding-top'    => "p-t-12",
                    'padding-bottom' => "p-b-12",
                    'padding-left'   => "p-l-12",
                    'padding-right'  => "p-r-12",
                    'flex-align'     => "y-c",
                    'max-width'      => "cnt",
                ],
            ],
            'wrapper_props' => [
                'justify-align' => 'y-j-cnt',
            ],
            'elements'      => $this->elements,
        ];

        return $result;
    }

    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars = []) {
        return parent::render($data, $is_backend, $tmpl_vars + [
                'children' => array_reduce($data->getRenderedChildren($is_backend), 'array_merge', []),
            ]);
    }

    public function getRawBlockSettingsFormConfig() {
        return [
                'type_name'    => _w('Block'),
                'sections'     => [
                    [
                        'type' => 'ColumnsGroup',
                        'name' => _w('Columns'),
                    ],
                    [
                        'type' => 'RowsAlignGroup',
                        'name' => _w('Columns alignment'),
                    ],
                    [
                        'type' => 'RowsWrapGroup',
                        'name' => _w('Wrap line'),
                    ],
                    [
                        'type' => 'TabsWrapperGroup',
                        'name' => _w('Tabs'),
                    ],                    
                    [   'type' => 'CommonLinkGroup',
                        'name' => _w('Link or action'),
                        'is_hidden' => true,
                    ],
                    [
                        'type' => 'MaxWidthToggleGroup',
                        'name' => _w('Max width'),
                    ],
                    [
                        'type' => 'BackgroundColorGroup',
                        'name' => _w('Background'),
                    ],
                    [
                        'type' => 'PaddingGroup',
                        'name' => _w('Padding'),
                    ],
                    [
                        'type' => 'MarginGroup',
                        'name' => _w('Margin'),
                    ],
                    [
                        'type' => 'BorderGroup',
                        'name' => _w('Border'),
                    ],
                    [
                        'type' => 'BorderRadiusGroup',
                        'name' => _w('Angle'),
                    ],
                    [
                        'type' => 'ShadowsGroup',
                        'name' => _w('Shadows'),
                    ],
                    [
                        'type' => 'IdGroup',
                        'name' => _w('Identifier (ID)'),
                    ],
                ],
                'elements'     => $this->elements,
                'semi_headers' => [
                    'main'    => _w('Whole block'),
                    'wrapper' => _w('Container'),
                ],
            ] + parent::getRawBlockSettingsFormConfig();
    }

    public function getBigColumn(): siteBlockData {
        $vseq = $this->createSequence();

        // Заголовок
        $heading = $this->createHeading('<font class="tx-wh">Aliquam erat sapien, enim&nbsp;vestibulum nec accumsan</font>', [
            'align' => 't-l',
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #3', 'value' => 't-2', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-12',
            'margin-right' => 'm-r-19',
            'margin-top' => 'm-t-0',
        ]);
        $vseq->addChild($heading);

        // Подзаголовок
        $heading2 = $this->createHeading('<font class="tx-wh">Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</font>', [
            'align' => 't-l',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-23',
            'margin-right' => 'm-r-19',
            'margin-top' => 'm-t-0',
        ], 'p');
        $vseq->addChild($heading2);

        // Кнопки
        $button1 = $this->createButton('Ullamco laboris', [
            'border-radius' => 'b-r-r',
            'button-size' => 'inp-m p-l-13 p-r-13',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-wht', 'type' => 'palette'],
            'margin-bottom' => 'm-b-12',
            'margin-right' => 'm-r-12',
        ]);
        $button2 = $this->createButton('Malesuada', [
            'border-radius' => 'b-r-r',
            'button-size' => 'inp-m p-l-13 p-r-13',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-wht', 'type' => 'palette'],
            'margin-bottom' => 'm-b-12',
            'margin-right' => 'm-r-12',
        ]);
        $button3 = $this->createButton('Elit a luctus', [
            'border-radius' => 'b-r-r',
            'button-size' => 'inp-m p-l-13 p-r-13',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-wht', 'type' => 'palette'],
            'margin-bottom' => 'm-b-12',
            'margin-right' => 'm-r-12',
        ]);
        $props = [
            'block_props' => [
                'margin-right' => 'm-r-0',
                'margin-top' => 'm-t-a',
                'padding-bottom' => 'p-b-0',
                'padding-right' => 'p-r-0',
                'padding-top' => 'p-t-10',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
            ],
        ];
        $vseq->addChild($this->createRow($props, [$button1, $button2, $button3]));


        $block_props = [
            $this->column_elements['main']    => [
                'border-radius' => 'b-r-l',
                'margin-bottom' => 'm-b-19-tb',
                'margin-right' => 'm-r-19 m-r-0-tb',
                'padding-bottom' => 'p-b-10-mb p-b-12',
                'padding-left' => 'p-l-18 p-l-16-lp p-l-14-mb',
                'padding-right' => 'p-r-19 p-r-16-lp p-r-14-mb',
                'padding-top' => 'p-t-10-mb p-t-12',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
                'padding-bottom' => 'p-b-12',
                'padding-right' => 'p-r-19 p-r-0-mb',
                'padding-top' => 'p-t-12',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-12-mb st-8 st-8-lp st-12-tb',
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-l'],
                'inline_props'  => [
                    $this->column_elements['main']    => [
                        'background'      => [
                            'type' => 'self_color',
                            'name' => 'Self color',
                            'value' => 'linear-gradient(#00000080, #00000080), center center / cover url(' . $this->image_url . 'girl-in-red-3.jpg)',
                            'layers' => [
                                [
                                    'css' => '#00000080',
                                    'name' => 'Self color',
                                    'type' => 'self_color',
                                    'value' => 'linear-gradient(#00000080, #00000080)',
                                ],
                                [
                                    'alignmentX' => 'center',
                                    'alignmentY' => 'center',
                                    'css' => '',
                                    'file_name' => 'girl-in-red-3.jpg',
                                    'file_url' => $this->image_url . 'girl-in-red-3.jpg',
                                    'name' => 'Image',
                                    'space' => 'cover',
                                    'type' => 'image',
                                    'value' => 'center center / cover url(' . $this->image_url . 'girl-in-red-3.jpg)',
                                ],
                            ],
                        ],
                    ],
                    $this->column_elements['wrapper'] => [
                        'min-height' => [
                            'name' => 'Fill parent',
                            'value' => '100%',
                            'type' => 'parent',
                        ],
                    ],
                ],
            ],
            $vseq
        );
    }

    public function getSmallColumn(): siteBlockData {
        $vseq = $this->createSequence();

        $heading = $this->createHeading('<font class="tx-w-opc-7">31.12 &nbsp;23:59</font>', [
            'align' => 't-l',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-8',
            'margin-right' => 'm-r-19 m-r-0-mb',
            'margin-top' => 'm-t-0',
        ]);
        $vseq->addChild($heading);

        $heading2 = $this->createHeading('<font class="tx-wh"> Curabitur tempor quis eros lacinia&nbsp;aliquip&nbsp;nisi</font>', [
            'align' => 't-l',
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #4', 'value' => 't-4', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-14',
            'margin-top' => 'm-t-0-mb m-t-4',
        ], 'h3');
        $vseq->addChild($heading2);

        $price = $this->createHeading('<font class="tx-wh">$99,9</font>', [
            'align' => 't-l',
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #2', 'value' => 't-2', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-0',
            'margin-top' => 'm-t-0',
        ], 'p');
        $vseq->addChild($price);

        $price2 = $this->createHeading('<strike style="color: rgba(255, 251, 251, 0.6);">$249,9</strike>', [
            'align' => 't-l',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #4', 'value' => 't-4', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-19',
            'margin-top' => 'm-t-0',
        ], 'p');
        $vseq->addChild($price2);

        $button = $this->createButton('Commodo&nbsp;consequat', [
            'border-radius' => 'b-r-r',
            'button-size' => 'inp-m p-l-13 p-r-13',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-wht', 'type' => 'palette'],
            'margin-bottom' => 'm-b-12',
            'margin-top' => 'm-t-a',
        ]);
        $vseq->addChild($button);

        $block_props = [
            $this->column_elements['main']    => [
                'border-radius' => 'b-r-l',
                'padding-bottom' => 'p-b-10-mb p-b-12',
                'padding-left' => 'p-l-18 p-l-16-lp p-l-14-mb',
                'padding-right' => 'p-r-16-lp p-r-14-mb p-r-19',
                'padding-top' => 'p-t-10-mb p-t-12',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
                'padding-bottom' => 'p-b-12',
                'padding-top' => 'p-t-12',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-12-mb st-4 st-4-lp st-12-tb',
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-l'],
                'inline_props'  => [
                    $this->column_elements['main']    => [
                        'background'      => [
                            'type' => 'self_color',
                            'name' => 'Self color',
                            'value' => 'linear-gradient(#0000008a, #0000008a), center center / cover url(' . $this->image_url . 'bike.jpg)',
                            'layers' => [
                                [
                                    'css' => '#0000008a',
                                    'name' => 'Self color',
                                    'type' => 'self_color',
                                    'value' => 'linear-gradient(#0000008a, #0000008a)',
                                ],
                                [
                                    'alignmentX' => 'center',
                                    'alignmentY' => 'center',
                                    'css' => '',
                                    'file_name' => 'bike.jpg',
                                    'file_url' => $this->image_url . 'bike.jpg',
                                    'name' => 'Image',
                                    'space' => 'cover',
                                    'type' => 'image',
                                    'value' => 'linear-gradient(#0000008a, #0000008a), center center / cover url(' . $this->image_url . 'bike.jpg)',
                                ],
                            ],
                        ],
                    ],
                    $this->column_elements['wrapper'] => [
                        'min-height' => [
                            'name' => 'Fill parent',
                            'value' => '100%',
                            'type' => 'parent',
                        ],
                    ],
                ],
            ],
            $vseq
        );
    }

    private function createHeading(string $text, array $block_props = [], $tag = 'h1') {
        $heading = (new siteHeadingBlockType())->getEmptyBlockData();

        $heading->data = [
            'html'        => $text,
            'block_props' => $block_props,
            'tag'         => $tag,
        ];

        return $heading;
    }

    private function createButton(string $text, array $block_props = [], $tag = 'a') {
        $button = (new siteButtonBlockType())->getEmptyBlockData();

        $button->data = [
            'html'        => $text,
            'block_props' => $block_props,
            'tag'         => $tag,
        ];

        return $button;
    }

    /**
     * Создаёт последовательность блоков
     *
     * @param bool   $is_horizontal
     * @param string $complex_type
     * @param bool   $indestructible
     * @return siteBlockData
     */
    private function createSequence(bool $is_horizontal = false, string $complex_type = 'with_row', bool $indestructible = false): siteBlockData {
        $seq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $seq->data['is_horizontal'] = $is_horizontal;
        $seq->data['is_complex'] = $complex_type;

        if ($indestructible) {
            $seq->data['indestructible'] = true;
        }

        return $seq;
    }

    /**
     * Создаёт колонку с настройками
     *
     * @param array         $params
     * @param siteBlockData $content
     * @return siteBlockData
     */
    private function createColumn(array $params, siteBlockData $content): siteBlockData {
        $column = (new siteColumnBlockType())->getEmptyBlockData();

        $column->data = [
            'elements'      => $this->column_elements,
            'column'        => $params['column'] ?? 'st-12 st-12-lp st-12-tb st-12-mb',
            'block_props'   => $params['block_props'] ?? [],
            'wrapper_props' => $params['wrapper_props'] ?? [],
            'inline_props'  => $params['inline_props'] ?? [],
        ];

        $column->addChild($content, '');

        return $column;
    }

    /**
     * Создаёт ряд блоков
     *
     * @param array $props
     * @param array $content
     * @return siteBlockData
     */

    private function createRow(array $props, array $content): siteBlockData {
        $row = (new siteRowBlockType())->getExampleBlockData();
        $row->data['block_props'] = $props['block_props'] ?? [];
        $row->data['wrapper_props'] = $props['wrapper_props'] ?? [];

        $hseq = reset($row->children['']);

        foreach ($content as $item) {
            $hseq->addChild($item);
        }

        return $row;
    }
}
