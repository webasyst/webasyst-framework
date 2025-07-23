<?php

class siteCustomBanner2BlockType extends siteBlockType {
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
            $options['columns'] = 1;
        }
        $options['type'] = 'site.CustomBanner2';
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
        $hseq->addChild($this->getBtnColumn());

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['main']    => [
                    'padding-top'    => "p-t-18",
                    'padding-bottom' => "p-b-18",
                    'padding-left' => 'p-l-blc',
                    'padding-right' => 'p-r-blc',
                ],
                $this->elements['wrapper'] => [
                    'border-radius' => "b-r-l",
                    'flex-align' => "y-c",
                    'max-width' => "cnt",
                    'padding-bottom' => "p-b-10",
                    'padding-top' => "p-t-10",
                ],
            ],
            'inline_props' => [
                $this->elements['wrapper'] => [
                    'background' => [
                        'name' => 'Self color',
                        'type' => 'self_color',
                        'value' => 'linear-gradient(#d99700, #d99700)',
                        'layers' => [
                            [
                                'name' => 'Self color',
                                'type' => 'self_color',
                                'css' => '#d99700',
                                'value' => 'linear-gradient(#d99700, #d99700)',
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
                'type_name_original'    => _w('Banner 2'),
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


    /**
     * Получает колонку с заголовком
     *
     * @return siteBlockData
     */
    public function getImageColumn(): siteBlockData {
        $vseq = $this->createSequence();

        $image = (new siteImageBlockType())->getEmptyBlockData();
        $image->data = [
            'image' => [
                'type'     => 'address',
                'url_text' => wa()->getAppStaticUrl('site') . 'img/blocks/banners/fall.png',
            ],
            'block_props' => [
                'border-radius' => 'b-r-m',
                'visibility'    => 'd-n-mb d-n-tb',
            ],
        ];

        $vseq->addChild($image);

        $block_props = [
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
                'padding-bottom' => 'p-b-12',
                'padding-top' => 'p-t-12',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-2 st-2-lp st-0-tb st-0-mb',
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-l'],
            ],
            $vseq
        );
    }

    public function getTextColumn(): siteBlockData {
        $vseq = $this->createSequence();

        $paragraph = (new siteParagraphBlockType())->getEmptyBlockData();
        $paragraph->data = [
            'html'        => '<font class="tx-blc">Suspendisse vulputate fermentu</font>',
            'tag'         => 'h1',
            'block_props' => [
                'align' => 't-l',
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-9',
                'margin-top' => 'm-t-0',
            ],
        ];

        $heading = (new siteHeadingBlockType())->getEmptyBlockData();
        $heading->data = [
            'html'        => '<font class="tx-bw-1" color="#000000">Phasellus porttitor, justo eu ultrices vulputate, sit amet at erat.  Sed vel lorem eros. </font>',
            'tag'         => 'h1',
            'block_props' => [
                'align' => 't-c',
                'font-header' => 't-hdn',
                'font-size' => ['name' => 'Size #4', 'value' => 't-4', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-10',
                'margin-top' => 'm-t-0',
            ],
        ];


        $vseq->addChild($paragraph);
        $vseq->addChild($heading);

        $block_props = [
            $this->column_elements['main']    => [
                'flex-align-vertical' => 'a-c-c',
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
                'column'        => 'st-12-mb st-7 st-7-lp st-8-tb',
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-c'],
            ],
            $vseq
        );
    }

    public function getBtnColumn(): siteBlockData {
        $vseq = $this->createSequence();

        $button = (new siteButtonBlockType())->getEmptyBlockData();
        $button->data = [
            'html'        => 'Sollicitudin lacus',
            'tag'         => 'a',
            'block_props' => [
                'border-radius' => 'b-r-r',
                'button-size'   => 'inp-l p-l-14 p-r-14',
                'button-style'  => ['name' => 'Palette', 'value' => 'btn-blc', 'type' => 'palette'],
            ],
        ];

        $vseq->addChild($button);

        $block_props = [
            $this->column_elements['main']    => [
                'flex-align-vertical' => 'a-c-c',
                'padding-bottom' => 'p-b-0',
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
                'column'        => 'st-12-mb st-4-tb st-3 st-3-lp',
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-c'],
            ],
            $vseq
        );
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
}
