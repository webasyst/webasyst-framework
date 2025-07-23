<?php

class siteCustomBanner3BlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomBanner3';
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
                        'value' => 'bg-bw-1',
                        'layers' => [
                            [
                                'name' => 'grey shades',
                                'type' => 'palette',
                                'value' => 'bg-bw-1',
                            ],
                        ],
                    ],
                    'color' => 'f-w',
                    'padding-bottom' => 'p-b-8',
                    'padding-left' => 'p-l-blc',
                    'padding-right' => 'p-r-blc',
                    'padding-top' => 'p-t-8',
                ],
                $this->elements['wrapper'] => [
                    'border-radius' => 'b-r-l',
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
                'fill' => 'removed', // удаляем заливку
                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24" fill="var(--bw-6)"><path d="M22.2742 0.0215189C22.7694 -0.0305195 23.2245 0.0179703 23.7116 0.101232C24.0139 0.337178 24.3015 0.626228 24.3631 1.0229C24.4767 1.75499 24.3451 2.61752 24.3028 3.35706L24.0897 7.08809C24.0148 8.3544 24.0253 9.66387 23.8817 10.9211C23.8452 11.2392 23.8157 11.7261 23.609 11.9776C22.6292 13.17 21.4291 14.2475 20.3568 15.3628C18.8964 16.8815 17.391 18.357 15.9249 19.8705C14.8537 20.9765 13.2382 22.8221 12.0621 23.6515C11.7224 23.891 11.2508 24.0021 10.8398 24C10.431 23.9978 10.0647 23.8352 9.72734 23.6145C8.87262 23.0554 7.11087 21.129 6.28017 20.3244C5.18642 19.2237 4.08171 18.1339 2.96615 17.0554C2.29644 16.4067 1.51569 15.7546 0.943926 15.0183C0.704431 14.7098 0.431281 14.2889 0.410336 13.8909C0.382575 13.3633 0.628745 12.8186 0.974669 12.4322C2.38221 10.8602 3.97853 9.4064 5.4645 7.90486C6.5276 6.83062 11.8559 1.11137 12.8233 0.667149C13.3779 0.412398 21.1587 0.0814812 22.2742 0.0215189ZM19.7709 6.51673C20.4113 6.36074 20.8651 5.79151 20.874 5.13275C20.8798 4.70355 20.6944 4.29387 20.3679 4.01499C20.0413 3.73611 19.6076 3.61678 19.1842 3.68939C18.6635 3.77869 18.2332 4.14485 18.0617 4.64418C17.8901 5.14363 18.0049 5.69677 18.361 6.08682C18.717 6.47688 19.2577 6.64174 19.7709 6.51673Z"></path></svg>',
            ],
            'block_props' => [
                'margin-bottom' => 'm-b-4',
                'margin-right' => 'm-r-10',
                'margin-top' => 'm-t-2',
                'picture-size' => 'i-xs',
            ],
        ];

        $heading = (new siteHeadingBlockType())->getEmptyBlockData();
        $heading->data = [
            'html'        => '<font class="tx-bw-6" data-value="internal-link">Eme unum, alterum 30% viliore. Finitur 31/12. <a href="/">Ordina Hodie!</a></font>',
            'tag'         => 'p',
            'block_props' => [
                'align' => 't-l',
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-0',
                'margin-top' => 'm-t-0',
            ],
        ];

        $vseq->addChild($this->createRow([
            'block_props' => [
                'padding-bottom' => 'p-b-6-lp p-b-6',
                'padding-top' => 'p-t-8-lp p-t-8',
            ],
            'wrapper_props' => [
                'flex-wrap' => 'n-wr-ds n-wr-mb',
                'justify-align' => 'y-j-cnt',
            ]
        ], [$image, $heading]));

        $block_props = [
            $this->column_elements['main']    => [
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'column-max-width' => 'fx-9',
                'flex-align' => 'y-c',
                'margin-left' => 'm-l-a',
                'margin-right' => 'm-r-a',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-12-lp st-12-tb st-12-mb st-12',
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
