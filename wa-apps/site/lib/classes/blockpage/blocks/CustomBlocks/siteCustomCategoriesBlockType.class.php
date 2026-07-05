<?php

class siteCustomCategoriesBlockType extends siteBlockType {
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

    /** @var array Стандартные настройки заголовков */
    private array $heading_base_props = [
        'font-header' => 't-hdn',
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
        $options['type'] = 'site.CustomCategories';
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
        $hseq->addChild($this->getHeadingColumn());

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
                'image_url' => $base_url . 'category-01.png',
                'title'     => 'Pellentesque',
            ],
            [
                'image_url' => $base_url . 'category-02.png',
                'title'     => 'Habitant',
            ],
            [
                'image_url' => $base_url . 'category-03.png',
                'title'     => 'Morbi',
            ],
            [
                'image_url' => $base_url . 'category-04.png',
                'title'     => 'Tristique',
            ],
            [
                'image_url' => $base_url . 'category-05.png',
                'title'     => 'Senectus',
            ],
            [
                'image_url' => $base_url . 'category-06.png',
                'title'     => 'Netus',
            ],
            [
                'image_url' => $base_url . 'category-07.png',
                'title'     => 'Vulputate',
            ],
            [
                'image_url' => $base_url . 'category-08.png',
                'title'     => 'Malesuada',
            ],
            [
                'image_url' => $base_url . 'category-09.png',
                'title'     => 'Hendrerit',
            ],
            [
                'image_url' => $base_url . 'category-10.png',
                'title'     => 'Mauris',
            ],
            [
                'image_url' => $base_url . 'category-11.png',
                'title'     => 'Egestas',
            ],
            [
                'image_url' => $base_url . 'category-12.png',
                'title'     => 'Scelerisque',
            ],
        ];
    }

    /**
     * Получает свойства основного блока
     *
     * @return array
     */
    private function getMainBlockProps(): array {
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
            'max-width'      => 'cnt',
            'flex-align'     => 'y-c',
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
    private function createColumn(string $column_classes, array $block_props, siteBlockData $content): siteBlockData {
        $column = (new siteColumnBlockType())->getEmptyBlockData();

        $column->data = array_merge($this->column_base_props, [
            'elements'    => $this->column_elements,
            'column'      => $column_classes,
            'block_props' => $block_props,
        ]);

        $column->addChild($content, '');

        return $column;
    }

    /**
     * Получает колонку с заголовком
     *
     * @return siteBlockData
     */
    public function getHeadingColumn(): siteBlockData {
        $vseq = $this->createSequence();
        $vseq->addChild($this->getTitle());

        $block_props = [
            $this->column_elements['main']    => [
                'padding-bottom' => 'p-b-0',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align'       => 'y-c',
                'column-max-width' => 'fx-6',
                'margin-left'      => 'm-l-a',
                'margin-right'     => 'm-r-a',
            ],
        ];

        return $this->createColumn(
            'st-12-mb st-12 st-12-lp st-12-tb',
            $block_props,
            $vseq
        );
    }

    /**
     * Получает колонку с карточкой товара
     *
     * @param array $card_data
     * @return siteBlockData
     */
    public function getCategoryColumn(array $category_data): siteBlockData {
        $vseq = $this->createSequence();

        $vseq->addChild($this->getCategoryImage($category_data['image_url']));
        $vseq->addChild($this->getCategoryTitle($category_data['title']));

        $block_props = [
            $this->column_elements['main']    => [
                'padding-top'    => 'p-t-12',
                'padding-bottom' => 'p-b-12',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align'     => 'y-c',
                'padding-top'    => 'p-t-12',
                'padding-bottom' => 'p-b-12',
            ],
        ];

        return $this->createColumn(
            'st-3-lp st-2 st-4-tb st-6-mb',
            $block_props,
            $vseq
        );
    }

    /**
     * Получает блок заголовка
     *
     * @return siteBlockData
     */
    private function getTitle(): siteBlockData {
        return $this->createHeadingBlock(
            'Ullamco laboris',
            'h1',
            array_merge($this->heading_base_props, [
                'font-size'     => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-18',
                'margin-top'    => 'm-t-0',
                'align'         => 't-c',
            ])
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
     * Получает блок изображения товара
     *
     * @param string $image_url
     * @return siteBlockData
     */
    private function getCategoryImage(string $image_url): siteBlockData {
        $imageBlock = (new siteImageBlockType())->getEmptyBlockData();

        $imageBlock->data = [
            'image'       => [
                'type'     => 'address',
                'url_text' => $image_url,
            ],
            'block_props' => [
                'margin-bottom' => 'm-b-14',
            ],
        ];

        return $imageBlock;
    }

    /**
     * Получает блок заголовка товара
     *
     * @param string $title
     * @return siteBlockData
     */
    private function getCategoryTitle(string $title): siteBlockData {
        return $this->createHeadingBlock(
            '<span class="tx-bw-2">' . $title . '</span><br>',
            'h1',
            [
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-12',
                'margin-top'    => 'm-t-0',
                'align'         => 't-c',
                'font-header'   => 't-rgl',
            ]
        );
    }
}
