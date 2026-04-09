<?php

class siteCustomShopProductBlockType extends siteBlockType
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
        $options['type'] = 'site.CustomShopProduct';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getMainBlock();
        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        $hseq->addChild($this->getProductPicture());
        $hseq->addChild($this->getProductInfo());

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
                    'background' => [
                        'name' => 'grey shades',
                        'value' => 'bg-bw-8',
                        'type' => 'palette',
                        'uuid' => 1,
                        'layers' => [
                            [
                                'name' => 'grey shades',
                                'value' => 'bg-bw-8',
                                'type' => 'palette',
                                'uuid' => 1,
                            ],
                        ],
                    ],
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

    private function getProductPicture(): siteBlockData
    {
        $vseq = $this->createSequence();

        wa('shop');
        $picture = (new shopProductPictureBlockType())->getExampleBlockData();
        $picture->data = [
            'picture_type' => 'url_big',
            'block_props' => [
                'margin-bottom' => 'm-b-14 m-b-0-tb',
                'border-radius' => 'b-r-l',
            ],
            'product_id' => null,
        ];
        $vseq->addChild($picture);

        $column = $this->createColumn([
            'column' => 'st-7 st-6-lp st-12-tb st-12-mb',
            'block_props' => [
                $this->column_elements['main'] => [
                    'padding-bottom' => 'p-b-10-mb p-b-12',
                    'padding-top' => 'p-t-10-mb p-t-12',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                ],
                $this->column_elements['wrapper'] => [
                    'flex-align' => 'y-c',
                    'padding-top' => 'p-t-12 p-t-0-tb',
                    'padding-bottom' => 'p-b-12 p-b-0-tb',
                ],
            ],
            'wrapper_props' => [
                'flex-align' => 'y-c',
            ],
        ], $vseq);

        return $column;
    }

    private function getProductInfo() {
        $vseq = $this->createSequence();

        $discount = $this->createParagraph('<font color="#ff0000">' . _w('50% off') . '</font>', [
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
            'margin-top' => 'm-t-0',
            'margin-bottom' => 'm-b-0',
            'align' => 't-l',
        ]);
        $sub_column_discount = $this->createSubColumn([
            'block_props' => [
                'padding-top' => 'p-t-2',
                'padding-bottom' => 'p-b-3',
                'padding-left' => 'p-l-8',
                'padding-right' => 'p-r-8',
                'margin-bottom' => 'm-b-10',
                'border-radius' => 'b-r-m',
            ],
            'inline_props' => [
                'background' => [
                    'type' => 'self_color',
                    'value' => 'linear-gradient(#ff000029, #ff000029)',
                    'name' => 'Self color',
                    'layers' => [
                        [
                            'type' => 'self_color',
                            'value' => 'linear-gradient(#ff000029, #ff000029)',
                            'name' => 'Self color',
                            'css' => '#ff000029',
                            'uuid' => 1,
                        ],
                    ],
                    'uuid' => 1,
                ],
            ],
            'wrapper_props' => ['justify-align' => 'j-s',],
        ], [$discount]);
        $vseq->addChild($sub_column_discount);

        $product_name = $this->createProductInfo([
            'info_type' => 'name',
            'tag' => 'p',
            'block_props' => [
                'font-header' => 't-hdn',
                'font-size' => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library',],
                'margin-top' => 'm-t-0',
                'margin-bottom' => 'm-b-12',
                'align' => 't-l',
            ],
        ]);
        $vseq->addChild($product_name);

        $product_description = $this->createProductInfo([
            'info_type' => 'summary',
            'tag' => 'p',
            'block_props' => [
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                'margin-top' => 'm-t-0',
                'margin-bottom' => 'm-b-12',
                'align' => 't-l',
            ],
        ]);
        $vseq->addChild($product_description);

        $product_price = $this->createProductInfo([
            'info_type' => 'price',
            'tag' => 'p',
            'block_props' => [
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library',],
                'margin-top' => 'm-t-0',
                'margin-bottom' => 'm-b-10',
                'align' => 't-l',
                'margin-right' => 'm-r-10',
            ],
        ]);
        $product_compare_price = $this->createProductInfo([
            'info_type' => 'compare_price',
            'tag' => 'p',
            'block_props' => [
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                'margin-top' => 'm-t-4',
                'margin-bottom' => 'm-b-8',
                'align' => 't-l',
            ],
        ]);
        $row_product_price = $this->createRow([
            'block_props' => [
                'margin-right' => 'm-r-14-lp m-r-0-mb m-r-16',
                'padding-bottom' => 'p-b-4',
                'padding-top' => 'p-t-4',
            ],
            'wrapper_props' => [
                'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp n-wr-mb',
                'justify-align' => 'j-s',
            ],
        ], [$product_price, $product_compare_price]);
        $vseq->addChild($row_product_price);

        $sale_widget = (new shopProductSaleWidgetBlockType())->getExampleBlockData();
        $sale_widget->data['block_props'] = [
            'padding-top' => 'p-t-10',
            'padding-bottom' => 'p-b-10',
            'full-width' => 'f-w',
        ];
        $sale_widget_children = reset($sale_widget->children['']['-1']->children);

        $sale_widget_sku = $sale_widget_children['-1'];
        $sale_widget_sku->data['block_props']['margin-bottom'] = 'm-b-12';

        $sale_widget_button = $sale_widget_children['-3'];
        $sale_widget_button->data['block_props'] = [
            'margin-bottom' => 'm-b-10',
            'button-style' => [
                'name' => 'complementary',
                'scheme' => 'complementary',
                'value' => 'btn-a',
                'type' => 'palette',
            ],
            'button-size' => 'inp-l p-l-14 p-r-14',
        ];
        $vseq->addChild($sale_widget);

        foreach ($this->getProductInfoBottomList() as $item) {
            $vseq->addChild($item);
        }

        $column = $this->createColumn([
            'column' => 'st-5 st-6-lp st-12-tb st-12-mb',
            'block_props' => [
                $this->column_elements['main'] => [
                    'flex-align-vertical' => 'a-c-c',
                    'padding-bottom' => 'p-b-12 p-b-10-mb',
                    'padding-top' => 'p-t-12 p-t-0-mb',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                ],
                $this->column_elements['wrapper'] => [
                    'flex-align' => 'y-c',
                    'padding-bottom' => 'p-b-12 p-b-0-tb',
                    'padding-top' => 'p-t-12 p-t-0-tb',
                ],
            ],
        ], $vseq);

        return $column;
    }

    private function getProductInfoBottomList(): array
    {
        $icons = [
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="var(--bw-3)"><path d="M2.95801 7.73608H10.458L10.4998 11.25H13.7371L13.6953 7.73608H20.958L20.9998 19.5C20.9998 20.3284 20.3283 21 19.4998 21H4.49985C3.67142 21 2.99985 20.3284 2.99985 19.5L2.95801 7.73608Z"></path><path d="M11.0623 3.24316L10.4998 6.81226V6.94629H3.11328C3.13916 6.88337 3.16894 6.82165 3.20337 6.76245L4.81617 3.98877C5.08475 3.52718 5.5785 3.2432 6.11255 3.24316H11.0623Z"></path><path d="M17.8872 3.24316C18.4213 3.2432 18.915 3.52718 19.1836 3.98877L20.7964 6.76245C20.8308 6.82165 20.8606 6.88337 20.8865 6.94629H13.7373V6.81226L13.1777 3.24316H17.8872Z"></path></svg>',
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="var(--bw-3)"><path d="M4.9939 13.6287C5.24127 13.6934 5.74329 13.9141 6.03674 14.0242C7.13897 14.4381 8.59394 14.4899 9.7381 14.2399C10.2816 14.1212 10.9648 13.7774 11.5435 13.6398C12.0933 13.7792 13.588 14.7383 14.0621 15.1168C15.3053 16.1454 16.4414 17.5685 16.4462 19.2576C16.4514 21.0799 14.9912 20.9872 13.6897 20.9857L12.2017 20.9833L2.95582 20.9857C2.00083 20.9865 0.651385 21.1415 0.157314 20.1122C0.114517 20.0231 0.0453959 19.8549 0.0327917 19.7584C-0.288246 17.2964 1.80528 15.2478 3.80492 14.1781C4.26209 13.9333 4.49861 13.7368 4.9939 13.6287Z"></path><path d="M15.9559 13.6695C18.1391 13.7057 19.7353 13.3373 21.505 15.0321C22.2596 15.8099 22.9758 16.9023 22.9996 18.021C23.0193 18.9513 22.3764 19.3698 21.534 19.3787L17.5586 19.3725C17.7852 17.3398 16.4461 15.267 14.769 14.3474C14.5818 14.2448 14.312 13.9973 14.4075 13.7579C14.6066 13.6381 15.6732 13.6714 15.9559 13.6695Z"></path><path d="M7.71454 4.51296C10.2125 4.11336 12.5639 5.80762 12.9754 8.30363C13.3868 10.7997 11.7035 13.1591 9.20942 13.5824C6.69859 14.0084 4.32015 12.3113 3.90596 9.79851C3.49186 7.28577 5.19987 4.9153 7.71454 4.51296Z"></path><path d="M16.6943 6.92894C18.3799 6.63667 19.9827 7.76818 20.2718 9.4543C20.5609 11.1404 19.4266 12.7409 17.74 13.0268C16.0578 13.312 14.4623 12.1815 14.1739 10.4999C13.8856 8.81826 15.0132 7.22046 16.6943 6.92894Z"></path></svg>',
        ];

        $row_props = [
            'block_props' => [
                'padding-top' => 'p-t-4',
                'padding-bottom' => 'p-b-4',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
                'flex-wrap' => 'n-wr-ds n-wr-mb n-wr-tb n-wr-lp',
            ],
        ];
        $image_data = [
            'image' => [
                'color' => ['name' => 'Palette', 'type' => 'palette', 'value' => 'tx-bw-3',],
                'type' => 'svg',
                'fill' => 'removed',
                'svg_html' => '',
            ],
            'block_props' => [
                'margin-bottom' => 'm-b-12',
                'margin-right' => 'm-r-10',
            ],
        ];

        $list = [];

        $image = (new siteImageBlockType())->getEmptyBlockData();
        $image_data['image']['svg_html'] = $icons[0];
        $image->data = $image_data;

        $product_sku = $this->createProductInfo([
            'info_type' => 'stock',
            'tag' => 'p',
            'block_props' => [
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                'margin-top' => 'm-t-0',
                'margin-bottom' => 'm-b-8',
                'align' => 't-l',
            ],
        ]);
        $list[] = $this->createRow($row_props, [$image, $product_sku]);

        $image = (new siteImageBlockType())->getEmptyBlockData();
        $image_data['image']['svg_html'] = $icons[1];
        $image->data = $image_data;

        $p = $this->createParagraph('Iam emerunt <b>128</b>', [
            'font-header' => 't-rgl',
            'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
            'margin-top' => 'm-t-0',
            'margin-bottom' => 'm-b-12',
            'align' => 't-l',
        ]);
        $list[] = $this->createRow($row_props, [$image, $p]);

        return $list;
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
