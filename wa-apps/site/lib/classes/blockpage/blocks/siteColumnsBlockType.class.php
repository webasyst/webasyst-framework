<?php
/**
 * Represents one or more columns of content.
 * Uses siteColumnBlockType to store settings of individual columns.
 */
class siteColumnsBlockType extends siteBlockType
{
    public $elements = [   
        'main' => 'site-block-columns',
        'wrapper' => 'site-block-columns-wrapper',
        ];

    public function __construct(array $options=[])
    {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = ifset(ref(explode('.', ifset($options, 'type', ''), 3)), 2, 2);
        }
        $options['type'] = 'site.Columns.'.$options['columns'];
        parent::__construct($options);

    }

    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        $column_block_type = new siteColumnBlockType();
        $column_count = ifset($this->options, 'columns', 2);

        $column_class = 'fx-8 st-6 st-6-lp st-6-tb st-12-mb';
        $column_props = array();
        $column_props[$this->elements['main']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20"];
        $column_props[$this->elements['wrapper']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", 'flex-align' => "y-c"];
        $column_props_inline = array();
        //$column_props_inline[$this->elements['main']] = ['border-style' => ['value' => "solid solid solid solid", 'type' => "all"], 'border-width' => ['value' => '0px', 'name' => _w('Not set'), 'unit' => "px"], 'border-color' => ['value' => "unset", 'name' => _w('Not set')]];
        //$column_props_inline[$this->elements['wrapper']] = ['border-style' => ['value' => "solid solid solid solid", 'type' => "all"], 'border-width' => ['value' => '0px', 'name' => _w('Not set'), 'unit' => "px"], 'border-color' => ['value' => "unset", 'name' => _w('Not set')]];
        if ($column_count > 1) {
            $column_class = 'st-6 st-6-lp st-6-tb st-12-mb';
            $column_props[$this->elements['wrapper']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", 'flex-align-vertical' => "x-t"];
        }
        if ($column_count > 2) {
            $column_class = 'st-4 st-6-lp st-6-tb st-12-mb';
        }
        if ($column_count > 3) {
            $column_class = 'st-3 st-6-lp st-6-tb st-12-mb';
        }

        $result->data = ['block_props' => $column_props];
        //$result->data['inline_props'] = $column_props_inline;
        $columns_arr = array();
        for($i = 1; $i <= $column_count; $i++) {
            $result->addChild($column_block_type->getExampleBlockData(), 'col'.$i);
            $columns_arr['column-'.$i] = $column_class;

        }
        $result->data['columns'] = $columns_arr;
        $result->data['elements'] = $this->elements;

        return $result;
    }

    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars=[])
    {
        return parent::render($data, $is_backend, $tmpl_vars + [
            'children' => array_reduce($data->getRenderedChildren($is_backend), 'array_merge', []),
        ]);
    }

    public function getRawBlockSettingsFormConfig()
    {
        $column_count = $this->options['columns'];
        if ($column_count == 1) {
            return [
                'type_name' => _w('Columns'),
                'sections' => [
                    [   'type' => 'ColumnsGroup',
                        'name' => _w('Column'),
                    ],
                    [   'type' => 'ColumnsAlignGroup',
                        'name' => _w('Alignment'),
                    ],
                    [   'type' => 'TabsWrapperGroup',
                        'name' => _w('Tabs'),
                    ],
                    [   'type' => 'BackgroundColorGroup',
                        'name' => _w('Background'),
                    ],
                    [   'type' => 'PaddingGroup',
                        'name' => _w('Padding'),
                    ],  
                    [   'type' => 'MarginGroup',
                        'name' => _w('Margin'),
                    ],
                    [   'type' => 'BorderGroup',
                        'name' => _w('Border'),
                    ],
                    [   'type' => 'BorderRadiusGroup',
                        'name' => _w('Angle'),
                    ],
                    [   'type' => 'ShadowsGroup',
                        'name' => _w('Shadows'),
                    ],
                ],
                'elements' => $this->elements,
                'semi_headers' => [
                    'main' => _w('Whole block'),
                    'wrapper' => _w('Container'),
                ]
            ] + parent::getRawBlockSettingsFormConfig();
        };

        return [
            'type_name' => _w('Columns'),
            'sections' => [
                [   'type' => 'ColumnsGroup',
                    'name' => _w('Column'),
                ],
                [   'type' => 'ColumnsAlignVerticalGroup',
                    'name' => _w('Columns alignment'),
                ],
                [  'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'BackgroundColorGroup',
                    'name' => _w('Background'),
                ],
                [   'type' => 'PaddingGroup',
                    'name' => _w('Padding'),
                ],
                [   'type' => 'MarginGroup',
                    'name' => _w('Margin'),
                ],
                [   'type' => 'BorderGroup',
                    'name' => _w('Border'),
                ],
                [   'type' => 'BorderRadiusGroup',
                    'name' => _w('Angle'),
                ],
                [   'type' => 'ShadowsGroup',
                    'name' => _w('Shadows'),
                ],
            ],
            'elements' => $this->elements,
            'semi_headers' => [
                'main' => _w('Whole block'),
                'wrapper' => _w('Container'),
            ]
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
