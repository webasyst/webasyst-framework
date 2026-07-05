<?php

class siteCustomHero4BlockType extends siteBlockType {
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
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = 2;
        }
        $options['type'] = 'site.CustomHero4';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        // Добавляем последовательности в основной блок
        $hseq->addChild($this->getTextColumn());
        $hseq->addChild($this->getImageColumn());

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['wrapper'] => [
                    'flex-align'     => "y-c",
                ],
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
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

    public function getImageColumn(): siteBlockData {
        $vseq = $this->createSequence();

        $block_props = [
            $this->column_elements['main']    => [
                'flex-align-vertical' => 'a-c-s',
                'padding-bottom'      => 'p-b-30',
                'padding-top'         => 'p-t-30',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align'       => 'y-c',
            ],
        ];

        $inline_props = [
            $this->column_elements['main']    => [
                'background' => [
                    'type' => 'self_color',
                    'name' => 'Self color',
                    'value' => 'center center / cover url(' . wa()->getAppStaticUrl('site') . 'img/blocks/hero/sandals-2x.jpg)',
                    'layers' => [
                        [
                            'alignmentX' => 'center',
                            'alignmentY' => 'center',
                            'css' => '',
                            'file_name' => 'sandals-2x.jpg',
                            'file_url' => wa()->getAppStaticUrl('site') . 'img/blocks/hero/sandals-2x.jpg',
                            'name' => 'Image',
                            'space' => 'cover',
                            'type' => 'image',
                            'value' => 'center center / cover url(' . wa()->getAppStaticUrl('site') . 'img/blocks/hero/sandals-2x.jpg)',
                        ],
                    ],
                ],
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-6 st-6-lp st-12-mb st-12-tb',
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-l'],
                'inline_props'  => $inline_props,
            ],
            $vseq
        );
    }

    public function getTextColumn(): siteBlockData {
        $vseq = $this->createSequence();

        $vseq->addChild($this->getRateTextRow());

        // Заголовок
        $heading = $this->createHeading('<font color="" class="tx-wh">Sandaliae Sahara Elegantia</font>', [
            'align' => 't-l',
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-12',
            'margin-top' => 'm-t-0',
        ], 'h3');
        $vseq->addChild($heading);

        foreach ($this->getRowData() as $row_data) {
            $vseq->addChild($this->getListRow($row_data['svg'], $row_data['heading'], $row_data['text']));
        } 

        $vseq->addChild($this->getPriceTextRow());

        $vseq->addChild($this->getButtonsRow());

        $block_props = [
            $this->column_elements['main']    => [
                'flex-align-vertical' => 'a-c-c',
                'padding-bottom'      => 'p-b-23 p-b-16-mb',
                'padding-top'         => 'p-t-23 p-t-16-mb',
            ],
            $this->column_elements['wrapper'] => [
                'column-max-width' => 'fx-6',
                'flex-align' => 'y-c',
                'margin-left' => 'm-l-a m-l-a-tb',
                'margin-right' => 'm-r-a-tb',
                'padding-bottom' => 'p-b-14',
                'padding-left' => 'p-l-blc-clm',
                'padding-right' => 'p-r-blc-clm',
                'padding-top' => 'p-t-14',
            ],
        ];

        $inline_props = [
            $this->column_elements['main']    => [
                'background' => [
                    'layers' => [
                        [
                            'type' => 'self_color',
                            'value' => 'linear-gradient(#2f4326, #2f4326)',
                            'name' => 'Self color',
                            'css' => '#2f4326',
                        ],
                    ],
                    'name' => 'Self color',
                    'type' => 'self_color',
                    'value' => 'linear-gradient(#2f4326, #2f4326)',
                ],
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-6 st-6-lp st-12-mb st-12-tb',
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-l'],
                'inline_props'  => $inline_props,
            ],
            $vseq
        );
    }

    private function createHeading(string $text, array $block_props = [], $tag = 'h1') {
        $heading = (new siteHeadingBlockType())->getEmptyBlockData();

        $heading->data = [
            'html'        => $text,
            'block_props' => $block_props,
            'tag'         => $tag,
        ];

        return $heading;
    }

    private function createButton(string $text, array $block_props = [], $tag = 'a') {
        $button = (new siteButtonBlockType())->getEmptyBlockData();

        $button->data = [
            'html'        => $text,
            'block_props' => $block_props,
            'tag'         => $tag,
        ];

        return $button;
    }

    private function getImage($svg_html, $block_props = []) {
        $imageBlock = (new siteImageBlockType())->getEmptyBlockData();

        $imageBlock->data = [
            'image'       => [
                'type'     => 'svg',
                'svg_html' => $svg_html,
            ],
            'block_props' => $block_props,
        ];

        return $imageBlock;
    }

    private function getListRow($svg_html, $heading, $text) {

        $image = $this->getImage($svg_html, [
            'margin-right' => 'm-r-12 m-r-10-mb',
            'margin-top' => 'm-t-0-mb',
            'picture-size' => 'i-xxl',
        ]);

        $_heading = $this->createHeading($heading, [
            'align' => 't-l',
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-5',
            'margin-top' => 'm-t-0',
        ], 'p');

        $_text = $this->createHeading($text, [
            'align' => 't-l',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-8',
            'margin-top' => 'm-t-0',
        ]);

        $sub_column = $this->createSubColumn([
            'block_props' => [
                'padding-bottom' => 'p-b-8',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
            ],
        ], [$_heading, $_text]);

        $props = [
            'block_props' => [
                'padding-bottom' => 'p-b-4',
                'padding-top' => 'p-t-8',
            ],
            'wrapper_props' => [
                'flex-wrap' => 'n-wr-lp n-wr-tb n-wr-mb',
                'justify-align' => 'j-s',
            ],
        ];

        return $this->createRow($props, [$image, $sub_column]);
    }

    private function getRateTextRow() {
        $image = $this->getImage('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="auto" viewBox="0 0 24 24" fill="var(--white)"><path d="M12.5489 2.92705C12.8483 2.00574 14.1517 2.00574 14.4511 2.92705L16.3064 8.63729C16.4403 9.04931 16.8243 9.32827 17.2575 9.32827H23.2616C24.2303 9.32827 24.6331 10.5679 23.8494 11.1373L18.9919 14.6664C18.6415 14.9211 18.4948 15.3724 18.6287 15.7844L20.484 21.4947C20.7834 22.416 19.7289 23.1821 18.9452 22.6127L14.0878 19.0836C13.7373 18.8289 13.2627 18.8289 12.9122 19.0836L8.0548 22.6127C7.27108 23.1821 6.2166 22.416 6.51596 21.4947L8.37132 15.7844C8.5052 15.3724 8.35854 14.9211 8.00805 14.6664L3.15064 11.1373C2.36692 10.5679 2.7697 9.32827 3.73842 9.32827H9.74252C10.1757 9.32827 10.5597 9.04931 10.6936 8.63729L12.5489 2.92705Z"></path></svg>', ['margin-right' => "m-r-10", 'margin-top' => "m-t-1"]);

        $rate = $this->createHeading('<b><font color="" class="tx-wh">4.9</font></b>', [
            'align' => 't-l',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-8',
            'margin-right' => 'm-r-8',
            'margin-top' => 'm-t-0',
        ], 'p');

        $text = $this->createHeading('<font color="" class="tx-bw-5">281 aestimatio</font>', [
            'align' => 't-l',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-8',
            'margin-top' => 'm-t-0',
        ], 'p');

        $props = [
            'block_props' => [
                'padding-top' => 'p-t-10',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
            ],
        ];

        return $this->createRow($props, [$image, $rate, $text]);
    }

    private function getPriceTextRow() {
        $text1 = $this->createHeading('<font color="" class="tx-wh">ab</font>', [
            'align' => 't-l',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-0',
            'margin-right' => 'm-r-8',
            'margin-top' => 'm-t-8',
        ], 'p');

        $text2 = $this->createHeading('<b><font color="" class="tx-wh">49.99</font></b>', [
            'align' => 't-l',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #2', 'value' => 't-2', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-8',
            'margin-top' => 'm-t-0',
            'margin-right' => 'm-r-8',
        ], 'p');

        $text3 = $this->createHeading('<font class="tx-wh" color="">denariis/mensis</font>', [
            'align' => 't-l',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-0',
            'margin-top' => 'm-t-8',
        ], 'p');

        $props = [
            'block_props' => [
                'padding-top' => 'p-t-10',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
            ],
        ];

        return $this->createRow($props, [$text1, $text2, $text3]);
    }

    private function getButtonsRow() {
        $button1 = $this->createButton('Accipere in creditum', [
            'button-size' => 'inp-m p-l-13 p-r-13',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-wht', 'type' => 'palette'],
            'margin-bottom' => 'm-b-10',
            'margin-right' => 'm-r-10',
        ]);

        $button2 = $this->createButton('Emere in Mercatu →', [
            'button-size' => 'inp-m p-l-13 p-r-13',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-wht-trnsp', 'type' => 'palette'],
            'margin-bottom' => 'm-b-10',
            'margin-right' => 'm-r-10',
        ]);

        $button3 = $this->createButton('Emere in Silvestri Bacca →', [
            'button-size' => 'inp-m p-l-13 p-r-13',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-wht-trnsp', 'type' => 'palette'],
            'margin-bottom' => 'm-b-10',
            'margin-right' => 'm-r-10',
        ]);

        $button4 = $this->createButton('Emere in Amazone →', [
            'button-size' => 'inp-m p-l-13 p-r-13',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-wht-trnsp', 'type' => 'palette'],
            'margin-bottom' => 'm-b-10',
        ]);

        $props = [
            'block_props' => [
                'padding-top' => 'p-t-10',
                'padding-bottom' => 'p-b-10',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
            ],
        ];

        return $this->createRow($props, [$button1, $button2, $button3, $button4]);
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

    private function createSubColumn(array $params, $content): siteBlockData {
        $sub_column = (new siteSubColumnBlockType())->getExampleBlockData();
        $sub_column->data['block_props'] = $params['block_props'] ?? [];
        $sub_column->data['wrapper_props'] = $params['wrapper_props'] ?? [];

        $vseq = reset($sub_column->children['']);
 
         foreach ($content as $item) {
            $vseq->addChild($item);
        }

        return $sub_column;
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

    private function getRowData() {
        return [
            [
                'svg' => '<svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M28.2884 6.99609C29.1561 7.01192 30.1615 7.7844 30.9207 8.1978C34.5925 10.1964 38.3583 9.58336 42.2447 8.73964C42.4247 15.7952 43.5264 21.5083 47.25 27.5967C45.3909 33.153 44.0516 38.6596 43.1417 44.448C38.6868 44.4005 35.6326 44.8873 31.6893 47.0359C30.6717 47.5904 29.6287 48.5384 28.556 48.9C28.4523 48.9349 28.3464 48.9641 28.2415 48.9961C23.1704 46.4124 20.3374 45.7529 14.6802 45.695C13.4071 39.6055 10.9415 34.0676 8.75 28.2832C9.55863 26.8915 10.5691 25.638 11.3265 24.2042C13.7796 19.5626 14.365 14.206 15.0051 9.0721C15.6097 9.19191 16.2159 9.30308 16.8234 9.40598C21.1327 10.1191 24.7247 9.5848 28.2884 6.99609ZM30.1844 10.5721C29.667 10.1518 29.1525 9.66611 28.5718 9.33978C27.8512 9.79564 26.4972 10.4181 26.1384 11.1542C26.5366 11.3755 26.7345 11.319 27.1742 11.315C28.1746 11.079 29.2026 10.875 30.1844 10.5721ZM42.916 25.869C43.2079 26.6724 43.4155 27.3402 43.4956 28.2005C43.5278 28.5437 43.6198 28.8121 43.7386 29.133C44.1572 29.2813 44.3622 29.1312 44.773 29.0039C44.9991 28.3012 45.0528 27.8241 44.705 27.1473C44.4162 26.5853 43.8559 26.0978 43.2444 25.9356C43.1364 25.9071 43.0255 25.8913 42.916 25.869ZM39.7001 13.6098L40.0436 13.6019C40.2883 13.2673 40.5151 12.7467 40.4683 12.3138C40.4157 11.8292 40.3051 11.7184 39.9645 11.4277L39.1111 11.4118C38.5104 11.4715 38.0209 11.487 37.5007 11.8317C37.9726 12.6225 38.9755 12.1465 39.7001 13.6098ZM14.1596 35.8723C14.5381 35.4729 14.6254 35.4639 14.6161 34.8919C14.6032 34.0899 14.2834 33.4203 13.9209 32.7238C13.7045 32.5745 13.493 32.4158 13.2633 32.2877C13.0579 32.4676 12.7742 32.5928 12.7638 32.8868C12.733 33.7632 13.5617 35.2477 14.1596 35.8723ZM16.7479 12.1929C16.5744 13.445 16.213 14.8737 16.6481 16.0949L16.8374 16.1078C16.9676 15.9409 17.1662 15.7362 17.2524 15.5448C17.4567 14.8802 17.6492 14.2232 17.9254 13.5846C18.0814 13.2237 18.1061 13.0201 18.0403 12.6211C17.6249 12.284 17.2674 12.2879 16.7479 12.1929ZM13.9996 26.5706C14.5739 25.6229 15.1611 24.7047 15.2305 23.5681L15.0798 23.2375L14.5778 23.1375C14.0386 23.9495 13.3735 24.75 13.2307 25.7344C13.4279 26.2302 13.5531 26.2644 13.9996 26.5706ZM41.9996 38.7863C42.2666 38.0159 42.7027 37.1528 42.526 36.3346C42.1574 35.9903 42.0186 36.0011 41.5155 35.9054C41.3484 36.6736 40.9395 37.6788 41.1298 38.4509C41.4261 38.7193 41.6093 38.7402 41.9996 38.7863ZM28.668 46.5142L28.8387 45.886C28.5868 45.4885 28.3807 45.2999 27.9123 45.1704C27.2 44.974 26.4171 44.9455 25.685 45.0031C26.3613 45.5874 27.2043 46.5437 28.0494 46.7877C28.2501 46.7021 28.4798 46.6233 28.668 46.5142ZM42.8992 30.9378L42.4437 32.6712C42.3227 33.1221 42.3145 33.3919 42.4208 33.8467C42.6934 34.0057 42.9077 34.0841 43.2158 34.1511C43.3697 33.6074 43.5292 33.0656 43.6938 32.5252C43.8359 32.0596 43.857 31.816 43.7368 31.3235C43.457 31.1353 43.2205 31.0371 42.8992 30.9378ZM32.099 11.0304C32.3319 11.6396 32.3977 11.7561 32.9988 12.0091C33.757 12.3282 35.0014 12.5311 35.7929 12.3264L35.5997 11.7277C34.5356 11.1942 33.2672 11.1654 32.099 11.0304ZM23.3998 44.6509C23.1611 44.0349 23.0194 43.8032 22.4069 43.5398C21.6838 43.229 20.7878 43.1635 20.0128 43.0491C20.2479 43.6834 20.3585 43.9144 20.9771 44.195C21.6902 44.5181 22.6223 44.7113 23.3998 44.6509ZM16.1561 21.3622C16.4781 20.5221 16.9905 19.5741 16.8645 18.6634C16.5658 18.2968 16.3575 18.277 15.9196 18.1227C15.6602 19.0038 15.1485 20.0922 15.2663 20.9985C15.5944 21.3212 15.7196 21.2766 16.1561 21.3622ZM18.2879 43.7953C18.1072 43.2175 17.4467 42.8321 16.9214 42.5425C16.637 42.3853 16.3432 42.2813 16.0269 42.2147C16.1235 42.7943 16.1468 43.4949 16.5797 43.9241C17.1744 44.086 17.7022 43.9205 18.2879 43.7953ZM14.6719 37.5903C14.7775 38.4905 14.9289 40.0794 15.8044 40.5928C16.0774 40.7525 16.4631 40.7414 16.7661 40.7561C16.472 39.8772 16.155 38.3261 15.3299 37.8084C15.1299 37.6828 14.8995 37.6328 14.6719 37.5903ZM39.3752 15.4303C39.5398 16.5569 39.5838 17.7823 40.1784 18.7728C40.474 18.8261 40.6897 18.8264 40.9874 18.784C40.9216 18.1079 40.8554 17.4387 40.8286 16.7598C40.8114 16.3162 40.703 16.0481 40.5098 15.6527C40.0793 15.4016 39.8647 15.4393 39.3752 15.4303ZM20.9807 12.7136C21.9278 12.6355 23.5357 12.7218 24.2055 11.9958L24.1068 11.5284L23.857 11.4388C22.8717 11.5136 21.7975 11.4953 20.8758 11.8742L20.6909 12.1562C20.7789 12.3559 20.8469 12.5398 20.9807 12.7136ZM11.3873 27.8486C11.1566 28.5261 11.0202 29.2493 11.3755 29.9199C11.6289 30.3981 11.8743 30.5913 12.3591 30.7992C12.6722 29.9228 12.8361 29.0193 12.4711 28.1382C12.0639 27.8018 11.9019 27.8662 11.3873 27.8486ZM35.6494 42.1323C34.6329 42.4576 33.4418 42.7436 32.6979 43.5546L32.6739 43.8773L33.0232 44.1536C33.9906 43.81 35.0712 43.5272 35.9417 42.9768L36.063 42.6767C35.9235 42.4108 35.9092 42.2957 35.6494 42.1323ZM40.3359 20.7959C40.8028 22.0628 41.1849 23.2551 42.1249 24.2575C42.3639 24.346 42.5535 24.4259 42.809 24.4482C42.5564 23.6379 42.0494 21.3043 41.2933 20.9524C40.9738 20.8038 40.6815 20.8139 40.3359 20.7959ZM40.7112 40.8979C39.7892 41.0717 38.7705 41.1663 37.9393 41.6186L37.8255 41.9028C38.0302 42.2111 38.1522 42.3302 38.4846 42.4914C39.4088 42.4047 40.348 42.4752 41.0597 41.8276C41.1588 41.3465 41.2025 41.3555 40.9474 40.945L40.7112 40.8979Z" fill="white"/></svg>',
                'heading' => '<font color="" class="tx-wh">Corium naturale</font>',
                'text' => '<font color="" class="tx-bw-5">Molle et durabile pro commoditate totius diei.</font>',
            ],
            [
                'svg' => '<svg width="56" height="56" viewBox="0 0 58 58" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.41209 19.5469C8.39415 19.5091 8.37373 19.4723 8.35846 19.4332C7.33213 16.834 6.70389 13.3291 7.90719 10.685C8.59908 9.16485 9.80265 8.15009 11.3548 7.57791C12.8265 7.03552 14.546 7.32882 15.9269 7.98368C18.775 9.33426 18.688 12.1185 18.7228 14.8476C18.7298 15.4015 18.6699 16.119 18.9238 16.6242C19.0856 16.946 19.156 17.0117 19.4937 17.0846C19.9876 16.5129 19.9421 11.8759 20.126 10.7681L20.1624 10.5573C22.681 12.8419 25.4595 15.2096 27.155 18.1979C28.1573 19.9643 28.7236 21.9034 29.1659 23.8756C29.4212 25.0129 29.5903 29.247 30.2172 29.8455L30.5098 29.8345C31.4067 28.3063 29.942 22.3004 29.585 20.3407C33.0966 23.5232 39.2421 27.531 41.6667 31.2795C42.4713 32.5235 42.9357 33.9176 43.5356 35.2641C43.7514 34.1205 43.9974 32.6328 45.0003 31.9123C45.3833 31.8494 45.4079 31.8497 45.7559 32.0193C46.022 32.8941 45.9119 34.1337 45.9619 35.0616C46.1532 38.6261 47.2366 43.1207 50.7092 44.8462C51.7275 45.3524 53.278 45.8538 54.0412 46.7145C54.3141 47.0221 54.3858 47.3917 54.3737 47.7923C54.3586 48.3092 54.2138 48.7393 53.8209 49.0865C52.9451 49.2989 51.828 48.47 51.0812 48.0642C49.9711 47.5048 48.8162 46.5261 47.6051 46.2462C43.7596 45.3578 41.1948 50.8636 38.4568 50.7943C38.1035 50.595 38.0346 50.5519 37.8272 50.2076C37.8118 49.0807 38.9956 48.3329 39.4691 47.3754L39.329 47.2414C38.6235 47.318 37.9171 47.3845 37.2099 47.441C31.9335 47.8231 27.6823 45.6722 23.794 42.3204C26.5306 43.2731 31.9273 44.8128 34.722 44.1818C35.1291 44.0898 35.2577 43.9914 35.4729 43.6449L35.3694 43.4418C34.2488 43.1222 32.9472 43.2038 31.7826 43.1048C30.3382 42.9753 28.9095 42.7138 27.5137 42.3231C25.6623 41.8027 23.4178 41.079 21.8013 40.0285C18.2876 37.7456 14.4729 31.3686 12.3857 27.6444C15.1955 29.2549 18.6788 30.4061 21.8923 30.788C22.529 30.8639 23.7915 31.1656 24.3038 30.7627L24.3176 30.4822C24.0646 30.1607 23.9624 30.1474 23.5654 30.0298C22.4763 29.707 21.2376 29.6603 20.1176 29.4407C18.0807 29.0413 15.8727 28.1742 14.0017 27.2958C10.372 25.5914 9.67439 23.0918 8.41209 19.5469ZM44.8096 42.5902C37.0576 38.8133 30.1311 33.5422 24.4266 27.0795C22.8807 25.3518 21.3371 23.5271 20.0177 21.6218C18.735 19.7683 17.5135 17.8732 16.355 15.9397C15.5988 14.6526 14.9215 13.2758 14.0937 12.0366C13.8655 11.6949 13.6384 11.3309 13.2657 11.1357L13.1157 11.1926C13.0493 11.7795 13.1561 12.1527 13.3651 12.6969C19.0046 23.6137 27.0456 33.147 37.4588 39.7902C39.168 40.8807 41.5918 42.4811 43.4828 43.1148C44.0637 43.3093 44.8071 43.5504 45.4125 43.408L45.5672 43.201C45.3274 42.9948 45.0855 42.7435 44.8096 42.5902Z" fill="white"/></svg>',
                'heading' => '<font color="" class="tx-wh">Solea levis</font>',
                'text' => '<font class="tx-bw-5" color="">Minuit pondus in pede etiam in longis ambulationibus.</font>',
            ],
            [
                'svg' => '<svg width="56" height="57" viewBox="0 0 56 57" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M22.1554 11.4707C19.9499 11.7682 18.0328 14.5281 16.1037 17.1901C14.5547 19.3277 13.4739 21.8577 12.9547 23.2322C12.4356 24.6067 11.9536 25.9723 11.528 27.3833C9.58211 27.2098 2.02556 26.2694 0.539358 27.0916C0.162375 27.3001 0.127069 27.3785 0 27.7759C0.10232 28.1476 -0.0582619 29.7943 0.165091 30.1122C0.409269 30.4593 3.11215 30.4873 3.5 30.5088C10.1966 30.8812 14.7012 31.5626 21.3421 32.5373C26.1571 33.3108 31.0452 35.1213 35.875 35.7588C41.1141 36.4507 46.3614 35.9586 51.625 35.7588C52.7393 35.7165 54.3561 35.4464 55.2378 34.9092C56 33.1338 55.9957 32.9317 55.9996 32.7383C56.0077 32.365 55.8961 32.0509 55.6652 31.78C54.1542 31.3516 49.6105 31.7211 47.7781 31.7551C47.8798 29.7949 47.9903 27.8383 48.0036 25.8746C45.7685 25.0684 43.4236 24.7322 41.1913 23.8706C34.3785 21.2413 27.7313 16.2977 22.1554 11.4707ZM40.9076 31.7208C39.1842 31.6135 36.4943 31.6821 34.8837 31.1281C29.3044 30.4791 23.8236 28.9759 18.2509 28.226C19.151 24.8315 22.4261 18.4477 25.269 16.5984C30.1526 20.1018 35.5668 24.2811 41.2882 26.0395C41.2233 27.9404 41.067 29.8269 40.9076 31.7208Z" fill="white"/><path d="M3.09524 30.1955C2.12637 30.1036 1.13788 30.1247 0.165091 30.1122C0.481407 32.7178 0.803456 35.0632 1.93169 37.4402C4.07074 37.7485 6.35014 37.6102 8.51153 37.6179C11.1649 37.6271 13.8454 37.7223 16.4937 37.5516L15.7744 34.0466C17.5431 34.0584 19.3342 34.3924 21.0836 34.6517C23.7668 35.0497 26.4718 35.4141 29.136 35.9373C31.4061 36.3833 33.6577 36.9753 35.9443 37.3201C40.1087 37.948 44.4728 37.6717 48.6715 37.5865C50.3128 37.5324 51.9581 37.5189 53.5838 37.2522C54.3767 36.5571 54.8043 35.9014 55.2378 34.9092C48.8414 34.8947 42.5625 35.4525 36.1641 34.7459C31.9979 34.2854 27.9248 33.2532 23.7955 32.5373C16.9503 31.2834 10.0358 30.5009 3.09524 30.1955Z" fill="white"/></svg>',
                'heading' => '<font color="" class="tx-wh">Forma elegans</font>',
                'text' => '<font class="tx-bw-5" color="">Pro litore, urbe et vespertinis egressibus.</font>',
            ],
        ];
    }

}
