<?php

class siteCustomCtaBlockType extends siteBlockType
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
        $options['type'] = 'site.CustomCta';
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

        // Добавляем заголовок в вертикальную последовательность
        $vseq->addChild($this->getHeading());
        // Добавляем параграф в вертикальную последовательность
        $vseq->addChild($this->getParagraph());
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
        $block_props = array();
        $block_props[$this->elements['main']] = [
            'padding-top' => "p-t-18",
            'padding-bottom' => "p-b-18",
            'padding-left' => 'p-l-blc',
            'padding-right' => 'p-r-blc',
            'color' => 'f-w',
        ];
        $block_props[$this->elements['wrapper']] = [
            'padding-top' => "p-t-12",
            'padding-bottom' => "p-b-12",
            'flex-align' => "y-c",
            'max-width' => "cnt",
            'border-radius' => "b-r-l",
            'background' => [
                'layers' => [
                    [
                        'name' => 'grey shades',
                        'value' => "bg-bw-8",
                        'type' => 'palette',
                    ],
                ],
                'name' => 'grey shades',
                'type' => 'palette',
                'value' => "bg-bw-8",
            ]
        ];

        $result->data = [
            'block_props' => $block_props,
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
            'column' => "st-12-lp st-12-tb st-12-mb st-12",
            'indestructible' => false,
            'block_props' => [
                $column_elements['main'] => [
                    'padding-top' => "p-t-20 p-t-16-tb",
                    'padding-bottom' => "p-b-20 p-b-16-tb",
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
            'html' => 'Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In vulputate lobortis ante, sed hendrerit mauris scelerisque ut.',
            'tag' => 'p',
            'block_props' => [
                'font-header' => "t-rgl",
                'font-size' => ["name" => "Size #5", "value" => "t-5", "unit" => "px", "type" => "library"],
                'margin-top' => "m-t-0",
                'margin-bottom' => "m-b-18",
                'align' => "t-c"
            ]
        ];

        return $paragraph;
    }

    private function getHeading()
    {
        $header = (new siteHeadingBlockType())->getEmptyBlockData();

        $header->data = [
            'html' => 'Nam a finibus magna',
            'tag' => 'h2',
            'block_props' => [
                'font-header' => "t-hdn",
                'font-size' => ["name" => "Size #3", "value" => "t-3", "unit" => "px", "type" => "library"],
                'margin-top' => "m-t-0",
                'margin-bottom' => "m-b-14",
                'align' => "t-c"
            ]
        ];

        return $header;
    }

    private function getButton()
    {
        $button = (new siteButtonBlockType())->getEmptyBlockData();

        $button->data = [
            'html' => 'Malesuada fames',
            'tag' => 'a',
            'block_props' => [
                'border-radius' => "b-r-r",
                'button-size' => "inp-l p-l-14 p-r-14",
                'button-style' => ['name' => 'Palette', 'value' => 'btn-blc', 'type' => 'palette'],
                'margin-bottom' => "m-b-12"
            ]
        ];

        return $button;
    }
}
