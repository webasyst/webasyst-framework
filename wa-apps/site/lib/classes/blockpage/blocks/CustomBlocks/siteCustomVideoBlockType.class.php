<?php

class siteCustomVideoBlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomVideo';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        // Добавляем последовательности в основной блок
        $hseq->addChild($this->getVideoColumn());
        $hseq->addChild($this->getTextColumn());

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['main'] => [
                    'padding-bottom' => 'p-b-20',
                    'padding-left' => 'p-l-blc',
                    'padding-right' => 'p-r-blc',
                    'padding-top' => 'p-t-20',
                ],
                $this->elements['wrapper'] => [
                    'flex-align' => 'y-c',
                    'max-width' => 'cnt',
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

    public function getVideoColumn(): siteBlockData {
        $vseq = $this->createSequence();
    
        $video = (new siteVideoBlockType())->getEmptyBlockData();
        $video->data['html'] = '';
        $video->data['video'] = [
            'type' => 'upload',
            'auto_loop' => true,
            'auto_play' => false,
            'muted' => true,
            'name' => 'woman-in-white.mp4',
        ];
        $video->data['block_props'] = [
            'border-radius' => 'b-r-l',
        ];
        $vseq->addChild($video);


        $block_props = [
            $this->column_elements['main']    => [
                'padding-bottom' => 'p-b-12',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
                'padding-top' => 'p-t-12',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align'       => 'y-c',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => "st-6-lp st-12-mb st-7 st-12-tb",
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-l'],
            ],
            $vseq
        );
    }

    public function getTextColumn(): siteBlockData {
        $vseq = $this->createSequence();

        $numerus = (new siteParagraphBlockType())->getEmptyBlockData();
        $numerus->data = [
            'html'        => '<font color="" class="tx-bw-4">Numerus: 91324123</font>',
            'block_props' => [
                'align' => 't-l',
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #7', 'value' => 't-7', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-10',
                'margin-top' => 'm-t-0',
            ],
            'tag'         => 'p',
        ];
        $vseq->addChild($numerus);

        $heading = $this->createHeading('Vestis Negotialis', [
            'align' => 't-l',
            'font-header' => 't-hdn',
            'font-size' => ['name' => 'Size #2', 'value' => 't-2', 'unit' => 'px', 'type' => 'library'],
            'margin-bottom' => 'm-b-14',
            'margin-top' => 'm-t-0',
        ], 'h3');
        $vseq->addChild($heading);

        $sticker_img = wa()->getAppStaticUrl('site') . 'img/blocks/video/sticker.svg';
        $sticker_text1 = (new siteParagraphBlockType())->getEmptyBlockData();
        $sticker_text1->data = [
            'html'        => '<font color="" class="tx-wh">$19.990</font>',
            'block_props' => [
                'align' => 't-c',
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-0',
                'margin-top' => 'm-t-0',
            ],
            'tag'         => 'p',
        ];
        $sticker_text2 = (new siteParagraphBlockType())->getEmptyBlockData();
        $sticker_text2->data = [
            'html'        => '<font color="" class="tx-bw-6"><strike>$29.490</strike></font>',
            'block_props' => [
                'align' => 't-c',
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #8', 'value' => 't-8', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-0',
                'margin-top' => 'm-t-0',
            ],
            'tag'         => 'p',
        ];
        $sticker = $this->createSubColumn([
            'block_props' => [
                'padding-bottom' => 'p-b-18',
                'padding-left' => 'p-l-14',
                'padding-right' => 'p-r-14',
                'padding-top' => 'p-t-18',
            ],
            'inline_props' => [
                'background' => [
                    'layers' => [
                        [
                            'alignmentX' => 'center',
                            'alignmentY' => 'center',
                            'css' => '',
                            'file_name' => 'sticker.svg',
                            'file_url' => $sticker_img,
                            'name' => 'Image',
                            'space' => 'contain no-repeat',
                            'type' => 'image',
                            'value' => 'center center / contain no-repeat url('.$sticker_img.')',
                        ],
                    ],
                    'name' => 'Self color',
                    'type' => 'self_color',
                    'value' => 'center center / contain no-repeat url('.$sticker_img.')',
                ],
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
            ],
        ], [$sticker_text1, $sticker_text2]);
        $vseq->addChild($sticker);

        foreach ($this->getRowData() as $row) {
            $vseq->addChild($this->createRow([
                'block_props' => [
                    'padding-bottom' => 'p-b-6 p-b-0-mb',
                    'padding-top' => 'p-t-6 p-t-0-mb',
                ],
                'wrapper_props' => [
                    'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb',
                    'justify-align' => 'j-s',
                ],
            ],$row));
        }

        $button = $this->createButton('Addere in Corbelam&nbsp', [
            'button-size' => 'inp-l p-l-14 p-r-14',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-blc', 'type' => 'palette'],
            'margin-bottom' => 'm-b-16',
            'margin-top' => 'm-t-14',
            'full-width' => 'f-w',
        ]);
        $vseq->addChild($button);

        $block_props = [
            $this->column_elements['main']    => [
                'flex-align-vertical' => 'a-c-c',
                'padding-bottom' => 'p-b-12',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
                'padding-top' => 'p-t-12',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => "st-6-lp st-12-mb st-5 st-12-tb",
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-l'],
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
        $sub_column->data['inline_props'] = $params['inline_props'] ?? [];

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
            $text1 = $this->createHeading('<font color="" class="tx-bw-4">'.$item['text1'].'</font>', [
                'align' => 't-l',
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #7', 'value' => 't-7', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-4',
                'margin-top' => 'm-t-0',
            ], 'p');
    
            $text2 = $this->createHeading($item['text2'], [
                'align' => 't-l',
                'font-header' => 't-hdn',
                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-8',
                'margin-top' => 'm-t-0',
            ], 'h3');


            $sub_column = $this->createSubColumn([
                'block_props' => [
                    'full-width' => 'f-w',
                    'margin-right' => 'm-r-12',
                    'padding-bottom' => 'p-b-8',
                    'padding-top' => 'p-t-4',
                ],
                'wrapper_props' => [
                    'justify-align' => 'j-s',
                ],
            ], [$text1, $text2]);


            $hseq->addChild($sub_column);
        }

        return $row;
    }

    private function getRowData() {
        return [
            [
                [
                    'text1' => 'Compositio',
                    'text2' => '70% Viscosa, 30% Linteum',
                ],
                [
                    'text1' => 'Tempus',
                    'text2' => 'Hiems, Aestas, Ver',
                ],
            ],
            [
                [
                    'text1' => 'Traditio',
                    'text2' => 'Gratis a pretio $100',
                ],
                [
                    'text1' => 'Collectio Propria',
                    'text2' => 'In 62 tabernis patriae',
                ],
            ],
            [
                [
                    'text1' => 'Solutio',
                    'text2' => 'Post probationem, Electronice, Pensionibus',
                ],
                [
                    'text1' => 'Redditus',
                    'text2' => '14 dierum',
                ],
            ]
        ];
    }

}
