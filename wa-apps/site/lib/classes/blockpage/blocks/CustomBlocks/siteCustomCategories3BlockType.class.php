<?php

class siteCustomCategories3BlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomCategories3';
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
        $hseq->addChild($this->getColumn());

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
        return [
            $this->elements['main'] => [
                'padding-bottom' => 'p-b-20 p-b-16-mb',
                'padding-left' => 'p-l-blc',
                'padding-right' => 'p-r-blc',
                'padding-top' => 'p-t-20 p-t-16-mb',
            ],
            $this->elements['wrapper'] => [
                'flex-align' => 'y-c',
                'max-width'  => 'cnt',
            ],
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
     * 
     * @return siteBlockData
     */
    public function getColumn(): siteBlockData {
        $vseq = $this->createSequence();
        $vseq->addChild($this->getTitle());

        $buttons_title = ['Libri', 'Vestimenta', 'Arma', 'Textilia', 'Vasa', 'Olea', 'Ornamenta', 'Vinum', 'Nummi', 'Chartae', 'Ludi', 'Musica', 'Ars', 'Instrumenta', 'Dona'];

        $buttons = [];
        foreach ($buttons_title as $title) {
            $buttons[] = $this->createButton($title);
        }
        $props = [
            'block_props' => [
                'padding-bottom' => 'p-b-10',
                'padding-top' => 'p-t-10',
            ],
            'wrapper_props' => [
                'justify-align' => 'y-j-cnt',
            ],
        ];
        $vseq->addChild($this->createRow($props, $buttons));


        $block_props = [
            $this->column_elements['main']    => [
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align'       => 'y-c',
            ],
        ];

        return $this->createColumn(
            "st-12-mb st-8-lp st-8 st-12-tb",
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
            "Catalogus mercium",
            'h3',
            array_merge($this->heading_base_props, [
                'font-size'     => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-12',
                'margin-top'    => 'm-t-0',
                'align'         => 't-c',
                'font-header'   => 't-hdn',
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

    private function createButton(string $text) {
        $button = (new siteButtonBlockType())->getEmptyBlockData();

        $button->data = [
            'html'        => $text,
            'block_props' => [
                'margin-bottom' => 'm-b-12',
                'margin-right' => 'm-r-12',
                'button-size' => 'inp-m p-l-13 p-r-13',
                'button-style' => ['name' => 'Palette', 'value' => 'btn-blc-trnsp', 'type' => 'palette'],
            ],
            'tag'         => 'a',
        ];

        return $button;
    }

    private function createRow(array $props, array $content): siteBlockData {
        $row = (new siteRowBlockType())->getExampleBlockData();
        $row->data['block_props'] = $props['block_props'] ?? [];
        $row->data['wrapper_props'] = $props['wrapper_props'] ?? [];

        $hseq = reset($row->children['']);

        foreach ($content as $item) {
            $hseq->addChild($item);
        }

        return $row;
    }
}
