<?php

class siteCustomCrmFormBlockType extends siteBlockType
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
        $options['type'] = 'site.CustomCrmForm';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getMainBlock();
        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        $hseq->addChild($this->getForm());
        $hseq->addChild($this->getContent());

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

        $bg_image = wa()->getAppStaticUrl('site').'img/blocks/apps/fitness-coach-wide.png';

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['main']    => [
                    'padding-bottom' => 'p-b-20-tb p-b-16-mb p-b-21',
                    'padding-left'   => 'p-l-blc',
                    'padding-right'  => 'p-r-blc',
                    'padding-top'    => 'p-t-20-tb p-t-21 p-t-14-mb',
                ],
                $this->elements['wrapper'] => [
                    'flex-align' => 'y-c',
                    'max-width' => 'cnt',
                    'padding-bottom' => 'p-t-20 p-t-16-mb',
                    'padding-top'    => 'p-b-20 p-b-16-mb',
                ],
            ],
            'inline_props' => [
                $this->elements['main'] => [
                    'background' => [
                        'type' => 'self_color',
                        'value' => 'linear-gradient(#000000a6, #000000a6), center center / cover url('.$bg_image.')',
                        'name' => 'Self color',
                        'layers' => [
                            [
                                'type' => 'self_color',
                                'value' => 'linear-gradient(#000000a6, #000000a6)',
                                'name' => 'Self color',
                                'css' => '#000000a6',
                            ],
                            [
                                'type' => 'image',
                                'value' => 'center center / cover url('.$bg_image.')',
                                'alignmentX' => 'center',
                                'alignmentY' => 'center',
                                'file_name' => 'fitness-coach-wide.png',
                                'file_url' => $bg_image,
                                'space' => 'cover',
                                'name' => 'Image',
                            ],
                        ],
                        'uuid' => 5,
                    ],
                ],
            ],
            'wrapper_props' => [
                'justify-align' => 'y-j-cnt',
            ],
            'elements'      => $this->elements,
        ];

        return $result;
    }

    private function getForm(): siteBlockData
    {
        $vseq = $this->createSequence();

        $heading = $this->createHeading('Postulatio de selectione alimentōrum', [
            'align' => 't-l',
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-16',
            'margin-top' => 'm-t-0',
        ], 'h3');
        $vseq->addChild($heading);

        $form = (new siteFormBlockType(['form_type' => 'crm']))->getExampleBlockData();
        $form->data['block_props'] = ['margin-bottom' => 'm-b-0'];
        $vseq->addChild($form);

        $bg = [
            'name' => 'black and white',
            'type' => 'palette',
            'uuid' => 1,
            'value' => 'bg-wh',
        ];
        $column = $this->createColumn([
            'column' => 'st-6 st-6-lp st-6-tb st-12-mb',
            'block_props' => [
                $this->column_elements['main'] => [
                    'flex-align-vertical' => 'a-c-c',
                    'padding-bottom' => 'p-b-12',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                    'padding-top' => 'p-t-12',
                    'border-radius' => 'b-r-l',
                ],
                $this->column_elements['wrapper'] => [
                    'background' => $bg + [
                        'layers' => [$bg],
                    ],
                    'border-radous' => 'b-r-l',
                    'column-max-width' => 'fx-9',
                    'flex-align' => 'y-c',
                    'margin-left' => 'm-l-a',
                    'margin-right' => 'm-r-a',
                    'padding-top' => 'p-t-16 p-t-12-mb',
                    'padding-bottom' => 'p-b-16 p-b-14-mb',
                    'padding-left' => 'p-l-16 p-l-12-mb',
                    'padding-right' => 'p-r-16 p-r-12-mb',
                ],
            ],
            'inline_props' => [
                $this->column_elements['wrapper'] => [
                    'min-height' => [
                        'name' => 'Content',
                        'type' => 'content',
                        'value' => 'none',
                    ],
                ]
            ],
            'wrapper_props' => [
                'flex-align' => 'y-l',
            ],
        ], $vseq);

        return $column;
    }

    private function getContent() {
        $vseq = $this->createSequence();

        $heading = $this->createHeading('<font color="" class="tx-wh">ELECTIO PERSONALIS</font>', [
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
            'margin-top' => 'm-t-0',
            'margin-bottom' => 'm-b-0',
            'align' => 't-l',
        ], 'h3');
        $sub_column_heading = $this->createSubColumn([
                'block_props' => [
                'padding-top' => 'p-t-4',
                'padding-bottom' => 'p-b-6',
                'padding-left' => 'p-l-9',
                'padding-right' => 'p-r-9',
                'background' => [
                    'name' => 'semi-transparent-white',
                    'value' => 'bg-w-opc-5',
                    'type' => 'palette',
                    'uuid' => 1,
                    'layers' => [
                        [
                            'name' => 'semi-transparent-white',
                            'value' => 'bg-w-opc-5',
                            'type' => 'palette',
                            'uuid' => 1,
                        ],
                    ],
                ],
                'border-radius' => 'b-r-m',
                    'margin-bottom' => 'm-b-10',
                ],
                'wrapper_props' => [
                    'justify-align' => 'j-s',
                ],
            ], [$heading]);
        $vseq->addChild($sub_column_heading);

        $heading = $this->createHeading('<font color="" class="tx-wh">Accipe programma ad tuos eventus</font>', [
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library',],
            'margin-top' => 'm-t-0',
            'margin-bottom' => 'm-b-12',
            'align' => 't-l',
        ], 'h3');
        $vseq->addChild($heading);

        $p = $this->createParagraph(
            '<font color="" class="tx-w-opc-7">Implē formam — noster magistēr corporis te monēbit qualia alimenta et vīres ad tuōs fīnēs et pecuniam tuam conveniunt.</font>',
            [
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library',],
                'margin-top' => 'm-t-0',
                'margin-bottom' => 'm-b-16',
                'align' => 't-l',
            ]
        );
        $vseq->addChild($p);

        foreach ($this->getContentBottomList() as $item) {
            $vseq->addChild($item);
        }

        $column = $this->createColumn([
            'column' => 'st-6 st-6-lp st-6-tb st-12-mb',
            'block_props' => [
                $this->column_elements['main'] => [
                    'padding-bottom' => 'p-b-12-tb p-b-16',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                    'padding-top' => 'p-t-12-tb p-t-16',
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
                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150" fill="var(--white)"><path d="M73.7084 119.66C77.754 121.168 77.5599 121.401 79.0961 125.079C78.8262 129.037 78.9767 128.908 77.0316 132.28C73.1525 131.354 73.1343 131.001 71.6371 127.757C71.7505 123.593 71.828 123.46 73.7084 119.66Z"></path><path d="M72.519 36.4493C75.3891 36.8571 75.3998 36.6235 77.5688 38.4454C80.2405 44.3918 78.9156 65.5321 78.809 73.4737C84.4625 78.803 90.7793 84.6066 95.9018 90.4259C98.7131 93.6189 98.1033 94.1814 97.808 97.7315C90.5702 98.2558 77.0675 81.8833 71.767 76.3917C71.7132 67.071 71.1401 44.6001 72.519 36.4493Z"></path><path d="M20.0961 71.3868C23.7075 71.3095 26.3556 70.7776 29.3236 72.6427C30.5538 75.7379 30.1418 74.8822 29.6147 78.6358C25.8117 78.6496 23.9357 78.9846 20.5336 77.5372C18.928 74.4715 19.392 75.551 20.0961 71.3868Z"></path><path d="M120.957 71.4015C124.434 71.3408 126.593 70.9735 129.742 72.4386C131.434 75.6941 131 74.417 130.388 78.6534C126.749 78.6231 124.123 79.0341 121.051 77.2198C119.717 73.7392 120.003 75.5923 120.957 71.4015Z"></path><path d="M72.5229 17.8556C75.4703 18.28 75.85 18.0549 78.0717 20.0616C79.6452 23.2109 78.7218 26.3668 78.2162 29.9435C73.8539 29.3459 75.5317 30.2484 72.5238 27.9737C71.0061 24.711 71.9693 21.4987 72.5229 17.8556Z"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M66.3246 7.46982C103.608 2.52591 137.839 28.7431 142.786 66.0284C147.731 103.314 121.518 137.549 84.2357 142.5C46.9505 147.449 12.7144 121.228 7.76798 83.9395C2.82274 46.6514 29.0395 12.4147 66.3246 7.46982ZM135.891 69.1788C132.663 35.7688 103.014 11.2672 69.5971 14.3936C36.036 17.5331 11.4071 47.3396 14.6498 80.8946C17.8917 114.45 47.7694 138.988 81.3109 135.645C114.709 132.314 139.119 102.589 135.891 69.1788Z"></path></svg>',
                'text' => '<font color="" class="tx-wh">Responsum post hōrās quartam</font>',
            ],
            [
                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150" fill="var(--white)"><path d="M40.9776 110.601C50.712 110.36 74.0588 109.016 82.1553 111.534C84.5659 114.347 83.7747 112.651 83.6339 117.195C74.7567 117.73 50.52 118.812 42.6368 116.388C40.3475 113.77 41.003 114.918 40.9776 110.601Z"></path><path d="M40.962 93.9892C48.9458 93.4307 101.031 93.0732 107.092 94.4384C109.011 97.2697 108.39 96.4981 108.337 100.63C99.2541 100.979 49.4434 101.285 42.6632 100.295C40.3102 97.4429 41.0138 98.5556 40.962 93.9892Z"></path><path d="M40.7735 77.3026C49.4165 76.887 100.665 76.4501 107.145 77.8124C109.034 80.6584 108.415 79.9396 108.375 84.0448C96.6171 84.1084 51.7859 85.0018 42.6758 83.6708C40.2828 80.7935 41.018 81.828 40.7735 77.3026Z"></path><path d="M41.0069 60.748C49.38 60.2883 100.606 60.0216 107.042 61.2157C109.037 63.9972 108.397 63.187 108.326 67.4022C99.3515 67.7113 49.3521 68.2508 42.7383 66.9628C40.3055 64.2266 41.0186 65.3852 41.0069 60.748Z"></path><path d="M40.8555 44.0614C49.9054 43.8648 68.7089 42.2801 76.2696 45.0077C78.5834 47.8752 77.8118 46.1773 77.7237 50.6405C68.2631 50.9261 51.0804 52.5096 42.6905 49.8642C40.3493 47.2391 41.0384 48.3579 40.8555 44.0614Z"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M21.8126 5.81534C40.4287 4.49207 74.2591 5.56134 94.044 5.61514C105.36 16.6316 116.767 28.4235 127.941 39.6522L128.03 106.629C128.04 117.128 128.564 134.065 127.48 143.829C122.05 144.103 116.474 144.104 111.031 144.045C81.2381 143.771 51.2376 144.611 21.4659 143.829C20.5427 118.431 21.6118 89.1653 21.293 63.4218C21.1219 49.5779 20.511 18.6453 21.8126 5.81534ZM28.3145 12.6962L28.3087 136.962L120.907 136.945L120.93 44.7206C112.648 44.8312 96.2372 46.5942 90.7901 42.2802C87.2354 36.2711 88.9777 20.6817 88.9171 12.6874L28.3145 12.6962ZM96.2188 37.5136C101.821 37.497 110.407 37.728 115.764 37.331C111.579 33.1166 100.276 21.2117 96.0743 18.2694L96.2188 37.5136Z"></path></svg>',
                'text' => '<font color="" class="tx-wh">Planificatiō persōnālis alimentōrum dōnum</font>',
            ],
            [
                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150" fill="var(--white)"><path fill-rule="evenodd" clip-rule="evenodd" d="M57.793 6.40249C66.1975 6.31447 96.8127 3.86674 101.492 9.48355C102.828 14.6044 99.5334 19.1546 97.3096 23.6388C91.9202 34.5074 86.8851 45.5386 81.7812 56.546C88.8617 56.4942 112.943 54.9581 116.681 58.9503C116.613 59.9048 116.544 60.8592 116.477 61.8126C105.811 75.9882 79.0393 109.279 69.248 121.546C65.1534 126.909 54.3574 143.071 48.1904 143.394L46.8359 141.8C45.7993 133.243 58.0326 97.5284 61.2178 87.1222C50.2217 87.132 39.4521 87.1863 28.457 86.7589C29.922 81.1764 32.1729 75.3481 34.2305 69.9054C42.1919 48.8446 49.4022 27.2804 57.793 6.40249ZM38.4355 79.9865C45.9833 79.8975 61.4475 78.662 67.4082 82.1525C68.9367 84.6542 69.5209 86.0913 68.4541 89.3302C64.6206 100.967 61.0955 112.038 57.7803 123.827C63.1658 118.375 69.0938 110.345 73.9268 104.176C83.9203 91.6514 96.8737 76.3472 106.099 63.6349C99.2637 63.7181 79.599 64.4713 74.1016 62.7736C71.778 56.8252 90.0623 21.6716 93.6377 13.5021L62.5605 13.4767L38.4355 79.9865Z"></path></svg>',
                'text' => '<font color="" class="tx-wh">Missio grātuita ab sēstertium 3 mīlia</font>',
            ]
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
                'margin-bottom' => 'm-b-14',
                'picture-size' => 'i-l',
                'margin-right' => 'm-r-14',
            ],
        ];
        $p_props = [
            'font-header' => 't-rgl',
            'font-size' => [
                'name' => 'Size #5',
                'value' => 't-5',
                'unit' => 'px',
                'type' => 'library',
            ],
            'margin-top' => 'm-t-2',
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
