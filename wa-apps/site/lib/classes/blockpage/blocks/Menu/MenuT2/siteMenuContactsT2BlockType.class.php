<?php
/**
 * Single column. Not used as a separate block but as a part of siteMenuBlockType.
 */
class siteMenuContactsT2BlockType extends siteBlockType
{
    public $elements = [
        'main' => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
    ];

    public function getExampleBlockData()
    {

        return $this->exampleBlockData();

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
                    [   'type' => 'VisibilityGroup',
                        'name' => _w('Visibility on devices'),
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

    public function exampleBlockData()
    {
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['is_complex'] = 'no_complex';

        $menu_item1 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item2 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item3 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item4 = (new siteMenuItemBlockType())->getExampleBlockData();

        $menu_item1->data = [
            'html' => 'De Nobis',
            'tag' => 'a',
            'block_props' => [
                'width' => 'cnt-w',
                'border-radius' => "b-r-r",
                'button-style' => [
                    "name" => "Palette",
                    "value" => "btn-wht-lnk",
                    "type" => "palette"
                ],
                'button-size' => 'inp-s p-l-12 p-r-12',
                'margin-bottom' => "m-b-12"
            ]
        ];
        $menu_item2->data = [
            'html' => 'Servitia',
            'tag' => 'a',
            'block_props' => [
                'width' => 'cnt-w',
                'border-radius' => "b-r-r",
                'button-style' => [
                    "name" => "Palette",
                    "value" => "btn-wht-lnk",
                    "type" => "palette"
                ],
                'button-size' => 'inp-s p-l-12 p-r-12',
                'margin-bottom' => "m-b-12"
            ]
        ];
        $menu_item3->data = [
            'html' => 'Opera',
            'tag' => 'a',
            'block_props' => [
                'width' => 'cnt-w',
                'border-radius' => "b-r-r",
                'button-style' => [
                    "name" => "Palette",
                    "value" => "btn-wht-lnk",
                    "type" => "palette"
                ],
                'button-size' => 'inp-s p-l-12 p-r-12',
                'margin-bottom' => "m-b-12"
            ]
        ];
        $menu_item4->data = [
            'html' => 'Auxilium',
            'tag' => 'a',
            'block_props' => [
                'width' => 'cnt-w',
                'border-radius' => "b-r-r",
                'button-style' => [
                    "name" => "Palette",
                    "value" => "btn-wht-lnk",
                    "type" => "palette"
                ],
                'button-size' => 'inp-s p-l-12 p-r-12',
                'margin-bottom' => "m-b-12"
            ]
        ];

        $hseq->addChild((new siteMenuT2BlockType())->createRow([
            'block_props' => [
                'padding-bottom' => "p-b-6",
                'padding-top' => "p-t-8",
            ],
            'wrapper_props' => [
                'flex-wrap' => "n-wr-ds n-wr-lp",
                'justify-align' => "j-end"
            ],
        ], [$menu_item1, $menu_item2, $menu_item3, $menu_item4]));

        $result = $this->getEmptyBlockData();
        $result->addChild($hseq, '');
        $card_props = array();
        $card_props[$this->elements['main']] = [
            'margin-bottom' => "m-b-a",
            'margin-left' => "m-l-a m-l-0-tb",
            'margin-right' => "m-r-a",
            'margin-top' => "m-t-a",
            'padding-top' => "p-t-6",
            'padding-bottom' => "p-b-6",
            'padding-left' => "p-l-0",
            'padding-right' => "p-r-clm"
        ];
        $card_props[$this->elements['wrapper']] = [
            "border-radius-corners" => [
                'value' => '',
                'type' => 'all',
            ],
            'flex-align' => "y-c"
        ];
        $result->data = ['block_props' => $card_props];
        $result->data['elements'] = $this->elements;
        $result->data['id'] = [
            $this->elements['main'] => [
                'id' => 'menut2itms'
            ]
        ];
        $result->data['indestructible'] = true;
        return $result;
    }
}
