<?php

class siteCustomImagesWithDescription4BlockType extends siteBlockType {
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

    /** @var array Стандартные настройки текста */
    private array $text_base_props = [
        'align'      => 't-l',
        'margin-top' => 'm-t-0',
    ];

    /** @var array Настройки выравнивания колонки */
    private array $column_base_props = [
        'indestructible' => false,
        'wrapper_props'  => [
            'flex-align' => 'y-l',
        ],
    ];

    /**
     * Конструктор класса
     *
     * @param array $options
     */
    public function __construct(array $options = []) {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = 3;
        }
        $options['type'] = 'site.CustomImagesWithDescription4';
        parent::__construct($options);
    }

    /**
     * Создаёт пример блока с данными
     *
     * @return siteBlockData
     * @throws \waException
     */
    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        // Добавляем последовательности в основной блок
        $hseq->addChild($this->getDescriptionColumn1());
        $hseq->addChild($this->getImageColumn());
        $hseq->addChild($this->getDescriptionColumn2());

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => $this->getMainBlockProps(),
            'wrapper_props' => ['justify-align' => 'y-j-cnt'],
            'elements'      => $this->elements,
        ];

        return $result;
    }

    /**
     * Получает свойства основного блока
     *
     * @return array
     */
    private function getMainBlockProps(): array {
        $block_props = [];
        $block_props[$this->elements['main']] = [
            'padding-top'    => 'p-t-12',
            'padding-bottom' => 'p-b-12',
        ];
        $block_props[$this->elements['wrapper']] = [
            'flex-align' => 'y-c',
        ];

        return $block_props;
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
     * Рендерит блок
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
     * Получает конфигурацию формы настроек блока
     *
     * @return array
     */
    public function getRawBlockSettingsFormConfig(): array {
        return [
                'type_name'    => _w('Block'),
                'sections'     => $this->getFormSections(),
                'elements'     => $this->elements,
                'semi_headers' => [
                    'main'    => _w('Whole block'),
                    'wrapper' => _w('Container'),
                ],
            ] + parent::getRawBlockSettingsFormConfig();
    }

    /**
     * Получает секции для формы настроек
     *
     * @return array
     */
    private function getFormSections(): array {
        return [
            ['type' => 'ColumnsGroup', 'name' => _w('Columns')],
            ['type' => 'RowsAlignGroup', 'name' => _w('Columns alignment')],
            ['type' => 'RowsWrapGroup', 'name' => _w('Wrap line')],
            ['type' => 'TabsWrapperGroup', 'name' => _w('Tabs')],
            ['type' => 'CommonLinkGroup', 'name' => _w('Link or action'), 'is_hidden' => true],
            ['type' => 'MaxWidthToggleGroup', 'name' => _w('Max width')],
            ['type' => 'BackgroundColorGroup', 'name' => _w('Background')],
            ['type' => 'HeightGroup', 'name' => _w('Height')],
            ['type' => 'PaddingGroup', 'name' => _w('Padding')],
            ['type' => 'MarginGroup', 'name' => _w('Margin')],
            ['type' => 'BorderGroup', 'name' => _w('Border')],
            ['type' => 'BorderRadiusGroup', 'name' => _w('Angle')],
            ['type' => 'ShadowsGroup', 'name' => _w('Shadows')],
            ['type' => 'IdGroup', 'name' => _w('Identifier (ID)')],
        ];
    }

    /**
     * Создаёт колонку с настройками
     *
     * @param string        $column_classes
     * @param array         $block_props
     * @param siteBlockData $content
     * @return siteBlockData
     */
    private function createColumn(string $column_classes, array $block_props, array $wrapper_props, siteBlockData $content): siteBlockData {
        $column = (new siteColumnBlockType())->getEmptyBlockData();

        $column->data = array_merge($this->column_base_props, [
            'elements'      => $this->column_elements,
            'column'        => $column_classes,
            'block_props'   => $block_props,
            'wrapper_props' => $wrapper_props,
        ]);

        $column->addChild($content, '');

        return $column;
    }

    /**
     * Получает колонку с заголовком
     *
     * @return siteBlockData
     */
    public function getImageColumn(): siteBlockData {
        $vseq = $this->createSequence();
        $vseq->addChild($this->getImage());

        $block_props = [
            $this->column_elements['main']    => [
                'padding-top'    => 'p-t-12',
                'padding-bottom' => 'p-b-12',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align'     => 'y-c',
            ],
        ];

        return $this->createColumn(
            "st-12-tb st-12-mb st-12 st-12-lp",
            $block_props,
            ['flex-align' => "y-l"],
            $vseq
        );
    }

    /**
     * Получает блок заголовка
     *
     * @return siteBlockData
     */
    private function getImage(): siteBlockData {
        $imageBlock = (new siteImageBlockType())->getEmptyBlockData();

        $imageBlock->data = [
            'image'       => [
                'type'     => 'address',
                'url_text' => wa()->getAppStaticUrl('site') . 'img/blocks/images_wd/baker.jpg',
            ],
            'block_props' => [
                'border-radius-corner' => 'all',
                'margin-left' => "m-l-a",
                'margin-right' => "m-r-a",
            ],
        ];

        return $imageBlock;
    }

    public function getDescriptionColumn1(): siteBlockData {
        $vseq = $this->createSequence();

        $heading = (new siteHeadingBlockType())->getEmptyBlockData();
        $heading->data = [
            'html'        => 'Descriptio Pistoriae',
            'tag'         => 'h3',
            'block_props' => [
                'align' => "t-l",
                'font-header' => "t-hdn",
                'margin-bottom' => "m-b-12",
                'margin-top' => "m-t-0",
                'font-size' => [
                    'name' => "Size #2",
                    'type' => "library",
                    'unit' => "px",
                    'value' => "t-2",
                ]
            ],
        ];

        $paragraph = (new siteParagraphBlockType())->getEmptyBlockData();
        $paragraph->data = [
            'html'        => "Pistoria nostra panem recentem quotidie facit. Farinam optimam et probatas formulas utimus. Pistores nostri ab aurora laborant ut mane panis paratus sit.",
            'tag'         => 'p',
            'block_props' => [
                'align' => "t-l",
                'font-header' => "t-rgl",
                'margin-bottom' => "m-b-12",
                'margin-top' => "m-t-0",
                'font-size' => [
                    'name' => "Size #6",
                    'type' => "library",
                    'unit' => "px",
                    'value' => "t-6",
                ]
            ],
        ];


        $vseq->addChild($heading);
        $vseq->addChild($paragraph);

        $block_props = [
            $this->column_elements['main']    => [
                'flex-align-vertical' => 'a-c-s',
                'padding-bottom'      => 'p-b-12',
                'padding-top'         => 'p-t-12',
                'padding-left'        => 'p-l-blc',
                'padding-right'       => 'p-r-blc',
            ],
            $this->column_elements['wrapper'] => [
                'column-max-width' => "fx-6",
                'flex-align'       => "y-c",
                'margin-left'      => "m-l-a",
                'margin-right'     => "m-r-a",
                'padding-left'     => "p-l-clm",
                'padding-right'    => "p-r-clm",
            ],
        ];

        return $this->createColumn(
            'st-12-tb st-12-mb st-12-lp st-12',
            $block_props,
            ['flex-align' => "y-l"],
            $vseq
        );
    }

    public function getDescriptionColumn2(): siteBlockData {
        $vseq = $this->createSequence();

        $heading = (new siteHeadingBlockType())->getEmptyBlockData();
        $heading->data = [
            'html'        => "Merces Nostrae",
            'tag'         => 'h3',
            'block_props' => [
                'align' => "t-l",
                'font-header' => "t-hdn",
                'margin-bottom' => "m-b-12",
                'margin-top' => "m-t-0",
                'font-size' => [
                    'name' => "Size #4",
                    'type' => "library",
                    'unit' => "px",
                    'value' => "t-4",
                ]
            ],
        ];

        $paragraph1 = (new siteParagraphBlockType())->getEmptyBlockData();
        $paragraph1->data = [
            'html'        => "In pistoria nostra varia genera panis invenietis. Panem album, panem nigrum et dulces libas coquimus. Omnia opera nostra ex naturalibus rebus fiunt.",
            'tag'         => 'p',
            'block_props' => [
                'align' => "t-l",
                'font-header' => "t-rgl",
                'margin-bottom' => "m-b-12",
                'margin-top' => "m-t-0",
                'font-size' => [
                    'name' => "Size #6",
                    'type' => "library",
                    'unit' => "px",
                    'value' => "t-6",
                ]
            ],
        ];

        $paragraph2 = (new siteParagraphBlockType())->getEmptyBlockData();
        $paragraph2->data = [
            'html'        => "Habemus quoque crustula mellita et placentas fructuosas. Quotidie aliquid novum emptoribus nostris paramus.",
            'tag'         => 'p',
            'block_props' => [
                'align' => "t-l",
                'font-header' => "t-rgl",
                'margin-bottom' => "m-b-12",
                'margin-top' => "m-t-0",
                'font-size' => [
                    'name' => "Size #6",
                    'type' => "library",
                    'unit' => "px",
                    'value' => "t-6",
                ]
            ],
        ];


        $vseq->addChild($heading);
        $vseq->addChild($paragraph1);
        $vseq->addChild($paragraph2);

        $block_props = [
            $this->column_elements['main']    => [
                'padding-bottom'      => 'p-b-12',
                'padding-top'         => 'p-t-12',
                'padding-left'        => 'p-l-blc',
                'padding-right'       => 'p-r-blc',
            ],
            $this->column_elements['wrapper'] => [
                'column-max-width' => "fx-6",
                'flex-align'       => "y-c",
                'margin-left'      => "m-l-a",
                'margin-right'     => "m-r-a",
                'padding-left'     => "p-l-clm",
                'padding-right'    => "p-r-clm",
            ],
        ];

        return $this->createColumn(
            'st-12-tb st-12-mb st-12-lp st-12',
            $block_props,
            ['flex-align' => "y-l"],
            $vseq
        );
    }
}
