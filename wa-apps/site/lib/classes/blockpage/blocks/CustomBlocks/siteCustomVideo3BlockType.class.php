<?php

class siteCustomVideo3BlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomVideo3';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        // Добавляем последовательности в основной блок
        $hseq->addChild($this->getTextColumn());

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['main'] => [
                    'padding-bottom' => 'p-b-30 p-b-27-lp p-b-18-mb p-b-23-tb',
                    'padding-left' => 'p-l-blc',
                    'padding-right' => 'p-r-blc',
                    'padding-top' => 'p-t-30 p-t-27-lp p-t-18-mb p-t-23-tb',
                ],
                $this->elements['wrapper'] => [
                    'flex-align' => 'y-c',
                    'max-width' => 'cnt',
                    'padding-bottom' => 'p-b-20 p-b-16-mb',
                    'padding-top' => 'p-t-20 p-t-16-mb',
                ],
            ],
            'wrapper_props' => [
                'justify-align' => 'y-j-cnt',
            ],
            'inline_props'  => [
                $this->elements['main'] => [
                    'background' => [
                        'layers' => [
                            [
                                'css' => '#00000057',
                                'name' => 'Self color',
                                'type' => 'self_color',
                                'value' => 'linear-gradient(#00000057, #00000057)',
                            ],
                            [
                                'css' => '',
                                'file_name' => 'new-collection.mp4',
                                'file_url' => wa()->getAppStaticUrl('site') . 'img/blocks/video/new-collection.mp4',
                                'name' => 'Video',
                                'type' => 'video',
                                'value' => 'url(' . wa()->getAppStaticUrl('site') . 'img/blocks/video/new-collection.mp4)',
                            ],
                        ],
                        'name' => 'Self color',
                        'type' => 'self_color',
                        'value' => 'linear-gradient(#00000057, #00000057)',
                    ],
                ]
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

    public function getTextColumn(): siteBlockData {
        $vseq = $this->createSequence();

        $text = (new siteParagraphBlockType())->getEmptyBlockData();
        $text->data = [
            'html'        => '<span class="tx-wh">Ver – Aestas</span>',
            'block_props' => [
                'align' => 't-c',
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-10',
                'margin-top' => 'm-t-0',
            ],
            'tag'         => 'h2',
        ];
        $vseq->addChild($text);

        $heading = $this->createHeading('<font color="" class="tx-wh"><b>NOVA COLLECTIO</b></font>', [
            'align' => 't-c',
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #1', 'value' => 't-1', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-12',
            'margin-top' => 'm-t-0',
        ], 'p');
        $vseq->addChild($heading);

        $button1 = $this->createButton("Pro Virō", [
            'button-size' => 'inp-l p-l-14 p-r-14',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-wht-strk', 'type' => 'palette'],
            'margin-bottom' => 'm-b-12',
            'margin-right' => 'm-r-12',
        ]);
        $button2 = $this->createButton("Pro Fēminā", [
            'button-size' => 'inp-l p-l-14 p-r-14',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-wht-strk', 'type' => 'palette'],
            'margin-bottom' => 'm-b-12',
        ]);
        $vseq->addChild($this->createRow([
            'block_props' => [
                'padding-bottom' => 'p-b-10',
                'padding-top' => 'p-t-10',
            ],
            'wrapper_props' => [
                'justify-align' => 'y-j-cnt',
            ],
        ],[$button1, $button2]));
        

        $block_props = [
            $this->column_elements['main']    => [
                'padding-bottom' => 'p-b-20 p-b-12-tb',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
                'padding-top' => 'p-t-20 p-t-12-tb',
            ],
            $this->column_elements['wrapper'] => [
                'column-max-width' => 'fx-9',
                'flex-align' => 'y-c',
                'margin-left' => 'm-l-a',
                'margin-right' => 'm-r-a',
                'padding-bottom' => 'p-b-20 p-b-12-tb',
                'padding-top' => 'p-t-20 p-t-12-tb',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => "st-12 st-12-lp st-12-tb st-12-mb",
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-c'],
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
        $row->data['inline_props'] = $props['inline_props'] ?? [];

        $hseq = reset($row->children['']);

        foreach ($content as $item) {
            $hseq->addChild($item);
        }

        return $row;
    }

}
