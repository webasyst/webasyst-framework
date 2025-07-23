<?php

class siteCustomDivider4BlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomDivider4';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $img_url = wa()->getAppStaticUrl('site') . 'img/blocks/dividers/pattern-top.svg';
        $result->data = [
            'block_props'   => [
                $this->elements['main']    => [
                    'background' => [
                        'name' => 'grey shades',
                        'type' => 'palette',
                        'value' => 'bg-bw-5',
                        'layers' => [
                            [
                                'name' => 'grey shades',
                                'type' => 'palette',
                                'value' => 'bg-bw-5',
                            ],
                        ],
                    ],
                ],
                $this->elements['wrapper'] => [
                    'flex-align' => 'y-c',
                ],
            ],
            'inline_props' => [
                $this->elements['main'] => [],
                $this->elements['wrapper'] => [
                    'background' => [
                        'name' => 'Self color',
                        'type' => 'self_color',
                        'value' => 'center bottom url(' . $img_url . ')',
                        'layers' => [
                            [
                                'alignmentX' => 'center',
                                'alignmentY' => 'bottom',
                                'css' => '',
                                'file_name' => 'pattern-top.svg',
                                'file_url' => $img_url,
                                'name' => 'Image',
                                'space' => 'contain',
                                'type' => 'image',
                                'value' => 'center bottom url(' . $img_url . ')',
                            ],
                        ],
                    ],
                    'min-height' => [
                        'name' => 'Custom',
                        'type' => 'custom',
                        'value' => '40px',
                        'unit' => 'px',
                    ]
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
                'type_name_original'    => _w('Divider 4'),
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
}
