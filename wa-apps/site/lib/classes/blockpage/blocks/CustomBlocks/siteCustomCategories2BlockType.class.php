<?php

class siteCustomCategories2BlockType extends siteBlockType {
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

    /** @var array Настройки выравнивания колонки */
    private array $column_base_props = [
        'indestructible' => false,
        'wrapper_props'  => [
            'flex-align' => 'y-c',
        ],
    ];

    /**
     * Конструктор класса
     *
     * @param array $options
     */
    public function __construct(array $options = []) {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = 1;
        }
        $options['type'] = 'site.CustomCategories2';
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


        // Создаём карточки товаров
        $categories = $this->getExampleCategories();
        foreach ($categories as $category_data) {
            $hseq->addChild($this->getCategoryColumn($category_data));
        }

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
     * Получает данные для примера категорий
     *
     * @return array
     * @throws \waException
     */
    private function getExampleCategories(): array {
        $base_url = wa()->getAppStaticUrl('site') . 'img/blocks/categories/';

        return [
            [
                'image_url' => $base_url . 'clothes.jpg',
                'title'     => 'Vestitus',
            ],
            [
                'image_url' => $base_url . 'shoes.jpg',
                'title'     => 'Calceamenta',
            ],
            [
                'image_url' => $base_url . 'accessories.jpg',
                'title'     => 'Ornamenta',
            ]
        ];
    }

    /**
     * Получает свойства основного блока
     *
     * @return array
     */
    private function getMainBlockProps(): array {
        return [
            $this->elements['main'] => [
                'padding-bottom' => 'p-b-18 p-b-16-mb',
                'padding-left' => 'p-l-blc',
                'padding-right' => 'p-r-blc',
                'padding-top' => 'p-t-18 p-t-16-mb',
            ],
            $this->elements['wrapper'] => [
                'flex-align' => 'y-c',
                'max-width' => 'cnt',
                'padding-bottom' => 'p-b-16 p-b-0-mb',
                'padding-top' => 'p-t-16 p-t-0-mb',
            ]
        ];
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
                'type_name_original'    => _w('Columns'),
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
    private function createColumn(string $column_classes, array $block_props, array $inline_props, siteBlockData $content): siteBlockData {
        $column = (new siteColumnBlockType())->getEmptyBlockData();

        $column->data = array_merge($this->column_base_props, [
            'elements'    => $this->column_elements,
            'column'      => $column_classes,
            'block_props' => $block_props,
            'inline_props' => $inline_props,
        ]);

        $column->addChild($content, '');

        return $column;
    }

    /**
     * Получает колонку с карточкой товара
     *
     * @param array $card_data
     * @return siteBlockData
     */
    public function getCategoryColumn(array $category_data): siteBlockData {
        $vseq = $this->createSequence();

        $vseq->addChild($this->getCategoryTitle($category_data['title']));

        $block_props = [
            $this->column_elements['main']    => [
                "padding-bottom" => "p-b-14",
                "padding-left" => "p-l-clm",
                "padding-right" => "p-r-clm",
                "padding-top" => "p-t-14",
            ],
            $this->column_elements['wrapper'] => [
                "border-radius" => "b-r-l",
                "flex-align" => "y-c",
                "padding-bottom" => "p-b-12",
                "padding-left" => "p-l-14",
                "padding-right" => "p-r-14",
                "padding-top" => "p-t-30 p-t-29-mb p-t-26-tb"
            ],
        ];

        $inline_props = [
            $this->column_elements['wrapper'] => [
                "background" => [
                    "layers" => [
                        [
                            "css" => "gradient",
                            "name" => "Self color",
                            "type" => "self_color",
                            "value" => "linear-gradient(180deg,  #04040400 60%,  #00000075 100%)",
                            "gradient" => [
                                "degree" => 180,
                                "type" => "linear-gradient",
                                "stops" => [
                                    ['color' => "#04040400", 'stop' => 60],
                                    ['color' => "#00000075", 'stop' => 100],
                                ],
                            ],
                        ],
                        [
                            "alignmentX" => "center",
                            "alignmentY" => "center",
                            "css" => "",
                            "file_name" => $category_data['image_url'],
                            "file_url" => $category_data['image_url'],
                            "name" => "Image",
                            "space" => "cover",
                            "type" => "image",
                            "value" => "center center / cover url(" . $category_data['image_url'] . ")",
                        ]
                    ],
                    "name" => "Self color",
                    "type" => "self_color",
                    "value" => "linear-gradient(180deg,  #04040400 60%,  #00000075 100%), center center / cover url(" . $category_data['image_url'] . ")",
            ],
            ],
        ];

        return $this->createColumn(
            "st-12-mb st-4 st-4-lp st-4-tb",
            $block_props,
            $inline_props,
            $vseq
        );
    }

    /**
     * Создаёт блок заголовка с настройками
     *
     * @param string $html
     * @param string $tag
     * @param array  $props
     * @return siteBlockData
     */
    private function createHeadingBlock(string $html, string $tag, array $props): siteBlockData {
        $block = (new siteHeadingBlockType())->getEmptyBlockData();

        $block->data = [
            'html'        => $html,
            'tag'         => $tag,
            'block_props' => $props,
        ];

        return $block;
    }

    /**
     * Получает блок заголовка товара
     *
     * @param string $title
     * @return siteBlockData
     */
    private function getCategoryTitle(string $title): siteBlockData {
        return $this->createHeadingBlock(
            '<span class="tx-wh">' . $title . '</span>',
            'h3',
            [
                'font-size'     => ['name' => 'Size #4', 'value' => 't-4', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-8',
                'margin-top'    => 'm-t-25',
                'align'         => 't-c',
                'font-header'   => 't-hdn',
            ]
        );
    }
}
