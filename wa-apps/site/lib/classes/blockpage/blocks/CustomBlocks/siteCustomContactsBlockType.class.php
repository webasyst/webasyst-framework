<?php

class siteCustomContactsBlockType extends siteBlockType {
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

    /**
     * Конструктор класса
     *
     * @param array $options
     */
    public function __construct(array $options = []) {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = 2;
        }
        $options['type'] = 'site.CustomContacts';
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

        // Добавляем колонку с картой
        $hseq->addChild($this->getMapColumn());

        // Добавляем колонку с информацией
        $hseq->addChild($this->getInfoColumn());

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
     * Получает свойства основного блока
     *
     * @return array
     */
    private function getMainBlockProps(): array {
        return [
            $this->elements['main']    => [
                'padding-top'    => 'p-t-18',
                'padding-bottom' => 'p-b-18',
                'padding-left' => 'p-l-blc',
                'padding-right' => 'p-r-blc',
            ],
            $this->elements['wrapper'] => [
                'padding-top'    => 'p-t-12',
                'padding-bottom' => 'p-b-12',
                'max-width'      => 'cnt',
                'flex-align'     => 'y-c',
            ],
        ];
    }

    /**
     * Создаёт колонку с информацией о контактах
     *
     * @return siteBlockData
     */
    private function getInfoColumn(): siteBlockData {
        // Создаём вертикальную последовательность для контента
        $vseq = $this->createSequence();

        // Добавляем заголовок
        $vseq->addChild($this->createHeadingBlock(
            'Praesent iaculis',
            'h2',
            [
                'font-size'     => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-14',
                'margin-top'    => 'm-t-0-mb m-t-4',
                'font-header'   => 't-hdn',
                'align'         => 't-l',
            ]
        ));

        // Добавляем текст
        $vseq->addChild($this->createHeadingBlock(
            'Aliquam erat sapien, vestibulum nec accumsan eu, molestie sit amet neque.',
            'h1',
            [
                'font-size'     => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-16',
                'margin-top'    => 'm-t-0',
                'font-header'   => 't-rgl',
                'align'         => 't-l',
            ]
        ));

        // Добавляем контактные элементы
        $contact_items_data = [
            [
                'text' => '+1 234 567 89 10',
                'icon' => '<svg viewBox="0 0 19 19" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_230_27981)"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.49785 0.489403L7.49453 4.12227C7.75271 4.592 7.59461 5.18175 7.13609 5.45936L5.75765 6.29395C5.29478 6.57419 5.13879 7.17178 5.40563 7.64251C6.10523 8.87662 6.93846 9.97711 7.90531 10.944C8.87217 11.9108 9.97266 12.7441 11.2068 13.4437C11.6775 13.7105 12.2751 13.5545 12.5554 13.0916L13.4114 11.6778C13.6802 11.2338 14.244 11.0692 14.7094 11.2989L17.8166 12.8326C18.2869 13.0647 18.498 13.6207 18.3002 14.1064L17.4293 16.2453C16.6961 18.0462 14.711 18.9913 12.8509 18.4251C9.49955 17.405 6.84563 15.9167 4.88911 13.9602C2.89131 11.9624 1.38171 9.23744 0.360304 5.78537C-0.169394 3.99514 0.609772 2.07657 2.2377 1.16259L4.13194 0.0990932C4.61351 -0.171282 5.22309 -6.92904e-05 5.49346 0.481507C5.49494 0.484132 5.4964 0.486764 5.49785 0.489403Z" /></g>
                <defs><clipPath id="clip0_230_27981"><rect width="19" height="19" fill="white"/></clipPath></defs>
            </svg>',
            ],
            [
                'text' => 'fames@aliquip.nisi',
                'icon' => '<svg viewBox="0 0 20 14" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_1174_362679)"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 2L9.4855 7.6913C9.80219 7.88131 10.1978 7.88131 10.5145 7.6913L20 2V12C20 13.1046 19.1046 14 18 14H2C0.89543 14 0 13.1046 0 12V2ZM0 0.5C0 0.223858 0.223858 0 0.5 0L19.5 0C19.7761 0 20 0.223858 20 0.5C20 0.810199 19.8372 1.09765 19.5713 1.25725L10.5145 6.6913C10.1978 6.88131 9.80219 6.88131 9.4855 6.6913L0.428746 1.25725C0.162753 1.09765 0 0.810199 0 0.5Z" /></g>
                <defs><clipPath id="clip0_1174_362679"><rect width="20" height="14" fill="white"/></clipPath></defs>
            </svg>',
            ],
            [
                'text' => 'Vivamus, Viverra, Laculis 12, v. 34, l. 56',
                'icon' => '<svg viewBox="0 0 24 24"  xmlns="http://www.w3.org/2000/svg">
                <path d="M12 23C6.66667 16.6812 4 12.0367 4 9.06667C4 4.61157 7.58172 1 12 1C16.4183 1 20 4.61157 20 9.06667C20 12.0367 17.3333 16.6812 12 23ZM11.7727 11.2C13.4296 11.2 14.7727 9.85685 14.7727 8.2C14.7727 6.54315 13.4296 5.2 11.7727 5.2C10.1159 5.2 8.77273 6.54315 8.77273 8.2C8.77273 9.85685 10.1159 11.2 11.7727 11.2Z" />
            </svg>',
            ],
        ];

        foreach ($contact_items_data as $item) {
            $vseq->addChild($this->createContactItem($item['text'], $item['icon']));
        }

        return $this->createColumn('st-6 st-6-lp st-6-tb st-12-mb', [
            $this->column_elements['main']    => [
                'padding-top'    => 'p-t-12 p-t-10-mb',
                'padding-bottom' => 'p-b-12 p-b-10-mb',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'padding-top'    => 'p-t-12',
                'padding-bottom' => 'p-b-12',
                'flex-align'     => 'y-c',
            ],
        ], $vseq);
    }

