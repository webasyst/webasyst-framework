<?php
/**
 * Single column. Not used as a separate block but as a part of siteMenuBlockType.
 */
class siteFooterColumnBlockType extends siteBlockType
{
    public $elements = [   
        'main' => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
        ];

    public function getExampleBlockData()
    {
        // Default card contents: Horizontal sequence with heading and a paragraph of text

        $result = $this->getEmptyBlockData();
        $card_props = array();
        $card_props[$this->elements['main']] = ['padding-top' => "p-t-0", 'padding-bottom' => "p-b-0", 'padding-left' => "p-l-clm", 'padding-right' => "p-r-clm"];
        $card_props[$this->elements['wrapper']] = ['padding-top' => "p-t-10", 'padding-bottom' => "p-b-10", "border-radius" => "b-r-l", 'flex-align' => "y-c"];
        $column_wrapper_class = 'st-6-tb st-12-mb st-3 st-3-lp';
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
            'type_name' => _w('Column'),
            'tags' => 'element',
            'sections' => [
                [   'type' => 'RowsAlignGroup',
                    'name' => _w('Column alignment'),
                ],
                [   'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'BackgroundColorGroup',
                    'name' => _w('Background'),
                ],
                [   'type' => 'HeightGroup',
                    'name' => _w('Height'),
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
