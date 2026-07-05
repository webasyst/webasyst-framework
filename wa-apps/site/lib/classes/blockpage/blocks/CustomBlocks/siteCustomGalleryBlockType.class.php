<?php

class siteCustomGalleryBlockType extends siteBlockType {
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
            $options['columns'] = 1;
        }
        $options['type'] = 'site.CustomGallery';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        // Добавляем последовательности в основной блок
        $hseq->addChild($this->getHeaderColumn());
        foreach ($this->getGalleryData() as $gallery) {
            $hseq->addChild($this->getGalleryColumn($gallery));
        }
        $hseq->addChild($this->getFooterColumn());

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['main']    => [
                    'padding-top'    => "p-t-18",
                    'padding-bottom' => "p-b-18",
                    'padding-left' => 'p-l-blc',
                    'padding-right' => 'p-r-blc',
                ],
                $this->elements['wrapper'] => [
                    'padding-top'    => "p-t-12",
                    'padding-bottom' => "p-b-12",
                    'flex-align'     => "y-c",
                    'max-width'      => "cnt",
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

    public function getHeaderColumn(): siteBlockData {
        $vseq = $this->createSequence();
        $vseq->addChild($this->getTitle());
        $vseq->addChild($this->createParagraph(
            'Praesent auctor purus luctus enim egestas, ac scelerisque ante pulvinar.',
            [
                'align'         => 't-c',
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-12',
                'margin-top'    => 'm-t-0',
            ]
        ));


        $block_props = [
            $this->column_elements['main']    => [
                'padding-bottom'      => 'p-b-12',
                'padding-top'         => 'p-t-12',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align'       => 'y-c',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-12-mb st-12 st-12-lp st-12-tb',
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-c'],
            ],
            $vseq
        );
    }

    public function getGalleryColumn(array $gallery): siteBlockData {
        $vseq = $this->createSequence();
        foreach ($gallery as $text => $image) {
            $vseq->addChild($this->getImage($image));
            $vseq->addChild($this->createParagraph(
                '<font class="tx-bw-4">'.$text.'</font>',
                [
                    'align'         => 't-c',
                    'font-header'   => 't-rgl',
                    'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                    'margin-bottom' => 'm-b-16',
                    'margin-top'    => 'm-t-0',
                ]
            ));
        }


        $block_props = [
            $this->column_elements['main']    => [
                'padding-bottom'      => 'p-b-12',
                'padding-top'         => 'p-t-12',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align'       => 'y-c',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-3 st-3-lp st-6-tb st-12-mb',
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-c'],
            ],
            $vseq
        );
    }

    public function getFooterColumn(): siteBlockData {
        $vseq = $this->createSequence();
        $vseq->addChild($this->createParagraph(
            'Ut euismod nisl arcu, sed placerat nulla volutpat aliquet. sed auctor sit amet, molestie a nibh ut euismod',
            [
                'align'         => 't-c',
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-16',
                'margin-top'    => 'm-t-0',
            ]
        ));
        $vseq->addChild($this->getBtn());

        $block_props = [
            $this->column_elements['main']    => [
                'padding-bottom'      => 'p-b-12',
                'padding-top'         => 'p-t-12',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'column-max-width' => 'fx-6',
                'flex-align'       => 'y-c',
                'margin-left'      => 'm-l-a',
                'margin-right'     => 'm-r-a',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-12-mb st-12 st-12-lp st-12-tb',
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-c'],
            ],
            $vseq
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
            'html'        => 'Dapibus pellentesque sit',
            'tag'         => 'h3',
            'block_props' => [
                'align'         => 't-c',
                'font-header'   => 't-hdn',
                'font-size'     => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-12',
                'margin-top'    => 'm-t-0',
            ],
        ];

        return $heading;
    }

    private function getImage(string $image_url) {
        $imageBlock = (new siteImageBlockType())->getEmptyBlockData();

        $imageBlock->data = [
            'image'       => [
                'type'     => 'address',
                'url_text' => $image_url,
            ],
            'block_props' => [
                'border-radius' => 'b-r-l',
                'margin-bottom' => 'm-b-12',
            ],
        ];

        return $imageBlock;
    }

    private function getBtn() {
        $btn = (new siteButtonBlockType())->getEmptyBlockData();    

        $btn->data = [
            'html'       => 'Dapibus pellentesque sit',
            'tag'        => 'a',
            'block_props' => [
                'border-radius' => 'b-r-r',
                'button-size'   => 'inp-l p-l-14 p-r-14',
                'button-style'  => ['name' => 'Palette', 'value' => 'btn-blc', 'type' => 'palette'],
                'margin-bottom' => 'm-b-12',
            ],
        ];

        return $btn;
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

    private function getGalleryData() {
        $image_url = wa()->getAppStaticUrl('site') . 'img/blocks/gallery/';

        return [
            
            [
                'Scelerisque ante pulvinar' => $image_url . 'food-3.jpg',
                'Interdum vulputate' => $image_url . 'food-11.jpg',
                'Pellentesque' => $image_url . 'food-9.jpg'
            ],

            [
                'Eusmod tempor incididunt' => $image_url . 'food-6.jpg',
                'Finibus id sollicitudin' => $image_url . 'food-1.jpg',
                'Velit ac dolor dapibus' => $image_url . 'food-5.jpg'
            ],

            [
                'Tempor incididunt' => $image_url . 'food-2.jpg',
                'Ad litora torquent per' => $image_url . 'food-7.jpg',
                'Praesent auctor' => $image_url . 'food-4.jpg'
            ],

            [
                'Labore et dolore ' => $image_url . 'food-8.jpg',
                'Convallis diam' => $image_url . 'food-10.jpg'
            ],
            
        ];
    }

}
