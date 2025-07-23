<?php

class siteCustomHero2BlockType extends siteBlockType {
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

    public function __construct(array $options = []) {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = 2;
        }
        $options['type'] = 'site.CustomHero2';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        // Добавляем последовательности в основной блок
        $hseq->addChild($this->getImageColumn());
        $hseq->addChild($this->getTextColumn());

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['main']    => [
                    'padding-top'    => "p-t-18",
                    'padding-bottom' => "p-b-18",
                ],
                $this->elements['wrapper'] => [
                    'padding-top'    => "p-t-12",
                    'padding-bottom' => "p-b-12",
                    'flex-align'     => "y-c",
                    'max-width'      => "cnt",
                ],
            ],
            'inline_props'  => [
                $this->elements['main']    => [
                    'background' => [
                        'type' => 'self_color',
                        'name' => 'Self color',
                        'value' => 'linear-gradient(#010102, #010102)',
                        'layers' => [
                            [
                                'css' => '#010102',
                                'name' => 'Self color',
                                'type' => 'self_color',
                                'value' => 'linear-gradient(#010102, #010102)',
                            ],
                        
                        ],
                    ],
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
                    [   'type' => 'HeightGroup',
                        'name' => _w('Height'),
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

    public function getImageColumn(): siteBlockData {
        $vseq = $this->createSequence();
        $vseq->addChild($this->getImage());


        $block_props = [
            $this->column_elements['main']    => [
                'padding-bottom'      => 'p-b-10-mb',
                'padding-top'         => 'p-t-10-mb',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align'       => 'y-c',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-6-lp st-12-mb st-12-tb st-7',
                'block_props'   => $block_props,
                //'wrapper_props' => ['flex-align' => 'y-l'],
            ],
            $vseq
        );
    }

    public function getTextColumn(): siteBlockData {
        $vseq = $this->createSequence();

        // Заголовок
        $heading = $this->createHeading('<font class="tx-wh">Senectus et netus et malesuada fames</font>', [
            'align' => 't-l',
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #3', 'value' => 't-2', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-12',
            'margin-top' => 'm-t-0',
        ]);
        $vseq->addChild($heading);

        // Подзаголовок
        $heading2 = $this->createHeading('<font class="tx-wh">Curabitur vel bibendum lorem. Morbi convallis convallis diam sit amet lacinia. Donec erat diam.</font>', [
            'align' => 't-l',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-18',
            'margin-top' => 'm-t-0',
        ]);
        $vseq->addChild($heading2);

        // Строки с текстом
        foreach ($this->getRowData() as $n_text => $b_text) {
            $vseq->addChild($this->getTextRow($n_text, $b_text));
        }

        // Цена
        $price = $this->createHeading('<font class="tx-wh">$179.99 – $299.99</font>', [
            'align' => 't-l',
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #4', 'value' => 't-4', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-14',
            'margin-top' => 'm-t-16',
        ]);
        $vseq->addChild($price);

        // Кнопки
        $button1 = $this->createButton('Vestibulum', [
            'border-radius' => 'b-r-r',
            'button-size' => 'inp-l p-l-14 p-r-14',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-wht', 'type' => 'palette'],
            'margin-bottom' => 'm-b-12',
            'margin-right' => 'm-r-14',
        ]);
        $button2 = $this->createButton('Curabitur', [
            'border-radius' => 'b-r-r',
            'button-size' => 'inp-l p-l-14 p-r-14',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-wht-strk', 'type' => 'palette'],
            'margin-bottom' => 'm-b-12',
        ]);
        $props = [
            'block_props' => [
                'padding-bottom' => 'p-b-4',
                'padding-top' => 'p-t-4',
            ],
            'wrapper_props' => [
                'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp',
                'justify-align' => 'j-s',
            ],
        ];
        $vseq->addChild($this->createRow($props, [$button1, $button2]));

        // Примечание
        $notice = $this->createHeading('<font class="tx-bw-4">*Aliquam erat sapien, vestibulum nec accumsan eu, molestie sit amet neque.</font>', [
            'align' => 't-l',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #8', 'value' => 't-8', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-16',
            'margin-top' => 'm-t-0',
        ]);
        $vseq->addChild($notice);

        $block_props = [
            $this->column_elements['main']    => [
                'flex-align-vertical' => 'a-c-c',
                'padding-bottom'      => 'p-b-12 p-b-10-mb',
                'padding-top'         => 'p-t-12 p-t-0-mb',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
                'padding-bottom' => 'p-b-12',
                'padding-top' => 'p-t-12',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-6-lp st-12-mb st-12-tb st-5',
                'block_props'   => $block_props,
                //'wrapper_props' => ['flex-align' => 'y-l'],
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

    private function getImage() {
        $imageBlock = (new siteImageBlockType())->getEmptyBlockData();

        $imageBlock->data = [
            'image'       => [
                'type'     => 'address',
                'url_text' => wa()->getAppStaticUrl('site') . 'img/blocks/hero/bottle.jpg',
            ]
        ];

        return $imageBlock;
    }

    private function getTextRow($n_text, $b_text) {
        $normal_text = $this->createHeading('<font class="tx-bw-6">'.$n_text.'</font>', [
            'align' => 't-l',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-4',
            'margin-right' => 'm-r-8',
            'margin-top' => 'm-t-0',

        ]);

        $bold_text = $this->createHeading('<font class="tx-bw-7">'.$b_text.'</font>', [
            'align' => 't-r',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-4',
            //'margin-left' => 'm-l-a',
            'margin-top' => 'm-t-0',
        ]);

        $props = [
            'block_props' => [
                'margin-bottom' => 'm-b-8',
                'margin-right' => 'm-r-14-lp m-r-0-mb m-r-16',
                'padding-bottom' => 'p-b-4',
                'padding-top' => 'p-t-4',
            ],
            'wrapper_props' => [
                'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp n-wr-mb',
                'justify-align' => 'j-s',
            ],
        ];

        return $this->createRow($props, [$normal_text, $bold_text]);
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

    private function getRowData() {
        return [
            'Nunc tempor interdum ex:' => '1987 ut mauris',
            'Minim veniam:' => '0°C enim egestas',
            'Aptent taciti sociosqu:' => '100% ullamcorper',
        ];
    }

}
