<?php

class siteCustomText4BlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomText4';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        // Добавляем последовательности в основной блок
        $hseq->addChild($this->getColumnTitle());
        foreach ($this->getColumnsData() as $column) {
            $hseq->addChild($this->getColumnContent($column));
        }

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['main']    => [
                    'padding-top'    => "p-t-20 p-t-18-mb",
                    'padding-bottom' => "p-b-20 p-b-12-mb",
                    'padding-left'  => 'p-l-blc',
                    'padding-right' => 'p-r-blc',
                ],
                $this->elements['wrapper'] => [
                    'flex-align'     => "y-c",
                    'max-width'      => "cnt",
                ],
            ],
            'wrapper_props' => [
                'justify-align' => 'y-j-cnt',
            ],
            'elements' => $this->elements,
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

    public function getColumnsData()
    {
        return [
            [
                ['Vis', '18 V'],
                ['Genus capitis', 'celeriter claudens'],
                ['Amplitudo capitis', '10 mm'],
                ['Vis torsionis maxima', '45 Nm'],
                ['Numerus graduum celeritatis', '2'],
            ],
            [
                ['Celeritas rotationis', 'ad 1 500 rpm'],
                ['Capacitas pilae electricae', '2 Ah'],
                ['Genus pilae', 'Li-Ion'],
                ['Tempus repletionis', '60 min'],
                ['Pondus', '1,3 kg'],
            ],
        ];
    }

    public function getColumnContent(array $rows): siteBlockData {
        $getRow = function ($name, $value, $hide_border = false) {
            $p_name = $this->createParagraph('<font color="" class="tx-bw-4">'.$name.'</font>', [
                'align' => 't-l',
                'font-header' => 't-hdn',
                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                'margin-bottom' => 'm-b-8',
                'margin-top' => 'm-t-0',
                'margin-right' => 'm-r-a',
            ]);
            $p_value = $this->createParagraph('<font color="" class="tx-bw-1">'.$value.'</font>', [
                'align' => 't-r',
                'font-header' => 't-hdn',
                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                'margin-bottom' => 'm-b-8',
                'margin-left' => 'm-l-12',
                'margin-top' => 'm-t-0',
            ]);
            return $this->createRow([
                'block_props' => [
                    'padding-top' => 'p-t-10',
                    'padding-bottom' => 'p-b-4',
                    'border-width' => [
                        'name' => 'Толщина 1',
                        'type' => 'library',
                        'unit' => 'px',
                        'value' => 'b-w-s',
                    ],
                ] + ($hide_border ? [
                        'border-color' => [
                            'css' => '#0000001a',
                            'name' => '1-1',
                            'type' => 'palette',
                            'value' => 'bd-tr-1',
                        ],
                        'border-style' => [
                            'type' => 'separate',
                            'value' => '',
                        ],
                    ] : [
                    'border-color' => [
                        'name' => 'semi-transparent-black',
                        'type' => 'palette',
                        'value' => 'br-b-opc-2',
                    ],
                    'border-style' => [
                        'type' => 'separate',
                        'value' => 'b-d-b',
                    ],
                ]),
                'wrapper_props' => [
                    'justify-align' => 'j-s',
                    'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
                ],
            ], [$p_name, $p_value]);
        };

        $vseq = $this->createSequence();

        $last_index = count($rows) - 1;
        foreach ($rows as $i => $row) {
            list($name, $value) = $row;
            $vseq->addChild($getRow($name, $value, $i === $last_index));
        }

        return $this->createColumn([
            'column' => 'st-6 st-6-lp st-6-tb st-12-mb',
            'block_props' => [
                $this->column_elements['main']   => [
                    'padding-bottom' => 'p-b-14',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                    'padding-top' => 'p-t-14',
                ],
                $this->column_elements['wrapper'] => [
                    'flex-align'     => "y-c",
                ],
            ],
            'inline_props' => [
                $this->column_elements['wrapper'] => [
                    'min-height'     => [
                        'name' => 'Parent height',
                        'type' => 'parent',
                        'value' => '100%',
                    ],
                ],
            ],
        ], $vseq);
    }

    private function createParagraph(string $text, array $block_props = [], $tag = 'p') {
        $paragraph = (new siteParagraphBlockType())->getEmptyBlockData();

        $paragraph->data = [
            'html'        => $text,
            'block_props' => $block_props,
            'tag'         => $tag,
        ];

        return $paragraph;
    }

    private function getColumnTitle()
    {
        $vseq = $this->createSequence();

        $title = $this->getTitle();

        $vseq->addChild($title);

        return $this->createColumn([
            'column' => 'st-12 st-12-lp st-12-tb st-12-mb',
            'block_props' => [
                $this->column_elements['main']    => [
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                ],
                $this->column_elements['wrapper'] => [
                    'flex-align'     => "y-c",
                ],
            ],
            'inline_props' => [
                $this->column_elements['wrapper'] => [
                    'min-height' => [
                        'name' => 'Parent height',
                        'type' => 'parent',
                        'value' => '100%',
                    ],
                ],
            ],
        ], $vseq);
    }

    private function getTitle() {
        $heading = (new siteHeadingBlockType())->getEmptyBlockData();

        $heading->data = [
            'html'        => 'Specificationes',
            'tag'         => 'h3',
            'block_props' => [
                'align'         => 't-l',
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-12',
                'margin-top'    => 'm-t-0',
            ],
        ];

        return $heading;
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