    /**
     * Создаёт колонку с картой
     *
     * @return siteBlockData
     */
    private function getMapColumn(): siteBlockData {
        $vseq = $this->createSequence();
        $vseq->addChild($this->createMapBlock());

        // Создаём колонку
        return $this->createColumn(
            'st-6 st-6-lp st-6-tb st-12-mb',
            [
                $this->column_elements['main']    => [
                    'padding-top'    => 'p-t-12 p-t-10-mb',
                    'padding-bottom' => 'p-b-12 p-b-10-mb',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                ],
                $this->column_elements['wrapper'] => [
                    'padding-top'    => 'p-t-12',
                    'padding-bottom' => 'p-b-12',
                    'flex-align'     => 'y-c',
                ],
            ],
            $vseq
        );
    }

    /**
     * Создаёт элемент контактной информации с иконкой
     *
     * @param string $text
     * @param string $icon
     * @return siteBlockData
     */
    private function createContactItem(string $text, string $icon): siteBlockData {
        $image = (new siteImageBlockType())->getEmptyBlockData();
        $image->data = [
            'image'       => [
                'type'     => 'svg',
                'svg_html' => $icon,
            ],
            'block_props' => [
                'margin-bottom' => 'm-b-8',
                'margin-right'  => 'm-r-14',
                'margin-top'    => (strpos($text, '@') !== false) ? 'm-t-6' : 'm-t-2',
                'picture-size'  => 'i-s',
            ],
        ];

        $text = $this->createHeadingBlock($text, 'p', [
            'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-4',
            'margin-top'    => 'm-t-0',
            'align'         => 't-l',
            'font-header'   => 't-rgl',
        ]);


        // Создаём ряд с изображением и текстом
        $row = (new siteRowBlockType())->getExampleBlockData();
        $row->data['block_props'] = ['padding-top' => 'p-t-4', 'padding-bottom' => 'p-b-4'];
        $row->data['wrapper_props'] = ['justify-align' => 'j-s', 'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp n-wr-mb'];
        $hseq = reset($row->children['']);
        $hseq->addChild($image);
        $hseq->addChild($text);

        return $row;
    }

    /**
     * Создаёт блок с заголовком
     *
     * @param string $html
     * @param string $tag
     * @param array  $props
     * @return \siteBlockData
     */
    private function createHeadingBlock(string $html, string $tag, array $props): siteBlockData {
        $heading = (new siteHeadingBlockType())->getEmptyBlockData();

        $heading->data = [
            'html'        => $html,
            'tag'         => $tag,
            'block_props' => $props,
        ];

        return $heading;
    }

    /**
     * Создаёт блок с картой
     *
     * @return siteBlockData
     */
    private function createMapBlock(): siteBlockData {
        $map = (new siteMapBlockType())->getEmptyBlockData();

        $map->data = [
            'html'        => '',
            'block_props' => [
                'border-radius' => 'b-r-l',
                'margin-bottom' => 'm-b-0-mb m-b-0-lp m-b-0',
            ],
        ];

        return $map;
    }

    /**
     * Создаёт колонку
     *
     * @param string $column_classes
     * @param array  $block_props
     * @return siteBlockData
     */
    private function createColumn(string $column_classes, array $block_props, siteBlockData $content): siteBlockData {
        $column = (new siteColumnBlockType())->getEmptyBlockData();

        $column->data = [
            'elements'       => $this->column_elements,
            'column'         => $column_classes,
            'block_props'    => $block_props,
            'indestructible' => false,
        ];

        $column->addChild($content, '');

        return $column;
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
     * Получает секции формы настроек блока
     *
     * @return array
     */
    private function getFormSections(): array {
        return [
            [
                'type' => 'ColumnsGroup',
                'name' => _w('Columns'),
            ],
            [
                'type' => 'RowsAlignGroup',
                'name' => _w('Columns alignment'),
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
        ];
    }
}
