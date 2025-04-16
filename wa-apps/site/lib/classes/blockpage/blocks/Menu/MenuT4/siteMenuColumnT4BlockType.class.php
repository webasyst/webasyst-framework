<?php
/**
 * Single column. Not used as a separate block but as a part of siteMenuBlockType.
 */
class siteMenuColumnT4BlockType extends siteBlockType
{
    public $elements = [   
        'main' => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
        ];

    public function getExampleBlockData()
    {
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['is_complex'] = 'no_complex';
        $menu_item1 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item2 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item3 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item4 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item1->data = ['html' => 'Malesuada', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10']];
        $menu_item2->data = ['html' => 'Quisque mauris', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10']];
        $menu_item3->data = ['html' => 'Ullamcorper', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10']];
        $menu_item4->data = ['html' => 'Turpis netus', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10']];

        $hseq->addChild($menu_item1);
        $hseq->addChild($menu_item2);
        $hseq->addChild($menu_item3);
        $hseq->addChild($menu_item4);

        $result = $this->getEmptyBlockData();
        $result->addChild($hseq, '');
        $card_props = array();
        $card_props[$this->elements['main']] = ['margin-bottom' => "m-b-a", 'margin-left' => "m-l-a", 'margin-right' => "m-r-a", 'margin-top' => "m-t-a", 'padding-top' => "p-t-0", 'padding-bottom' => "p-b-0", 'visibility' => "d-n-tb d-n-mb"];
        $card_props[$this->elements['wrapper']] = ['padding-top' => "p-t-10", 'padding-bottom' => "p-b-10", "border-radius" => "b-r-l", 'flex-align' => "y-c"];
        $result->data = ['block_props' => $card_props];
        $result->data['elements'] = $this->elements;
        $result->data['indestructible'] = true;
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
                'main' => _w('Whole block'),
                'wrapper' => _w('Container'),
            ]
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
