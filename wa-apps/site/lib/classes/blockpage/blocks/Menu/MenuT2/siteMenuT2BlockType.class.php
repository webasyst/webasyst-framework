<?php
/**
 * Represents one or more cards of content.
 * Uses siteCardBlockType to store settings of individual cards.
 */
class siteMenuT2BlockType extends siteBlockType
{
    public $elements = [   
        'main' => 'site-block-menu',
        'wrapper' => 'site-block-menu-wrapper',
        ];

    public function __construct(array $options=[])
    {
        $options['type'] = 'site.Menu.';
        parent::__construct($options);
    }

    public function getExampleBlockData()
    {
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['is_complex'] = 'only_columns';
        $hseq->data['indestructible'] = true;
        
        //$column_count = ifset($this->options, 'columns', 2);

        $columns_arr = array();
        $logo_column = (new siteMenuLogoT2BlockType())->getExampleBlockData();
        $logo_column->data['column'] = 'st-3 st-8-mb st-3-lp st-6-tb';
        $menu_column = (new siteMenuColumnT2BlockType())->getExampleBlockData();
        $menu_column->data['column'] = 'st-6 st-0-tb st-0-mb st-7-lp';
        $contacts_column = (new siteMenuContactsT2BlockType())->getExampleBlockData();
        $contacts_column->data['column'] = 'st-3 st-4-mb st-2-lp st-6-tb';
        $burger_column = (new siteMenuBurgerBlockType())->getExampleBlockData();
        $burger_column->data['column'] = 'st-0 st-0-lp st-0-mb st-0-tb';

        $hseq->addChild($logo_column, 'col1');
        $hseq->addChild($menu_column, 'col2');
        $hseq->addChild($contacts_column, 'col3');
        $hseq->addChild($burger_column, 'col4');

        $result = $this->getEmptyBlockData();
        $result->addChild($hseq, '');

        $column_props = array();
        $column_props[$this->elements['main']] = ['padding-top' => "p-t-0", 'padding-bottom' => "p-b-0"];
        $column_props[$this->elements['wrapper']] = ['padding-top' => "p-t-10", 'padding-bottom' => "p-b-10", 'flex-align-vertical' => "x-c", 'max-width' => "cnt"];

        $result->data = ['block_props' => $column_props, 'wrapper_props' => ['justify-align' => "y-j-cnt", 'flex-align-vertical' => "x-c"]];
        //$result->data['columns'] = $columns_arr;
        $result->data['elements'] = $this->elements;
       
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
            'type_name' => _w('Menu'),
            'sections' => [
                [   'type' => 'MenuToggleGroup',
                    'name' => _w('Menu toggle'),
                ],
                [   'type' => 'ColumnsGroup',
                    'name' => _w('Columns'),
                ],
                [   'type' => 'ColumnsAlignVerticalGroup',
                    'name' => _w('Vertical alignment'),
                ],
                [   'type' => 'MenuDecorationGroup',
                    'name' => _w('Decoration'),
                ],
                [  'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'MaxWidthToggleGroup',
                    'name' => _w('Max width'),
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
                [   'type' => 'IdGroup',
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
