<?php
/**
 * Represents one or more columns of content.
 * Uses siteColumnBlockType to store settings of individual columns.
 */
class siteCustomColumnsBlockType extends siteBlockType
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
        $column_count = ifset($this->options, 'columns', 2);
        $column_wrapper_class = 'st-3 st-3-lp st-6-tb st-12-mb';
        if ($column_count < 3) {
            $column_wrapper_class = 'st-6 st-6-lp st-6-tb st-12-mb';
        }
        if ($column_count < 2) {
            $column_wrapper_class = 'st-12 st-12-lp st-12-tb st-12-mb';
        }

        $column_block_type = (new siteColumnBlockType())->getExampleBlockData();
        $column_block_type->data['indestructible'] = false;
        $column_block_type->data['column'] = $column_wrapper_class;

        $column_props = array();
        $column_props[$this->elements['main']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", 'padding-left' => 'p-l-clm', 'padding-right' => 'p-r-clm'];
        $column_props[$this->elements['wrapper']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", 'flex-align' => "y-c", 'max-width' => "cnt"];

        $result = $this->getEmptyBlockData();

        for ($i = 1; $i <= $column_count; $i++) {
            $hseq->addChild($column_block_type);
        }
        $result->addChild($hseq, '');
        $result->data = ['block_props' => $column_props, 'wrapper_props' => ['justify-align' => "y-j-cnt"]];
        $result->data['elements'] = $this->elements;

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
        $column_count = $this->options['columns'];

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
}
