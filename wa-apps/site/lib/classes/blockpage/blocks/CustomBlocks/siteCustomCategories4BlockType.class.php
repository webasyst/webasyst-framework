<?php

class siteCustomCategories4BlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomCategories4';
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
            'wrapper_props' => ['justify-align' => 'j-s'],
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
                'image_url' => $base_url . 'categories4-1.jpg',
                'title'     => 'Culina: Ars et Usus',
                'text'      => 'Locus artis et usus, ubi coquere est gaudium.',
                'id'        => 1,
            ],
            [
                'image_url' => $base_url . 'categories4-2.jpg',
                'title'     => 'Balneum: Refugium Tuum',
                'text'      => 'Locus quietis cum materia optima et luce ad commoditatem tuam.',
                'id'        => 2,
            ],
            [
                'image_url' => $base_url . 'categories4-3.jpg',
                'title'     => 'Oecus: Ad Vitam et Otium',
                'text'      => 'Aequilibrium perfectum inter elegantiam et commoditatem.',
                'id'        => 3,
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
                "background" => [
                    "layers" => [
                        [
                            "css" => "palette",
                            "name" => "grey shades",
                            "type" => "palette",
                            "value" => "bg-bw-6",
                        ],
                    ],
                    "name" => "grey shades",
                    "type" => "palette",
                    "value" => "bg-bw-6",
                ],
                'padding-bottom' => 'p-b-20 p-b-16-mb',
                'padding-left' => 'p-l-blc',
                'padding-right' => 'p-r-blc',
                'padding-top' => 'p-t-20 p-t-16-mb',
            ],
            $this->elements['wrapper'] => [
                'flex-align' => 'y-c',
                'max-width' => 'cnt',
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

        // Изображение
        $image = (new siteImageBlockType())->getEmptyBlockData();
        $image->data = [
            'image' => [
                'type'     => 'address',
                'url_text' => $category_data['image_url'],
            ],
            'block_props' => [
                'border-radius' => 'b-r-l',
                'border-radius-corners' => [
                    'value' => 'b-r-u-bl b-r-u-br',
                    'type' => 'separate',
                ],
            ],
        ];
        $vseq->addChild($image);

        // Подколонка
        $sub_column = (new siteSubColumnBlockType())->getExampleBlockData();
        $sub_column->data['block_props'] = [
            'full-width' => 'f-w',
            'padding-bottom' => 'p-b-12',
            'padding-left' => 'p-l-14',
            'padding-right' => 'p-r-14',
            'padding-top' => 'p-t-12',
            'border-radius-corners' => [
                'value' => '',
                'type' => 'separate',
            ],
        ];
        $sub_column->data['wrapper_props'] = [
            'justify-align' => 'j-s',
            'flex-align' => 'y-l',
        ];
        $sub_column_vseq = reset($sub_column->children['']);


        $sub_column_vseq->addChild($this->getCategoryTitle($category_data['title']));
        $sub_column_vseq->addChild($this->getCategoryText($category_data['text']));
        
        $vseq->addChild($sub_column);

        $block_props = [
            $this->column_elements['main']    => [
                'padding-bottom' => 'p-b-14',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
                'padding-top' => 'p-t-14',
            ],
            $this->column_elements['wrapper'] => [
                "border-radius" => "b-r-l",
                "background" => [
                    "layers" => [
                        [
                            "css" => "palette",
                            "name" => "black and white",
                            "type" => "palette",
                            "value" => "bg-wh",
                        ],
                    ],
                    "name" => "black and white",
                    "type" => "palette",
                    "value" => "bg-wh",
                ],
                "border-radius-corners" => [
                    "value" => "",
                    "type" => "separate",
                ],
                "flex-align" => "y-c",
            ],
        ];

        $inline_props = [
            $this->column_elements['wrapper'] => [
                "min-height" => [
                    "name" => "Parent height",
                    "type" => "parent",
                    "value" => "100%",
                ],
            ],
        ];

        if($category_data['id'] == 1) {
            $column_classes = 'st-12-mb st-4 st-4-lp st-12-tb';
        } else {
            $column_classes = 'st-12-mb st-4 st-4-lp st-6-tb';
        }

        return $this->createColumn(
            $column_classes,
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

    private function createTextBlock(string $html, string $tag, array $props): siteBlockData {
        $block = (new siteParagraphBlockType())->getEmptyBlockData();

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
            '<b>' . $title . '</b>',
            'h3',
            [
                'font-size'     => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-10',
                'margin-top'    => 'm-t-0',
                'align'         => 't-l',
                'font-header'   => 't-hdn',
            ]
        );
    }

    private function getCategoryText(string $text): siteBlockData {
        return $this->createTextBlock(
            $text,
            'p',
            [
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-8',
                'margin-top'    => 'm-t-a',
                'align'         => 't-l',
                'font-header'   => 't-rgl',
            ]
        );
    }
}
