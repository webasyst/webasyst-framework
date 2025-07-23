<?php

class siteCustomImagesWithDescription3BlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomImagesWithDescription3';
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
        $hseq->addChild($this->getImageColumn());
        $hseq->addChild($this->getHeadingColumn());
        $hseq->addChild($this->getDescriptionColumn());

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
            'padding-bottom' => 'p-b-20 p-b-12-mb',
            'padding-left'   => 'p-l-blc',
            'padding-right'  => 'p-r-blc',
            'padding-top'    => 'p-t-20 p-t-12-mb',
        ];
        $block_props[$this->elements['wrapper']] = [
            'flex-align' => 'y-c',
            'max-width' => 'cnt',
            'padding-bottom' => 'p-b-20 p-b-12-mb',
            'padding-top' => 'p-t-20 p-t-12-mb',
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
    public function getImageColumn(): siteBlockData {
        $vseq = $this->createSequence();
        $vseq->addChild($this->getImage());

        $block_props = [
            $this->column_elements['main']    => [
                'padding-bottom' => 'p-b-12',
                'padding-left'   => 'p-l-clm',
                'padding-right'  => 'p-r-clm',
                'padding-top'    => 'p-t-12',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
            ],
        ];

        return $this->createColumn(
            'st-12-mb st-12 st-12-lp st-12-tb',
            $block_props,
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
                'url_text' => wa()->getAppStaticUrl('site') . 'img/blocks/images_wd/youth.jpg',
            ],
            'block_props' => [
                'border-radius' => 'b-r-l',
                'margin-bottom' => 'm-b-14',
            ],
        ];

        return $imageBlock;
    }

    public function getHeadingColumn(): siteBlockData {
        $vseq = $this->createSequence();

        $vseq->addChild($this->createHeadingBlock(
            'Stylus sine finibus — nova tua forma',
            'h3',
            [
                'align'         => 't-l',
                'font-header'   => 't-hdn',
                'font-size'     => ['name' => 'Size #1', 'value' => 't-1', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-12',
                'margin-top'    => 'm-t-0',
            ]
        ));

        $block_props = [
            $this->column_elements['main']    => [
                'padding-bottom' => 'p-b-0',
                'padding-left'   => 'p-l-clm',
                'padding-right'  => 'p-r-clm',
                'padding-top'    => 'p-t-0',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
                'padding-bottom' => 'p-b-0',
                'padding-top'    => 'p-t-0',
            ],
        ];

        return $this->createColumn(
            'st-6 st-6-lp st-6-tb st-12-mb',
            $block_props,
            $vseq
        );
    }

    public function getDescriptionColumn(): siteBlockData {
        $vseq = $this->createSequence();

        $vseq->addChild($this->createHeadingBlock(
            'Crea singulares aspectus quotidie cum nostra collectione modernorum vestimentorum. Texta excellentia, forma considerata et novissimi cursus in quaque re. Commoditas et elegantia manum in manu procedunt in omni parte nostri vestiarii.',
            'p',
            [
                'align'         => 't-l',
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-12',
                'margin-top'    => 'm-t-0',
            ]
        ));

        $block_props = [
            $this->column_elements['main']    => [
                'padding-bottom' => 'p-b-0',
                'padding-left'   => 'p-l-clm',
                'padding-right'  => 'p-r-clm',
                'padding-top'    => 'p-t-12 p-t-4-tb',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
                'padding-bottom' => 'p-b-0',
                'padding-top'    => 'p-t-0',
            ],
        ];

        return $this->createColumn(
            'st-6 st-6-lp st-6-tb st-12-mb',
            $block_props,
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
}
