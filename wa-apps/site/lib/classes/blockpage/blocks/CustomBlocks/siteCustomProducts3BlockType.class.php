<?php

class siteCustomProducts3BlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomProducts3';
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
        $hseq->addChild($this->getLinkColumn());

        // Создаём карточки товаров
        $cards = $this->getExampleCards();
        foreach ($cards as $card_data) {
            $hseq->addChild($this->getCardColumn($card_data));
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
     * Получает данные для примера карточек
     *
     * @return array
     * @throws \waException
     */
    private function getExampleCards(): array {
        $base_url = wa()->getAppStaticUrl('site') . 'img/blocks/products/';

        return [
            [
                'image_url'   => $base_url . 'product-09.jpg',
                'title'       => 'Morbi convallis',
                'description' => 'Phasellus porttitor, justo eu ultrices vulputate.',
                'btn_text'    => 'Sollicitudin lacus',
            ],
            [
                'image_url'   => $base_url . 'product-06.jpg',
                'title'       => 'Consec adipiscing',
                'description' => 'Suspendisse vulputate fermentu libero.',
                'btn_text'    => 'Sollicitudin lacus',
            ],
            [
                'image_url'   => $base_url . 'product-07.jpg',
                'title'       => 'Vestibulum et turpis',
                'description' => 'Praesent auctor purus luctus enim egestas.',
                'btn_text'    => 'Sollicitudin lacus',
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
    private function createColumn(string $column_classes, array $props, siteBlockData $content): siteBlockData {
        $column = (new siteColumnBlockType())->getEmptyBlockData();

        $column->data = array_merge($this->column_base_props, [
            'elements'    => $this->column_elements,
            'column'      => $column_classes,
            'block_props' => $props['block_props'] ?? [],
            'inline_props' => $props['inline_props'] ?? [],
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
                'flex-align-vertical' => 'a-c-c',
                'padding-top'         => 'p-t-0',
                'padding-bottom'      => 'p-b-0',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
            ],
        ];

        return $this->createColumn(
            'st-9-lp st-9-tb st-7-mb st-9',
            [
                'block_props' => $block_props,
            ],
            $vseq
        );
    }

    /**
     * Получает колонку со ссылкой
     *
     * @return siteBlockData
     */
    public function getLinkColumn(): siteBlockData {
        $vseq = $this->createSequence();
        $vseq->addChild($this->getLink());

        $block_props = [
            $this->column_elements['main']    => [
                'flex-align-vertical' => 'a-c-c',
                'padding-top'         => 'p-t-8',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
            ],
        ];

        return $this->createColumn(
            'st-3 st-3-lp st-3-tb st-5-mb',
            [
                'block_props' => $block_props,
            ],
            $vseq
        );
    }

    /**
     * Получает колонку с карточкой товара
     *
     * @param array $card_data
     * @return siteBlockData
     */
    public function getCardColumn(array $card_data): siteBlockData {
        $vseq = $this->createSequence();

        $vseq->addChild($this->getProductImage($card_data['image_url']));
        $vseq->addChild($this->getProductTitle($card_data['title']));
        $vseq->addChild($this->getProductDescription($card_data['description']));
        $vseq->addChild($this->getProductButton($card_data['btn_text']));

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
        $inline_props = [
            $this->column_elements['wrapper'] => [
                'min-height' => [
                    'name' => 'Fill parent',
                    'value' => '100%',
                    'type' => 'parent',
                ],
            ],
        ];

        return $this->createColumn(
            'st-12-mb st-4 st-4-lp st-4-tb',
            [
                'block_props' => $block_props,
                'inline_props' => $inline_props,
            ],
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
            'Viverra',
            'h1',
            array_merge($this->heading_base_props, $this->text_base_props, [
                'font-size'     => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-8',
                'max-width'     => 'fx-9',
            ])
        );
    }

    /**
     * Получает блок ссылки
     *
     * @return siteBlockData
     */
    private function getLink(): siteBlockData {
        return $this->createHeadingBlock(
            '<span class="tx-bw-1"><u>Sollic</u>&nbsp;→&nbsp;</span>',
            'h1',
            array_merge($this->text_base_props, [
                'margin-left'   => 'm-l-a m-l-0-mb',
                'margin-bottom' => 'm-b-8',
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
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
    private function getProductImage(string $image_url): siteBlockData {
        $imageBlock = (new siteImageBlockType())->getEmptyBlockData();

        $imageBlock->data = [
            'image'       => [
                'type'     => 'address',
                'url_text' => $image_url,
            ],
            'block_props' => [
                'margin-bottom' => 'm-b-14',
                'border-radius' => 'b-r-m',
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
    private function getProductTitle(string $title): siteBlockData {
        return $this->createHeadingBlock(
            $title,
            'h3',
            array_merge($this->heading_base_props, $this->text_base_props, [
                'font-size'     => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-10',
            ])
        );
    }

    /**
     * Получает блок описания товара
     *
     * @param string $description
     * @return siteBlockData
     */
    private function getProductDescription(string $description): siteBlockData {
        $descBlock = (new siteParagraphBlockType())->getEmptyBlockData();

        $descBlock->data = [
            'html'        => $description,
            'tag'         => 'p',
            'block_props' => array_merge($this->text_base_props, [
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-16',
            ]),
        ];

        return $descBlock;
    }

    /**
     * Получает блок кнопки товара
     *
     * @param string $btn_text
     * @return siteBlockData
     */
    private function getProductButton(string $btn_text): siteBlockData {
        $button = (new siteButtonBlockType())->getEmptyBlockData();

        $button->data = [
            'html'        => $btn_text,
            'block_props' => [
                'border-radius' => 'b-r-r',
                'button-size'   => 'inp-m p-l-13 p-r-13',
                'button-style'  => ['name' => 'Palette', 'value' => 'btn-blc-trnsp', 'type' => 'palette'],
                'margin-bottom' => 'm-b-12',
                'margin-top' => 'm-t-a',
            ],
        ];

        return $button;
    }
}
