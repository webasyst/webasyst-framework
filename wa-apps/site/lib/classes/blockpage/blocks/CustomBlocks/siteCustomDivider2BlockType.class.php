<?php

class siteCustomDivider2BlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomDivider2';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        $hseq->addChild($this->getColumn());

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['main']    => [
                    'padding-bottom' => 'p-b-16',
                    'padding-left' => 'p-l-blc',
                    'padding-right' => 'p-r-blc',
                    'padding-top' => 'p-t-16',
                ],
                $this->elements['wrapper'] => [
                    'flex-align' => 'y-c',
                    'max-width' => 'cnt',
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

    public function getColumn(): siteBlockData {
        $vseq = $this->createSequence();

        $image = (new siteImageBlockType())->getEmptyBlockData();
        $image->data = [
            'image' => [
                'color' => [
                    'name' => 'Palette',
                    'type' => 'palette',
                    'value' => 'tx-bw-6',
                ],
                'type'     => 'svg',
                'fill' => 'removed',
                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="auto" viewBox="0 0 24 24" fill="var(--bw-6)"><path d="M24 12.5L12 25L0 12.5L12 0L24 12.5ZM2.77148 12.5L12 22.1123L21.2275 12.5L12 2.88672L2.77148 12.5Z"></path></svg>',
            ],
            'block_props' => [
                'border-radius' => 'b-r-l',
                'margin-bottom' => 'm-b-8',
                'margin-left' => 'm-l-8',
                'margin-right' => 'm-r-8',
                'margin-top' => 'm-t-8',
                'picture-size' => 'i-s',
            ],
        ];
        
        $hr = (new siteHrBlockType())->getEmptyBlockData();
        $hr->data = [
            'tag' => 'hr',
            'block_props' => [
                'border-color' => [
                    'name' => 'grey shades',
                    'type' => 'palette',
                    'value' => 'br-bw-6',
                ],
                'margin-bottom' => 'm-b-8',
                'margin-right' => 'm-r-8',
                'margin-top' => 'm-t-13',
            ],
            'inline_props' => [
                'border-width' => [
                    'name' => 'Self size',
                    'type' => 'self_size',
                    'unit' => 'px',
                    'value' => '1px',
                ],
            ],
        ];

        $vseq->addChild($this->createRow([
            'block_props' => [
                'padding-bottom' => 'p-b-10',
                'padding-top' => 'p-t-10',
            ],
            'wrapper_props' => [
                'flex-wrap' => 'n-wr-lp n-wr-ds n-wr-tb n-wr-mb',
                'justify-align' => 'y-j-cnt',
            ]
        ], [$hr, $image, $hr]));

        $block_props = [
            $this->column_elements['main']    => [
                'flex-align-vertical' => 'a-c-c',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-12 st-12-tb st-12-lp st-12-mb',
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
