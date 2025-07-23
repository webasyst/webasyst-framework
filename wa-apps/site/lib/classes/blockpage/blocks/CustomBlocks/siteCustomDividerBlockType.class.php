<?php

class siteCustomDividerBlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomDivider';
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
                    'background' => [
                        'name' => 'grey shades',
                        'type' => 'palette',
                        'value' => 'bg-bw-2',
                        'layers' => [
                            [
                                'name' => 'grey shades',
                                'type' => 'palette',
                                'value' => 'bg-bw-2',
                            ],
                        ],
                    ],
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

        $image1 = (new siteImageBlockType())->getEmptyBlockData();
        $image1->data = [
            'image' => [
                'color' => [
                    'name' => 'Palette',
                    'type' => 'palette',
                    'value' => 'tx-bw-4',
                ],
                'type'     => 'svg',
                'fill' => 'removed',
                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 11 11" fill="var(--bw-4)"><path d="M5.96244e-08 5.79425L11 0.794251C11 0.794251 8.81165 3.67553 8.81165 5.79425C8.81165 7.91297 11 10.7943 11 10.7943L5.96244e-08 5.79425Z"></path></svg>',
            ],
            'block_props' => [
                'border-radius' => 'b-r-l',
                'margin-bottom' => 'm-b-8',
                'margin-left' => 'm-l-4',
                'margin-right' => 'm-r-4',
                'margin-top' => 'm-t-11',
                'picture-size' => 'i-s',
            ],
        ];

        $image2 = (new siteImageBlockType())->getEmptyBlockData();
        $image2->data = [
            'image' => [
                'color' => [
                    'name' => 'Palette',
                    'type' => 'palette',
                    'value' => 'tx-bw-4',
                ],
                'type'     => 'svg',
                'fill' => 'removed',
                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="20" viewBox="0 0 22 20" fill="var(--bw-4)"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.5556 11.6058C12.9964 10.4925 14.2737 10 14.2737 7.08152C14.2737 5 12.3046 1.21631 11.1092 0C9.91378 1.21631 7.94471 5 7.94471 7.08152C7.94471 10 9.24315 10.6137 9.71315 11.7292C5.11773 8.42174 2.3116 10.8701 0 13.576C3.15111 13.4547 6.34212 14.3309 8.74778 16.7165C9.73768 17.6982 10.5136 18.8116 11.0754 20C11.6476 18.6217 12.4984 17.3304 13.6275 16.2106C15.9377 13.9196 18.9721 13.0406 22 13.0615C19.5421 10.8634 16.9031 8.4381 12.5556 11.6058Z"></path></svg>',
            ],
            'block_props' => [
                'border-radius' => 'b-r-l',
                'margin-bottom' => 'm-b-8',
                'margin-left' => 'm-l-4',
                'margin-right' => 'm-r-4',
                'margin-top' => 'm-t-6',
                'picture-size' => 'i-s',
            ],
        ];

        $image3 = (new siteImageBlockType())->getEmptyBlockData();
        $image3->data = [
            'image' => [
                'color' => [
                    'name' => 'Palette',
                    'type' => 'palette',
                    'value' => 'tx-bw-4',
                ],
                'type'     => 'svg',
                'fill' => 'removed',
                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="11" viewBox="0 0 12 11" fill="var(--bw-4)"><path d="M11.25 5.79425L0.25 10.7943C0.25 10.7943 2.43835 7.91297 2.43835 5.79425C2.43835 3.67553 0.25 0.79425 0.25 0.79425L11.25 5.79425Z"></path></svg>',
            ],
            'block_props' => [
                'border-radius' => 'b-r-l',
                'margin-bottom' => 'm-b-8',
                'margin-left' => 'm-l-4',
                'margin-right' => 'm-r-4',
                'margin-top' => 'm-t-11',
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
                    'value' => 'br-bw-4',
                ],
                'margin-bottom' => 'm-b-8',
                'margin-left' => 'm-l-4',
                'margin-right' => 'm-r-4',
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
        ], [$image1, $hr, $image2, $hr, $image3]));

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
