<?php

class siteCustomBannerBlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomBanner';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        // Добавляем последовательности в основной блок
        $hseq->addChild($this->getColumn());

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
                    'padding-top'    => "p-t-12",
                    'padding-bottom' => "p-b-12",
                    'flex-align'     => "y-c",
                    'max-width'      => "cnt",
                    'border-radius'  => "b-r-l",
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
                'type_name_original'    => _w('Banner'),
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
    public function getColumn(): siteBlockData {
        $vseq = $this->createSequence();
        $vseq->addChild($this->getTitle());
        $vseq->addChild($this->getText());
        $vseq->addChild($this->getBtn());

        $block_props = [
            $this->column_elements['main']    => [
                'border-radius'       => 'b-r-l',
                'flex-align-vertical' => 'a-c-s',
                'padding-bottom'      => 'p-b-29',
                'padding-top'         => 'p-t-16',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'column-max-width' => 'fx-6',
                'flex-align'       => 'y-c',
                'margin-left'      => 'm-l-a',
                'margin-right'     => 'm-r-a',
                'padding-bottom'   => 'p-b-30',
                'padding-top'      => 'p-t-12-tb p-t-0-mb p-t-12',
            ],
        ];

        $image = wa()->getAppStaticUrl('site') . 'img/blocks/banners/flowers.jpg';
        $inline_props = [
            $this->column_elements['main'] => [
                'background' => [
                    'name'   => 'Self color',
                    'type'   => 'self_color',
                    'value'  => 'left top / cover url(' . $image . ')',
                    'layers' => [
                        [
                            'alignmentX' => 'left',
                            'alignmentY' => 'top',
                            'css'        => '',
                            'file_name'  => 'flowers.jpg',
                            'file_url'   => $image,
                            'name'       => 'Image',
                            'space'      => 'cover',
                            'type'       => 'image',
                            'uuid'       => 1,
                            'value'      => 'left top / cover url(' . $image . ')',
                        ],
                    ],
                ],
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-12 st-12-lp st-12-tb st-12-mb',
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-c'],
                'inline_props'  => $inline_props,
            ],
            $vseq
        );
    }

    private function getText() {
        $paragraph = (new siteParagraphBlockType())->getEmptyBlockData();

        $paragraph->data = [
            'html'        => 'Eiusmod tempor incididunt ut labore et dolore magna aliqua. ',
            'tag'         => 'h1',
            'block_props' => [
                'align'         => 't-c',
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-14',
                'margin-top'    => 'm-t-0',
                'visibility'    => 'd-n-mb',
            ],
        ];

        return $paragraph;
    }

    private function getTitle() {
        $header = (new siteHeadingBlockType())->getEmptyBlockData();

        $header->data = [
            'html'        => 'Nam a finibus magna',
            'tag'         => 'h2',
            'block_props' => [
                'align'         => 't-c',
                'font-header'   => 't-hdn',
                'font-size'     => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-10',
                'margin-top'    => 'm-t-0',
            ],
        ];

        return $header;
    }

    private function getBtn() {
        $button = (new siteButtonBlockType())->getEmptyBlockData();

        $button->data = [
            'html'        => 'Exercitation',
            'tag'         => 'a',
            'block_props' => [
                'border-radius' => 'b-r-r',
                'button-size'   => 'inp-l p-l-14 p-r-14',
                'button-style'  => ['name' => 'Palette', 'value' => 'btn-blc', 'type' => 'palette'],
                'margin-bottom' => 'm-b-23',
            ],
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
}
