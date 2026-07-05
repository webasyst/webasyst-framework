<?php

class siteCustomMailerSubscribeBlockType extends siteBlockType
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
        $options['type'] = 'site.CustomMailerSubscribe';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();
        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        $vseq = $this->createSequence();
        foreach ($this->getContent() as $el) {
            $vseq->addChild($el);
        }
        $vseq->addChild($this->getForm());

        $column_background = [
            'name' => 'black and white',
            'type' => 'palette',
            'uuid' => 1,
            'value' => 'bg-wh',
        ];
        $column = $this->createColumn([
            'column' => 'st-8 st-8-lp st-12-tb st-12-mb',
            'block_props' => [
                $this->column_elements['main'] => [
                    'padding-bottom' => 'p-b-16-tb p-b-12',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                    'padding-top' => 'p-t-16-tb p-t-12',
                    'border-radius' => 'b-r-l',
                    'background' => $column_background + [
                        'layers' => [$column_background],
                    ],
                ],
                $this->column_elements['wrapper'] => [
                    'flex-align' => 'y-c',
                    'margin-left' => 'm-l-a',
                    'margin-right' => 'm-r-a',
                    'margin-bottom' => 'p-b-12-tb p-b-12 p-b-0-mb',
                    'margin-top' => 'p-t-12-tb p-t-12 p-b-0-mb',
                    'padding-bottom' => 'p-b-0-mb',
                    'padding-top' => 'p-t-0-mb',
                ],
            ],
            'wrapper_props' => [
                'flex-align' => 'y-c',
            ],
        ], $vseq);

        $hseq->addChild($column);

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $background = [
            'name' => 'grey shades',
            'type' => 'palette',
            'uuid' => 1,
            'value' => 'bg-bw-7',
        ];
        $result->data = [
            'block_props'   => [
                $this->elements['main']    => [
                    'padding-bottom' => 'p-b-12',
                    'padding-left'   => 'p-l-blc',
                    'padding-right'  => 'p-r-blc',
                    'padding-top'    => 'p-t-12',
                    'background'     => $background + [
                        'layers' => [$background],
                    ],
                ],
                $this->elements['wrapper'] => [
                    'border-radius' => 'b-r-l',
                    'flex-align' => 'y-c',
                    'max-width' => 'cnt',
                    'padding-bottom' => 'p-b-12',
                    'padding-top'    => 'p-t-12',
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

    private function getContent() {
        $heading = (new siteHeadingBlockType())->getEmptyBlockData();
        $heading->data = [
            'html'        => '<b>Semper inter primos de novitatibus cognosce</b>',
            'tag'         => 'h2',
            'block_props' => [
                'align' => 't-c',
                'font-header' => 't-hdn',
                'font-size' => ['name' => 'Size #4', 'value' => 't-4', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-12',
                'margin-top' => 'm-t-0',
            ],
        ];

        $p = $this->createParagraph(
            'Ad epistulas nuntiorum subscribite et primi cognoscite de novis collectionibus, offertis specialibus et materiis utilibus.',
            [
                'align'         => 't-c',
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-18',
                'margin-top'    => 'm-t-0',
            ]
        );

        return [$heading, $p];
    }

    private function getForm()
    {
        $form = (new siteFormBlockType(['form_type' => 'mailer']))->getExampleBlockData();
        $form->data['block_props'] = ['margin-bottom' => 'm-b-8'];

        return $form;
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
}
