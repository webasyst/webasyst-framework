<?php
/**
 * Represents one or more columns of content.
 * Uses siteColumnBlockType to store settings of individual columns.
 */
class siteCustomColumnsBlockType extends siteBlockType implements siteBlockTypeOptionsInterface
{
    public $elements = [
        'main' => 'site-block-columns',
        'wrapper' => 'site-block-columns-wrapper',
    ];

    public function __construct(array $options = [])
    {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = ifset(ref(explode('.', ifset($options, 'type', ''), 3)), 2, 2);
        }
        $options['type'] = 'site.CustomColumns.' . $options['columns'];
        parent::__construct($options);
    }

    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['is_complex'] = 'only_columns';
        $hseq->data['indestructible'] = true;
        $column_count = (int)ifset($this->options, 'columns', 2);

        if ($column_count === 1) {
            $column_class = 'st-12-tb st-12-mb st-8-lp st-7';
        } else if ($column_count === 2) {
            $column_class = 'st-6 st-6-lp st-12-mb st-12-tb';
        } else if ($column_count === 3) {
            $column_class = 'st-4 st-4-lp st-12-tb st-12-mb';
        } else if ($column_count === 4) {
            $column_class = 'st-3 st-3-lp st-6-tb st-12-mb';
        } else {
            $column_count = 6;
            $column_class = 'st-6-tb st-12-mb st-2 st-2-lp';
        }

        $column_props[$this->elements['main']] = [
            'padding-top' => "p-t-18 p-t-16-mb",
            'padding-bottom' => "p-b-18 p-b-16-mb",
            'padding-left' => 'p-l-blc',
            'padding-right' => 'p-r-blc'
        ];
        $column_props[$this->elements['wrapper']] = [
            'padding-top' => "p-t-0-mb",
            'padding-bottom' => "p-b-0-mb",
            'flex-align' => "y-c",
            'max-width' => "cnt"
        ];

        for ($i = 1; $i <= $column_count; $i++) {
            $column = $this->createColumn($i, [
                'column' => $column_class,
            ]);
            $hseq->addChild($column);
        }

        $result->addChild($hseq, '');
        $result->data = ['block_props' => $column_props, 'wrapper_props' => ['justify-align' => "y-j-cnt"]];
        $result->data['elements'] = $this->elements;

        return $result;
    }

    private function createColumn(int $num, array $params): siteBlockData
    {
        $column_block = new siteColumnBlockType();
        $column = $column_block->getEmptyBlockData();
        $column->data = [
            'elements' => $column_block->elements,
            'column' => $params['column'] ?? 'st-12 st-12-lp st-12-tb st-12-mb',
            'block_props' => [
                $column_block->elements['main'] => [
                    'padding-bottom' => 'p-b-12',
                    'padding-top' => 'p-t-12',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                ],
                $column_block->elements['wrapper'] => [
                    'flex-align' => 'y-c',
                ],
            ],
            'wrapper_props' => $params['wrapper_props'] ?? [],
            'inline_props' => $params['inline_props'] ?? [],
        ];

        $content = $this->getContent($num);
        $column->addChild($content, '');

        return $column;
    }


    private function getContent(int $num): siteBlockData
    {
        $vseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();

        $p = $this->createParagraph($num . ' Columna', [
            'font-header' => 't-rgl',
            'font-size' => [
                'name' => 'Size #6',
                'value' => 't-6',
                'unit' => 'px',
                'type' => 'library',
            ],
            'margin-top' => 'm-t-0',
            'margin-bottom' => 'm-b-14',
            'align' => 't-l',
        ]);

        $vseq->addChild($p);

        return $vseq;
    }

    private function createParagraph(string $text, array $block_props = [], $tag = 'p')
    {
        $paragraph = (new siteParagraphBlockType())->getEmptyBlockData();

        $paragraph->data = [
            'html'        => $text,
            'block_props' => $block_props,
            'tag'         => $tag,
        ];

        return $paragraph;
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
                [
                    'type' => 'CommonLinkGroup',
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
                    'type' => 'HeightGroup',
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
            'elements' => $this->elements,
            'semi_headers' => [
                'main' => _w('Whole block'),
                'wrapper' => _w('Container'),
            ]
        ] + parent::getRawBlockSettingsFormConfig();
    }

    public static function getConfiguratorHtml()
    {
        $options = [
            'available_columns' => [1, 2, 3, 4, 6],
        ];
        return wa()->getView()->renderTemplate(__DIR__ . '/templates/CustomColumns.configurator.html', $options);
    }

    public function getTypeOptions(): array
    {
        return [
            'columns' => $this->options['columns'],
        ];
    }

    public function setTypeOptions(array $type_options): void
    {
        if (wa_is_int(ifset($type_options, 'columns', null))) {
            $this->options['columns'] = (int)$type_options['columns'];
        }
    }
}
