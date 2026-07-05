<?php

class siteCustomShopProductsBlockType extends siteBlockType
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
        $options['type'] = 'site.CustomShopProducts';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getMainBlock();
        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        $hseq->addChild($this->getHeaderTitle());
        $hseq->addChild($this->getHeaderLink());
        for ($i=0; $i < 4; $i++) {
            $hseq->addChild($this->getProductColumn());
        }

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
                    'padding-bottom' => 'p-b-18',
                    'padding-left'   => 'p-l-blc',
                    'padding-right'  => 'p-r-blc',
                    'padding-top'    => 'p-t-18',
                ],
                $this->elements['wrapper'] => [
                    'flex-align' => 'y-c',
                    'max-width' => 'cnt',
                    'padding-bottom' => 'p-t-12',
                    'padding-top'    => 'p-b-12',
                ],
            ],
            'wrapper_props' => [
                'justify-align' => 'y-j-cnt',
            ],
            'elements'      => $this->elements,
        ];

        return $result;
    }

    private function getHeaderTitle(): siteBlockData
    {
        $vseq = $this->createSequence();
        $heading =$this->createHeading('Nova', [
            'font-header' => 't-hdn',
            'align' => 't-l',
            'margin-top' => 'm-t-0',
            'font-size' => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library',],
            'margin-bottom' => 'm-b-8',
            'max-width' => 'fx-9',
        ], 'h2');
        $vseq->addChild($heading);

        $heading_column  = $this->createColumn([
            'column' => 'st-9 st-9-lp st-9-tb st-7-mb',
            'block_props' => [
                $this->column_elements['main'] => [
                    'flex-align-vertical' => 'a-c-c',
                    'padding-top' => 'p-t-0',
                    'padding-bottom' => 'p-b-0',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                ],
                $this->column_elements['wrapper'] => ['flex-align' => 'y-c',],
            ],
            'wrapper_props' => ['flex-align' => 'y-l',],
        ], $vseq);

        return $heading_column;
    }

    private function getHeaderLink(): siteBlockData
    {
        $vseq = $this->createSequence();
        $link =$this->createHeading('<span class="tx-bw-1"><u>Omnia videre</u> →</span>', [
            'align' => 't-l',
            'margin-top' => 'm-t-0',
            'margin-left' => 'm-l-a m-l-0-mb',
            'margin-bottom' => 'm-b-8',
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
        ], 'h2');
        $vseq->addChild($link);

        $link_column  = $this->createColumn([
            'column' => 'st-3 st-3-lp st-3-tb st-5-mb',
            'block_props' => [
                $this->column_elements['main'] => [
                    'flex-align-vertical' => 'a-c-c',
                    'padding-top' => 'p-t-8',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                ],
                $this->column_elements['wrapper'] => ['flex-align' => 'y-c',],
            ],
            'wrapper_props' => ['flex-align' => 'y-l',],
        ], $vseq);

        return $link_column;
    }

    private function getProductColumn(): siteBlockData
    {
        $vseq = $this->createSequence();

        wa('shop');
        $picture = (new shopProductPictureBlockType())->getExampleBlockData();
        $picture->data['block_props'] = [
            'margin-bottom' => 'm-b-14',
            'border-radius' => 'b-r-l',
        ];
        $vseq->addChild($picture);

        $product_name = $this->createProductInfo([
            'info_type' => 'name',
            'tag' => 'h3',
            'block_props' => [
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                'margin-top' => 'm-t-0',
                'margin-bottom' => 'm-b-10',
                'align' => 't-l',
            ],
        ]);
        $vseq->addChild($product_name);

        $product_price = $this->createProductInfo([
            'info_type' => 'price',
            'tag' => 'p',
            'block_props' => [
                'font-header' => 't-hdn',
                'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library',],
                'margin-top' => 'm-t-0',
                'margin-bottom' => 'm-b-8',
                'align' => 't-l',
            ],
        ]);
        $vseq->addChild($product_price);

        $sale_widget = (new shopProductSaleWidgetBlockType())->getExampleBlockData();
        $sale_widget->data['block_props'] = [
            'padding-top' => 'p-t-10',
            'padding-bottom' => 'p-b-10',
            'full-width' => 'f-w',
            'margin-top' => 'm-t-a',
        ];
        $vseq->addChild($sale_widget);

        return $this->createColumn([
            'column' => 'st-3 st-3-lp st-6-tb st-12-mb',
            'block_props' => [
                $this->column_elements['main'] => [
                    'padding-top' => 'p-t-12',
                    'padding-bottom' => 'p-b-12',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                ],
                $this->column_elements['wrapper'] => [
                    'flex-align' => 'y-c',
                    'padding-top' => 'p-t-12',
                    'padding-bottom' => 'p-b-12',
                ],
            ],
            'inline_props' => [
                $this->column_elements['wrapper'] => [
                    'min-height' => [
                        'name' => 'Fill parent',
                        'value' => '100%',
                        'type' => 'parent',
                    ],
                ],
            ],
            'wrapper_props' => ['flex-align' => 'y-l',],
        ], $vseq);
    }

    private function createProductInfo(array $data)
    {
        $block = (new shopProductInfoBlockType())->getExampleBlockData();
        $block->data = $data;

        return $block;
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
