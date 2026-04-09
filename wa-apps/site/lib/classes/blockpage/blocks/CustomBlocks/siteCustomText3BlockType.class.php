<?php

class siteCustomText3BlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomText3';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        // Добавляем последовательности в основной блок
        $vseq = $this->createSequence();
        $vseq->addChild($this->getTitle());
        $vseq->addChild($this->getText('Explicamus cui talis machina utilis sit, quid ante emptionem spectandum sit et quae proprietates vere momenti sint in usu cotidiano.'));
        $vseq->addChild($this->getQuote());
        $vseq->addChild($this->getText('Talis machina praecipue convenit iis qui usum quotidianum simplicem et effectum constantem quaerunt. Non tantum de sapore agitur, sed etiam de celeritate, facilitate et consuetudine quae vitam matutinam leviorem reddit'));

        $column = $this->createColumn([
            'column' => 'st-12-tb st-12-mb st-8-lp st-7',
            'block_props' => [
                $this->column_elements['main'] => [
                    'padding-bottom' => 'p-b-12',
                    'padding-top' => 'p-t-12',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                ],
                $this->column_elements['wrapper'] => [
                    'flex-align' => 'y-c',
                ],
            ],
        ], $vseq);

        $hseq->addChild($column);

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['main']    => [
                    'padding-top'    => "p-t-12-mb p-t-16",
                    'padding-bottom' => "p-b-12-mb p-b-16",
                    'padding-left' => 'p-l-blc',
                    'padding-right' => 'p-r-blc',
                ],
                $this->elements['wrapper'] => [
                    'flex-align'     => "y-c",
                    'max-width'      => "cnt",
                    'padding-bottom' => "p-b-0-mb",
                    'padding-top'    => "p-t-0-mb",
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

    /**
     * Получает колонку с заголовком
     *
     * @return siteBlockData
     */
    public function getQuote(): siteBlockData {
        $img = (new siteImageBlockType())->getExampleBlockData();
        $img->data = [
            'block_props' => [
                'margin-bottom' => 'm-b-14',
                'margin-right' => 'm-r-16',
                'picture-size' => 'i-xl',
            ],
            'image' => [
                'type' => 'svg',
                'color' => ['name' => 'Palette','value' => 'tx-b-opc-3', 'type' => 'palette',],
                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="32" viewBox="0 0 40 32" fill="var(--b-opc-3)"><path d="M31.3911 29.6961C28.2551 29.6961 26.0151 28.6881 24.6711 26.6721C23.3271 24.5814 22.6551 22.3788 22.6551 20.0641C22.6551 18.1228 22.9537 16.1068 23.5511 14.0161C24.1484 11.8508 25.0817 9.79744 26.3511 7.8561C27.6204 5.91477 29.2631 4.34677 31.2791 3.1521C33.3697 1.95744 35.8711 1.36011 38.7831 1.36011C38.8577 1.36011 38.8577 1.65877 38.7831 2.25611C38.7831 2.85344 38.7831 3.4881 38.7831 4.1601C38.7831 4.75744 38.7831 5.05611 38.7831 5.05611C38.7831 5.05611 38.3724 5.20544 37.5511 5.50411C36.7297 5.72811 35.7591 6.21344 34.6391 6.96011C33.5191 7.63211 32.5484 8.60277 31.7271 9.8721C30.9057 11.1414 30.4951 12.7841 30.4951 14.8001C30.6444 14.7254 30.8311 14.6881 31.0551 14.6881C31.2791 14.6881 31.4657 14.6881 31.6151 14.6881C33.7804 14.6881 35.4977 15.3601 36.7671 16.7041C38.1111 18.0481 38.7831 19.7654 38.7831 21.8561C38.7831 24.1708 38.0737 26.0748 36.6551 27.5681C35.3111 28.9868 33.5564 29.6961 31.3911 29.6961ZM9.77506 29.6961C6.63906 29.6961 4.39906 28.6881 3.05506 26.6721C1.71106 24.5814 1.03906 22.3788 1.03906 20.0641C1.03906 18.1228 1.33773 16.1068 1.93506 14.0161C2.5324 11.8508 3.46573 9.79744 4.73506 7.8561C6.0044 5.91477 7.64706 4.34677 9.66306 3.1521C11.7537 1.95744 14.2551 1.36011 17.1671 1.36011C17.2417 1.36011 17.2417 1.65877 17.1671 2.25611C17.1671 2.85344 17.1671 3.4881 17.1671 4.1601C17.1671 4.75744 17.1671 5.05611 17.1671 5.05611C17.1671 5.05611 16.7564 5.20544 15.9351 5.50411C15.1137 5.72811 14.1431 6.21344 13.0231 6.96011C11.9031 7.63211 10.9324 8.60277 10.1111 9.8721C9.28973 11.1414 8.87906 12.7841 8.87906 14.8001C9.0284 14.7254 9.21506 14.6881 9.43906 14.6881C9.66306 14.6881 9.84973 14.6881 9.99906 14.6881C12.1644 14.6881 13.8817 15.3601 15.1511 16.7041C16.4951 18.0481 17.1671 19.7654 17.1671 21.8561C17.1671 24.1708 16.4577 26.0748 15.0391 27.5681C13.6951 28.9868 11.9404 29.6961 9.77506 29.6961Z"></path></svg>',
                'fill' => 'removed',
            ],
        ];

        $text = $this->createParagraph(
            'Bona machina domestica non est de “sicut in taberna coffeae”, sed de sapore constanti, celeritate et consuetudine usus quae matutino tempore non molestat.',
            [
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library',],
                'margin-top' => 'm-t-0',
                'margin-bottom' => 'm-b-10',
                'align' => 't-l',
            ]
        );

        $row = $this->createRow([
            'block_props' => [
                'padding-top' => 'p-t-14',
                'padding-bottom' => 'p-b-14',
                'padding-left' => 'p-l-16',
                'padding-right' => 'p-r-16',
                'border-radius' => 'b-r-l',
                'background' => [
                    'name' => 'grey shades',
                    'value' => 'bg-bw-7',
                    'type' => 'palette',
                    'uuid' => 1,
                    'layers' => [
                        ['name' => 'grey shades','value' => 'bg-bw-7','type' => 'palette','uuid' => 1,],
                    ],
                ],
                'margin-bottom' => 'm-b-16',
                'margin-top' => 'm-t-12',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
            ],
        ], [$img, $text]);

        return $row;
    }

    /**
     * Получает колонку с текстом
     *
     * @param array $text_data
     * @return siteBlockData
     */
    public function getText(string $text): siteBlockData {

        return $this->createParagraph(
            $text,
            [
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                'margin-top' => 'm-t-0',
                'margin-bottom' => 'm-b-14',
                'align' => 't-l',
            ]
        );
    }

    private function createParagraph(string $text, array $block_props = [], $tag = 'p') {
        $paragraph = (new siteParagraphBlockType())->getEmptyBlockData();

        $paragraph->data = [
            'html'        => $text,
            'block_props' => $block_props,
            'tag'         => $tag,
        ];

        return $paragraph;
    }


    private function getTitle() {
        $heading = (new siteHeadingBlockType())->getEmptyBlockData();

        $heading->data = [
            'html'        => 'Utrum machina automatica coffeae domui conveniat',
            'tag'         => 'h3',
            'block_props' => [
                'align'         => 't-l',
                'font-header'   => 't-hdn',
                'font-size'     => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-12',
                'margin-top'    => 'm-t-0',
            ],
        ];

        return $heading;
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

    /**
     * Создаёт ряд блоков
     *
     * @param array $props
     * @param array $content
     * @return siteBlockData
     */

     private function createRow(array $props, array $content): siteBlockData {
        $row = (new siteRowBlockType())->getExampleBlockData();
        $row->data['block_props'] = $props['block_props'] ?? [];
        $row->data['wrapper_props'] = $props['wrapper_props'] ?? [];
        $row->data['inline_props'] = $props['inline_props'] ?? [];

        $hseq = reset($row->children['']);

        foreach ($content as $item) {
            $hseq->addChild($item);
        }

        return $row;
    }
}
