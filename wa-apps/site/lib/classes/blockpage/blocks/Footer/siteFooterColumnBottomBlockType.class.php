<?php
/**
 * Single column. Not used as a separate block but as a part of siteMenuBlockType.
 */
class siteFooterColumnBottomBlockType extends siteBlockType
{
    public $elements = [
        'main' => 'site-block-column footer-bottom',
        'wrapper' => 'site-block-column-wrapper footer-bottom',
        ];

    public function getExampleBlockData()
    {
        //Default card contents: Horizontal sequence with heading and a paragraph of text
        //$hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        //$hseq->data['is_horizontal'] = true;
        //$hseq->data['is_complex'] = 'no_complex';
        //$menu_item = (new siteMenuItemBlockType())->getExampleBlockData();
        //$menu_item->data["block_props"]["width"] = "cnt-w";
        //$paragraph->data = ["html" => "Column","block_props" => ["font-header" => "t-rgl","font" => "t-7","margin-top" => "m-t-0","margin-bottom" => "m-b-0","margin-left" => "m-l-10","margin-right" => "m-r-16","align" => "t-c"]];
        //$hseq->addChild($menu_item);
        //$hseq->addChild($menu_item);
        //$hseq->addChild($menu_item);

        $result = $this->getEmptyBlockData();
        //$result->addChild($hseq, '');
        $card_props = array();
        $card_props[$this->elements['main']] = ['padding-top' => "p-t-0", 'padding-bottom' => "p-b-0", 'padding-left' => "p-l-clm", 'padding-right' => "p-r-clm"];
        $card_props[$this->elements['wrapper']] = ['padding-top' => "p-t-14", 'padding-bottom' => "p-b-14", "border-radius" => "b-r-l", 'flex-align' => "y-c"];
        $column_wrapper_class = 'st-6 st-6-lp st-6-tb st-12-mb';
        $result->data = ['block_props' => $card_props];
        $result->data['elements'] = $this->elements;
        $result->data['column'] = $column_wrapper_class;
        //$result->data['indestructible'] = true;
        return $result;
    }

    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars=[])
    {
        return parent::render($data, $is_backend, $tmpl_vars + [
            'children' => array_reduce($data->getRenderedChildren($is_backend), 'array_merge', []),
        ]);
    }

    protected function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('Column bottom'),
            'tags' => 'element',
            'sections' => [
                [   'type' => 'ColumnsAlignGroup',
                    'name' => _w('Alignment'),
                ],
                [   'type' => 'RowsAlignGroup',
                    'name' => _w('Column alignment'),
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
                [   'type' => 'VisibilityGroup',
                    'name' => _w('Visibility on devices'),
                ],
            ],
            'elements' => $this->elements,
            'semi_headers' => [
                'main' => _w('Whole column'),
                'wrapper' => _w('Content'),
            ]
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
