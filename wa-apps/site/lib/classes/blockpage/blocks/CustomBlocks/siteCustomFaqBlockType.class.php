<?php

class siteCustomFaqBlockType extends siteBlockType {
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
            $options['columns'] = 10;
        }
        $options['type'] = 'site.CustomFaq';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        // Добавляем последовательности в основной блок
        foreach ($this->getExampleData() as $item) {
            $hseq->addChild($this->getQuestionColumn($item['q']));
            $hseq->addChild($this->getAnswerColumn($item['a']));
        }

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['main']    => [
                    'padding-top'    => "p-t-20",
                    'padding-bottom' => "p-b-20",
                    'padding-left' => 'p-l-blc',
                    'padding-right' => 'p-r-blc',
                ],
                $this->elements['wrapper'] => [
                    'flex-align' => 'y-c',
                    'max-width' => 'cnt',
                    'padding-bottom' => 'p-b-20',
                    'padding-top' => 'p-t-20',
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

    private function getExampleData() {
        
        return [[
            'q' => 'Mauris in ex arcu?',
            'a' => 'Nulla condimentum condimentum ultricies. Integer sollicitudin fermentum risus id pulvinar.',
        ], [
            'q' => 'Nunc auctor quam non porttitor?',
            'a' => 'Cras eleifend dapibus pretium. Proin nec malesuada velit. Nulla nibh sem, lacinia nec hendrerit in, mattis vitae nulla. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus.',
        ], [
            'q' => 'Vivamus ligula risus?',
            'a' => 'Aliquam ultricies, nisl eu eleifend congue, ante dolor ultrices nulla, id vulputate ante nisi a lorem.',
        ], [
            'q' => 'Donec sagittis ipsum ac libero tincidunt, ac semper nisl varius?',
            'a' => 'Maecenas facilisis, arcu eu blandit varius, diam est tempor odio, quis pharetra turpis nisl et urna.',
        ], [
            'q' => 'Integer vitae facilisis massa?',
            'a' => 'Nam id dui faucibus, viverra turpis vel, vulputate massa.',
        ]];
    }

    private function getQuestionColumn($text): siteBlockData {
        $vseq = $this->createSequence();

        $heading = (new siteHeadingBlockType())->getEmptyBlockData();
        $heading->data = [
            'html'        => $text,
            'tag'         => 'h1',
            'block_props' => [
                'align' => 't-l',
                'font-header' => 't-hdn',
                'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-12',
                'margin-top' => 'm-t-0',
            ],
        ];

        $vseq->addChild($heading);

        $block_props = [
            $this->column_elements['main'] => [
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-6-lp st-6-tb st-12-mb st-5',
                'block_props'   => $block_props,
            ],
            $vseq
        );
    }

    private function getAnswerColumn($text): siteBlockData {
        $vseq = $this->createSequence();

        $paragraph = (new siteParagraphBlockType())->getEmptyBlockData();
        $paragraph->data = [
            'html'        => $text,
            'tag'         => 'p',
            'block_props' => [
                'align' => 't-l',
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-14',
                'margin-top' => 'm-t-0',
            ],
        ];

        $vseq->addChild($paragraph);

        $block_props = [
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-6-lp st-6-tb st-12-mb st-5',
                'block_props'   => $block_props,
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
