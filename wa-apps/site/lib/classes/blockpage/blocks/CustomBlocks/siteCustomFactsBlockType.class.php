<?php

class siteCustomFactsBlockType extends siteBlockType {
    /**
     * Элементы блока
     */
    protected array $elements = [
        'main'    => 'site-block-columns',
        'wrapper' => 'site-block-columns-wrapper',
    ];

    public function __construct(array $options = []) {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = 1;
        }
        $options['type'] = 'site.CustomFacts';
        parent::__construct($options);
    }

    /**
     * Возвращает данные для примера блока
     *
     * @return siteBlockData
     */
    public function getExampleBlockData(): siteBlockData {
        try {
            // Создаём основной блок
            $result = $this->getEmptyBlockData();

            // Создаём горизонтальную последовательность для колонок
            $hseq = $this->createHorizontalSequence();

            // Добавляем факты в горизонтальную последовательность
            $facts = $this->getDefaultFacts();
            foreach ($facts as $fact) {
                $hseq->addChild($this->getFact($fact['heading'], $fact['paragraph']));
            }

            // Добавляем горизонтальную последовательность в основной блок
            $result->addChild($hseq, '');

            // Настраиваем свойства основного блока
            $block_props = [];
            $block_props[$this->elements['main']] = [
                'padding-top'    => 'p-t-18',
                'padding-bottom' => 'p-b-18',
                'padding-left' => 'p-l-blc',
                'padding-right' => 'p-r-blc',
            ];
            $block_props[$this->elements['wrapper']] = [
                'padding-top'    => 'p-t-12',
                'padding-bottom' => 'p-b-12',
                'flex-align'     => 'y-c',
                'max-width'      => 'cnt',
            ];

            $result->data = [
                'block_props'   => $block_props,
                'wrapper_props' => ['justify-align' => 'y-j-cnt'],
                'elements'      => $this->elements,
            ];

            return $result;
        } catch (Exception $e) {
            waLog::log($e->getMessage());
            return $this->getEmptyBlockData();
        }
    }

    /**
     * Рендеринг блока
     *
     * @param siteBlockData $data
     * @param bool          $is_backend
     * @param array         $tmpl_vars
     * @return string
     */
    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars = []): string {
        return parent::render($data, $is_backend, $tmpl_vars + [
                'children' => array_reduce($data->getRenderedChildren($is_backend), 'array_merge', []),
            ]);
    }

    /**
     * Возвращает конфигурацию формы настроек блока
     *
     * @return array
     */
    public function getRawBlockSettingsFormConfig(): array {
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

    /**
     * Создаёт блок с фактом
     *
     * @param string $heading
     * @param string $paragraph
     * @return siteBlockData
     */
    private function getFact(string $heading, string $paragraph): siteBlockData {
        // Создаём вертикальную последовательность для содержимого колонки
        $vseq = $this->createVerticalSequence();

        // Добавляем заголовок и параграф в вертикальную последовательность
        $vseq->addChild($this->getHeading($heading));
        $vseq->addChild($this->getParagraph($paragraph));

        // Создаём колонку и добавляем в неё вертикальную последовательность
        $column = $this->getColumn();
        $column->addChild($vseq, '');

        return $column;
    }

    /**
     * Создаёт колонку
     *
     * @return siteBlockData
     */
    private function getColumn(): siteBlockData {
        $column = (new siteColumnBlockType())->getEmptyBlockData();

        $column_elements = [
            'main'    => 'site-block-column',
            'wrapper' => 'site-block-column-wrapper',
        ];

        $column->data = [
            'elements'       => $column_elements,
            'column'         => "st-6-tb st-3 st-3-lp st-6-mb",
            'indestructible' => false,
            'block_props'    => [
                $column_elements['main']    => [
                    'padding-top'    => "p-t-12",
                    'padding-bottom' => "p-b-12",
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                ],
                $column_elements['wrapper'] => [
                    'flex-align'     => "y-c",
                    'padding-top'    => "p-t-12",
                    'padding-bottom' => "p-b-12",
                ],
            ],
        ];

        return $column;
    }

    /**
     * Создаёт параграф с текстом
     *
     * @param string $content
     * @return siteBlockData
     */
    private function getParagraph(string $content): siteBlockData {
        $paragraph = (new siteParagraphBlockType())->getEmptyBlockData();

        $paragraph->data = [
            'html'        => $content,
            'tag'         => 'p',
            'block_props' => [
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-top'    => 'm-t-0',
                'margin-bottom' => 'm-b-12',
                'align'         => 't-c',
            ],
        ];

        return $paragraph;
    }

    /**
     * Создаёт заголовок с текстом
     *
     * @param string $content
     * @return siteBlockData
     */
    private function getHeading(string $content): siteBlockData {
        $header = (new siteHeadingBlockType())->getEmptyBlockData();

        $header->data = [
            'html'        => $content,
            'tag'         => 'h1',
            'block_props' => [
                'font-header'   => 't-hdn',
                'font-size'     => ['name' => 'Size #1', 'value' => 't-1', 'unit' => 'px', 'type' => 'library'],
                'margin-top'    => 'm-t-0',
                'margin-bottom' => 'm-b-8',
                'align'         => 't-c',
            ],
        ];

        return $header;
    }

    /**
     * Создаёт горизонтальную последовательность
     *
     * @return siteBlockData
     */
    private function createHorizontalSequence(): siteBlockData {
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['is_complex'] = 'only_columns';
        $hseq->data['indestructible'] = true;

        return $hseq;
    }

    /**
     * Создаёт вертикальную последовательность
     *
     * @return siteBlockData
     */
    private function createVerticalSequence(): siteBlockData {
        $vseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $vseq->data['is_complex'] = 'with_row';

        return $vseq;
    }

    /**
     * Возвращает массив с данными фактов по умолчанию
     *
     * @return array
     */
    private function getDefaultFacts(): array {
        return [
            [
                'heading'   => '$0',
                'paragraph' => 'consectetur',
            ],
            [
                'heading'   => '$52K',
                'paragraph' => 'adipiscing elit',
            ],
            [
                'heading'   => '99%',
                'paragraph' => 'malesuada',
            ],
            [
                'heading'   => 'A+',
                'paragraph' => 'veniam ante',
            ],
        ];
    }
}
