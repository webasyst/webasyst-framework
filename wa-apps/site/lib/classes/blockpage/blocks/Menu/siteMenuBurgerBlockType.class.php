<?php
/**
 * Single column. Not used as a separate block but as a part of siteMenuBlockType.
 */
class siteMenuBurgerBlockType extends siteBlockType
{
    public $elements = [   
        'main' => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
        ];

    public function getExampleBlockData()
    {
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $logo = (new siteImageBlockType())->getExampleBlockData();
        $logo->data['block_props'] = ["margin-right" => "m-r-0","margin-bottom" => "m-b-0", 'width' => 'i-xxl'];
        $logo->data['indestructible'] = true;
        $logo->data['default_image_url'] = wa()->getAppStaticUrl('site').'img/burger.svg';
        $logo->data['image'] = 'burger.svg';
        $hseq->addChild($logo);
        $result = $this->getEmptyBlockData();
        $result->addChild($hseq, '');
        $card_props = array();
        $card_props[$this->elements['main']] = ['padding-top' => "p-t-0", 'padding-bottom' => "p-b-0", 'visibility' => "d-n-lp d-n-ds"];
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
            'type_name' => _w('Burger'),
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
            ],
            'elements' => $this->elements,
            'semi_headers' => [
                'main' => _w('Whole block'),
                'wrapper' => _w('Container'),
            ]
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
