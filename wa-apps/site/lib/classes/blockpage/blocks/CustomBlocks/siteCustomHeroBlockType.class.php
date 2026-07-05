<?php

class siteCustomHeroBlockType extends siteBlockType
{
    public $elements = [
        'main' => 'site-block-columns',
        'wrapper' => 'site-block-columns-wrapper',
    ];

    public function __construct(array $options = [])
    {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = 1;
        }
        $options['type'] = 'site.CustomHero';
        parent::__construct($options);
    }

    public function getExampleBlockData()
    {
        // Создаем основной блок
        $result = $this->getEmptyBlockData();

        // Создаем горизонтальную последовательность для колонок
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['is_complex'] = 'only_columns';
        $hseq->data['indestructible'] = true;

        // Создаем вертикальную последовательность для содержимого колонки
        $vseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $vseq->data['is_complex'] = 'with_row';

        // Добавляем параграф в вертикальную последовательность
        $vseq->addChild($this->getParagraph());
        // Добавляем заголовок в вертикальную последовательность
        $vseq->addChild($this->getHeading());
        // Добавляем кнопку в вертикальную последовательность
        $vseq->addChild($this->getButton());

        // Создаем колонку
        $column = $this->getColumn();
        // Добавляем вертикальную последовательность в колонку
        $column->addChild($vseq, '');

        // Добавляем колонку в горизонтальную последовательность
        $hseq->addChild($column);

        // Добавляем горизонтальную последовательность в основной блок
        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $column_props = array();
        $column_props[$this->elements['main']] = [
            'padding-top' => "p-t-25 p-t-20-tb p-t-16-mb",
            'padding-bottom' => "p-b-25 p-b-20-tb p-b-16-mb",
            'padding-left' => 'p-l-blc',
            'padding-right' => 'p-r-blc',
        ];
        $column_props[$this->elements['wrapper']] = [
            'padding-top' => "p-t-20 p-t-16-mb",
            'padding-bottom' => "p-b-20 p-b-16-mb",
            'flex-align' => "y-c",
            'max-width' => "cnt",
        ];
        $image = wa()->getAppStaticUrl('site') . 'img/blocks/hero/store.jpg';
        $inline_props = [];
        $inline_props[$this->elements['main']] = [
            'background' => [
                'type' => 'self_color',
                'value' => 'linear-gradient(#00000099, #00000099), center center / cover url("' . $image . '")',
                'layers' => [
                    [
                        'type' => 'self_color',
                        'name' => 'Self color',
                        'css' => '#00000099',
                        'value' => 'linear-gradient(#00000099, #00000099)'
                    ],
                    [
                        'type' => 'image',
                        'value' => 'center center / cover url("' . $image . '")',
                        'alignmentX' => 'center',
                        'alignmentY' => 'center',
                        'file_name' => 'store.jpg',
                        'file_url' => $image,
                        'space' => 'cover',
                        'name' => 'Image',
                    ]
                ]
            ],
        ];

        $result->data = [
            'block_props' => $column_props,
            'inline_props' => $inline_props,
            'wrapper_props' => ['justify-align' => "y-j-cnt"],
            'elements' => $this->elements
        ];

        return $result;
    }

    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars = [])
    {
        return parent::render($data, $is_backend, $tmpl_vars + [
            'children' => array_reduce($data->getRenderedChildren($is_backend), 'array_merge', []),
        ]);
    }

    public function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('Block'),
            'sections' => [
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
            'elements' => $this->elements,
            'semi_headers' => [
                'main' => _w('Whole block'),
                'wrapper' => _w('Container'),
            ]
        ] + parent::getRawBlockSettingsFormConfig();
    }


    private function getColumn()
    {
        $column = (new siteColumnBlockType())->getEmptyBlockData();

        $column_elements = [
            'main' => 'site-block-column',
            'wrapper' => 'site-block-column-wrapper',
        ];

        $column->data = [
            'elements' => $column_elements,
            'column' => "st-12 st-12-lp st-12-tb st-12-mb",
            'indestructible' => false,
            'block_props' => [
                $column_elements['main'] => [
                    'padding-top' => "p-t-20 p-t-12-tb",
                    'padding-bottom' => "p-b-20 p-b-12-tb",
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                ],
                $column_elements['wrapper'] => [
                    'column-max-width' => "fx-9",
                    'flex-align' => "y-c",
                    'margin-left' => "m-l-a",
                    'margin-right' => "m-r-a",
                    'padding-top' => "p-t-20 p-t-12-tb",
                    'padding-bottom' => "p-b-20 p-b-12-tb",
                ],
            ],
            'wrapper_props' => [
                'flex-align' => "y-c",
            ],
        ];

        return $column;
    }

    private function getParagraph()
    {
        $paragraph = (new siteParagraphBlockType())->getEmptyBlockData();

        $paragraph->data = [
            'html' => '<span class="tx-wh">Hendrerit mauris</span>',
            'tag' => 'h2',
            'block_props' => [
                'font-header' => "t-rgl",
                'font-size' => ["name" => "Size #5", "value" => "t-5", "unit" => "px", "type" => "library"],
                'margin-top' => "m-t-0",
                'margin-bottom' => "m-b-12",
                'align' => "t-l"
            ]
        ];

        return $paragraph;
    }

    private function getHeading()
    {
        $header = (new siteHeadingBlockType())->getEmptyBlockData();

        $header->data = [
            'html' => '<span class="tx-wh">Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In vulputate lobortis ante.</span>',
            'tag' => 'p',
            'block_props' => [
                'font-header' => "t-hdn",
                'font-size' => ["name" => "Size #2", "value" => "t-2", "unit" => "px", "type" => "library"],
                'margin-top' => "m-t-0",
                'margin-bottom' => "m-b-18",
                'align' => "t-c"
            ]
        ];

        return $header;
    }

    private function getButton()
    {
        $button = (new siteButtonBlockType())->getEmptyBlockData();

        $button->data = [
            'html' => 'Quis nostrud',
            'tag' => 'a',
            'block_props' => [
                'border-radius' => "b-r-r",
                'button-size' => "inp-l p-l-14 p-r-14",
                'button-style' => ['name' => 'Palette', 'value' => 'btn-wht', 'type' => 'palette'],
                'margin-bottom' => "m-b-12"
            ]
        ];

        return $button;
    }
}
