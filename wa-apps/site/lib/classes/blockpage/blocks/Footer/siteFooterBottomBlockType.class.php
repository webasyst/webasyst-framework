<?php
/**
 * Represents one or more cards of content.
 * Uses siteCardBlockType to store settings of individual cards.
 */
class siteFooterBottomBlockType extends siteBlockType
{
    public $elements = [
        'main' => 'site-block-footer',
        'wrapper' => 'site-block-footer-wrapper',
        ];

    public function __construct(array $options=[])
    {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = ifset(ref(explode('.', ifset($options, 'type', ''), 3)), 2, 2);
        }
        $options['type'] = 'site.FooterBottom.'.$options['columns'];
        parent::__construct($options);
    }

    public function getExampleBlockData()
    {
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['is_complex'] = 'only_columns';
        $hseq->data['indestructible'] = true;
        //$column_class = 'st-6 st-6-lp st-6-tb st-12-mb';
        $column_block_type = (new siteFooterColumnBottomBlockType())->getExampleBlockData();
        //$column_block_type->data['indestructible'] = false;
        //$column_block_type->data['new_column'] = $column_class;
        $column_count = ifset($this->options, 'columns', 2);

        $column_props = array();
        $column_props[$this->elements['main']] = ['padding-top' => "p-t-0", 'padding-bottom' => "p-b-0"];
        $column_props[$this->elements['wrapper']] = ['padding-top' => "p-t-10", 'padding-bottom' => "p-b-10", 'flex-align-vertical' => "x-c"];

        $result = $this->getEmptyBlockData();

        //$columns_arr = array();
        for($i = 1; $i <= $column_count; $i++) {
            $hseq->addChild($column_block_type, 'col'.$i);
            //$columns_arr['column-'.$i] = $column_class;
        }
        $result->addChild($hseq, '');
        $result->data = ['block_props' => $column_props, 'wrapper_props' => ['justify-align' => "y-j-cnt"]];
        //$result->data['new_columns'] = $columns_arr;
        $result->data['elements'] = $this->elements;
        //$result->data['indestructible'] = true;

        $app_template_prop = array();
        $app_template_prop['disabled'] = false;
        $app_template_prop['active'] = false;
        $result->data['app_template'] = $app_template_prop;

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
        return [
            'type_name' => _w('Footer bottom'),
            'sections' => [
                [   'type' => 'MenuToggleGroup',
                    'name' => _w('Footer toggle'),
                ],
                [   'type' => 'NewColumnsGroup',
                    'name' => _w('Columns'),
                ],
                [   'type' => 'ColumnsAlignGroup',
                    'name' => _w('Alignment'),
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
