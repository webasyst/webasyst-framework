<?php

class siteCustomCrmForm2BlockType extends siteBlockType
{
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
        $options['type'] = 'site.CustomCrmForm2';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getMainBlock();
        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        $hseq->addChild($this->getContent());
        $hseq->addChild($this->getForm());

        $result->addChild($hseq, '');

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

    private function getMainBlock(): siteBlockData
    {
        $result = $this->getEmptyBlockData();

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['main']    => [
                    'padding-bottom' => 'p-b-21 p-b-12-mb',
                    'padding-left'   => 'p-l-blc',
                    'padding-right'  => 'p-r-blc',
                    'padding-top'    => 'p-t-18 p-t-14-tb p-t-12-mb',
                    'background' => [
                        'name' => 'complementary',
                        'value' => 'bg-brn-a-9',
                        'type' => 'palette',
                        'scheme' => 'complementary',
                        'uuid' => 5,
                        'layers' => [
                            [
                                'name' => 'complementary',
                                'value' => 'bg-brn-a-9',
                                'type' => 'palette',
                                'scheme' => 'complementary',
                            ],
                        ],
                    ],
                ],
                $this->elements['wrapper'] => [
                    'flex-align' => 'y-c',
                    'max-width' => 'cnt',
                    'padding-bottom' => 'p-t-20 p-t-16-mb p-t-0-tb',
                    'padding-top'    => 'p-b-20 p-b-16-mb p-b-0-tb',
                ],
            ],
            'wrapper_props' => [
                'justify-align' => 'y-s',
            ],
            'elements'      => $this->elements,
        ];

        return $result;
    }

    private function getForm(): siteBlockData
    {
        $heading = $this->createHeading('Petitio', [
            'align' => 't-l',
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #4', 'value' => 't-4', 'unit' => 'px', 'type' => 'library'],
            'margin-top' => 'm-t-0',
            'margin-bottom' => 'm-b-12',
            'align' => 't-l',
            'margin-right' => 'm-r-a',
        ], 'h3');

        $sub_column_content = $this->createParagraph('<font color="" class="tx-bw-3">Responsum intra 2 horas</font>', [
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #7', 'value' => 't-7', 'unit' => 'px', 'type' => 'library',],
            'margin-top' => 'm-t-0',
            'margin-bottom' => 'm-b-0',
            'align' => 't-l',
        ]);
        $sub_column = $this->createSubColumn([
            'block_props' => [
                'padding-top' => 'p-t-3',
                'padding-bottom' => 'p-b-4',
                'padding-left' => 'p-l-9',
                'padding-right' => 'p-r-9',
                'background' => [
                    'name' => 'semi-transparent-black',
                    'value' => 'bg-b-opc-2',
                    'type' => 'palette',
                    'uuid' => 1,
                    'layers' => [
                        [
                            'name' => 'semi-transparent-black',
                            'value' => 'bg-b-opc-2',
                            'type' => 'palette',
                            'uuid' => 1,
                        ],
                    ],
                ],
                'border-radius' => 'b-r-m',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
            ],
        ], [$sub_column_content]);

        $vseq = $this->createSequence();

        $row = $this->createRow([
            'block_props' => [
                'padding-top' => 'p-t-4',
                'padding-bottom' => 'p-b-10',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
            ],
        ], [$heading, $sub_column]);
        $vseq->addChild($row);

        $form = (new siteFormBlockType(['form_type' => 'crm']))->getExampleBlockData();
        $form->data['block_props'] = ['margin-bottom' => 'm-b-0'];
        $vseq->addChild($form);

        $p = $this->createParagraph('Mensura et iudicium — liberum. <br>Ratio computationis: 4 dies laborales.', [
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #7', 'value' => 't-7', 'unit' => 'px', 'type' => 'library',],
            'margin-top' => 'm-t-16',
            'margin-bottom' => 'm-b-12',
            'align' => 't-c',
        ]);
        $vseq->addChild($p);

        $bg = [
            'name' => 'black and white',
            'value' => 'bg-wh',
            'type' => 'palette',
            'uuid' => 1,
        ];
        $column = $this->createColumn([
            'column' => 'st-6 st-6-lp st-12-tb st-12-mb',
            'block_props' => [
                $this->column_elements['main'] => [
                      'padding-top' => 'p-t-12-tb p-t-16 p-t-12-mb',
                      'padding-bottom' => 'p-b-16 p-b-0-tb p-b-12-mb',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'border-radius' => 'b-r-l',
                      'flex-align-vertical' => 'a-c-c',
                ],
                $this->column_elements['wrapper'] => [
                    'column-max-width' => 'fx-9',
                    'flex-align' => 'y-c',
                    'margin-left' => 'm-l-a',
                    'margin-right' => 'm-r-a',
                    'padding-top' => 'p-t-16 p-t-12-mb',
                    'padding-bottom' => 'p-b-20 p-b-12-mb',
                    'padding-left' => 'p-l-19 p-l-14-mb',
                    'padding-right' => 'p-r-19 p-r-14-mb',
                    'background' => $bg + [
                        'layers' => [$bg,],
                    ],
                    'border-radius' => 'b-r-l',
                ],
            ],
            'wrapper_props' => [
                'flex-align' => 'y-c',
            ],
        ], $vseq);

        return $column;
    }

    private function getContent() {
        $vseq = $this->createSequence();

        $heading = $this->createHeading('<font style="color: rgb(255, 255, 255);">Titulus Privatus<br></font><font color="#dc3f3f">-50% reductio </font>', [
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library',],
            'margin-top' => 'm-t-0',
            'margin-bottom' => 'm-b-12',
            'align' => 't-l',
        ], 'h3');
        $vseq->addChild($heading);

        $p = $this->createParagraph('<font color="" class="tx-w-opc-7">Quodlibet opus nascetur ex consilio. Nobis tuam fabulam narra — opus hoc in aes et lapide fixabimus.</font>', [
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
            'margin-top' => 'm-t-0',
            'margin-bottom' => 'm-b-16',
            'align' => 't-l',
        ], 'h3');
        $vseq->addChild($p);

        foreach ($this->getContentBottomList() as $item) {
            $vseq->addChild($item);
        }

        $vseq->addChild($this->getContentStats());

        $column = $this->createColumn([
            'column' => 'st-6 st-6-lp st-12-tb st-12-mb',
            'block_props' => [
                $this->column_elements['main'] => [
                    'padding-bottom' => 'p-t-12-tb p-t-16 p-t-8-mb',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                    'padding-top' => 'p-b-12-tb p-b-16 p-b-8-mb',
                    'border-radius' => 'b-r-l',
                    'flex-align-vertical' => 'a-c-c',
                ],
                $this->column_elements['wrapper'] => [
                    'column-max-width' => 'fx-9',
                    'flex-align' => 'y-c',
                    'margin-left' => 'm-l-a',
                    'margin-right' => 'm-r-a',
                    'padding-top' => 'p-t-0-mb p-t-8-tb',
                    'padding-bottom' => 'p-b-0-mb p-b-8-tb',
                    'padding-left' => 'p-l-19 p-l-0-mb p-l-12-tb',
                    'padding-right' => 'p-r-0-mb p-r-8-tb p-r-19',
                ],
            ],
            'wrapper_props' => [
                'flex-align' => 'y-l',
            ],
        ], $vseq);

        return $column;
    }

    private function getContentBottomList(): array
    {
        $data = [
            [
                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 19 19" fill="var(--white)"><g clip-path="url(#clip0_230_27981)"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.49785 0.489403L7.49453 4.12227C7.75271 4.592 7.59461 5.18175 7.13609 5.45936L5.75765 6.29395C5.29478 6.57419 5.13879 7.17178 5.40563 7.64251C6.10523 8.87662 6.93846 9.97711 7.90531 10.944C8.87217 11.9108 9.97266 12.7441 11.2068 13.4437C11.6775 13.7105 12.2751 13.5545 12.5554 13.0916L13.4114 11.6778C13.6802 11.2338 14.244 11.0692 14.7094 11.2989L17.8166 12.8326C18.2869 13.0647 18.498 13.6207 18.3002 14.1064L17.4293 16.2453C16.6961 18.0462 14.711 18.9913 12.8509 18.4251C9.49955 17.405 6.84563 15.9167 4.88911 13.9602C2.89131 11.9624 1.38171 9.23744 0.360304 5.78537C-0.169394 3.99514 0.609772 2.07657 2.2377 1.16259L4.13194 0.0990932C4.61351 -0.171282 5.22309 -6.92904e-05 5.49346 0.481507C5.49494 0.484132 5.4964 0.486764 5.49785 0.489403Z"></path></g><defs><clipPath id="clip0_230_27981"><rect width="19" height="19"></rect></clipPath></defs></svg>',
                'text' => '<font color="" class="tx-wh">+1 234 567 89 10</font>',
            ],
            [
                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 14" fill="var(--white)"><g clip-path="url(#clip0_1174_362679)"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 2L9.4855 7.6913C9.80219 7.88131 10.1978 7.88131 10.5145 7.6913L20 2V12C20 13.1046 19.1046 14 18 14H2C0.89543 14 0 13.1046 0 12V2ZM0 0.5C0 0.223858 0.223858 0 0.5 0L19.5 0C19.7761 0 20 0.223858 20 0.5C20 0.810199 19.8372 1.09765 19.5713 1.25725L10.5145 6.6913C10.1978 6.88131 9.80219 6.88131 9.4855 6.6913L0.428746 1.25725C0.162753 1.09765 0 0.810199 0 0.5Z"></path></g><defs><clipPath id="clip0_1174_362679"><rect width="20" height="14"></rect></clipPath></defs></svg>',
                'text' => '<font color="" class="tx-wh">fames@aliquip.nisi</font>',
            ],
            [
                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="var(--white)"><path d="M12 23C6.66667 16.6812 4 12.0367 4 9.06667C4 4.61157 7.58172 1 12 1C16.4183 1 20 4.61157 20 9.06667C20 12.0367 17.3333 16.6812 12 23ZM11.7727 11.2C13.4296 11.2 14.7727 9.85685 14.7727 8.2C14.7727 6.54315 13.4296 5.2 11.7727 5.2C10.1159 5.2 8.77273 6.54315 8.77273 8.2C8.77273 9.85685 10.1159 11.2 11.7727 11.2Z"></path></svg>',
                'text' => '<font color="" class="tx-wh">Vivamus, Viverra, Laculis 12, v. 34, l. 56</font>',
            ],
        ];

        $list = [];

        $row_props = [
            'block_props' => [
                'padding-top' => 'p-t-4',
                'padding-bottom' => 'p-b-4',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
                'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp n-wr-mb',
            ],
        ];
        $image_data = [
            'image' => [
                'color' => ['name' => 'Palette', 'type' => 'palette', 'value' => 'tx-wh',],
                'type'     => 'svg',
                'fill' => 'removed',
                'svg_html' => '',
            ],
            'block_props' => [
                'margin-bottom' => 'm-b-10',
                'picture-size' => 'i-m',
                'margin-right' => 'm-r-14',
                'margin-top' => 'm-t-2',
            ],
        ];
        $p_props = [
            'font-header' => 't-rgl',
            'font-size' => [
                'name' => 'Size #6',
                'value' => 't-6',
                'unit' => 'px',
                'type' => 'library',
            ],
            'margin-top' => 'm-t-0',
            'margin-bottom' => 'm-b-10',
            'align' => 't-l',
        ];
        foreach ($data as $item) {
            $image = (new siteImageBlockType())->getEmptyBlockData();
            $image_data['image']['svg_html'] = $item['svg_html'];
            $image->data = $image_data;

            $p = $this->createParagraph($item['text'], $p_props);

            $list[] = $this->createRow($row_props, [$image, $p]);
        }

        return $list;
    }

    private function getContentStats(): siteBlockData
    {
        $data = [
            ['heading' => '<font color="" class="tx-wh">1200+</font>', 'text' => '<font color="" class="tx-w-opc-7">opera</font>',],
            ['heading' => '<font color="" class="tx-wh">14 a.</font>', 'text' => '<font color="" class="tx-w-opc-7">in mercatu</font>',],
            ['heading' => '<font color="" class="tx-wh">97%</font>', 'text' => '<font class="tx-w-opc-7" color="">probant</font>',],
        ];

        $sub_column_props = [
            'block_props' => [
                'padding-top' => 'p-t-10',
                'padding-bottom' => 'p-b-10',
                'margin-right' => 'm-r-18 m-r-16-mb',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
            ],
        ];
        $heading_block_props = [
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #4', 'value' => 't-4', 'unit' => 'px', 'type' => 'library',],
            'margin-top' => 'm-t-0',
            'margin-bottom' => 'm-b-4',
            'align' => 't-c',
        ];
        $p_block_props = [
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #7', 'value' => 't-7', 'unit' => 'px', 'type' => 'library',],
            'margin-top' => 'm-t-0',
            'margin-bottom' => 'm-b-8',
            'align' => 't-c',
        ];

        $columns = [];
        foreach ($data as $item) {
            $heading = $this->createHeading($item['heading'], $heading_block_props, 'h3');
            $p = $this->createHeading($item['text'], $p_block_props);
            $columns[] = $this->createSubColumn($sub_column_props, [$heading, $p]);
        }

        $row = $this->createRow([
            'block_props' => [
                'padding-top' => 'p-t-8',
                'padding-bottom' => 'p-b-8',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
            ],
        ], $columns);

        return $row;
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

    private function createParagraph(string $text, array $block_props = [], $tag = 'p') {
        $paragraph = (new siteParagraphBlockType())->getEmptyBlockData();

        $paragraph->data = [
            'html'        => $text,
            'block_props' => $block_props,
            'tag'         => $tag,
        ];

        return $paragraph;
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

    private function createSubColumn(array $params, array $content): siteBlockData {
        $sub_column = (new siteSubColumnBlockType())->getExampleBlockData();

        $sub_column->data = $params;

        $vseq = reset($sub_column->children['']);

        foreach ($content as $item) {
            $vseq->addChild($item);
        }

        return $sub_column;
    }
}
