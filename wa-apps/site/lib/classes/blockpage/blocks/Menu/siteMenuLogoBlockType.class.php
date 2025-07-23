<?php
/**
 * Single column. Not used as a separate block but as a part of siteMenuBlockType.
 */
class siteMenuLogoBlockType extends siteBlockType
{
    public $elements = [
        'main' => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
        ];

    public function getExampleBlockData()
    {
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['indestructible'] = true;
        $hseq->data['is_complex'] = 'no_complex';
        $vseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        //$vseq->data['indestructible'] = true;
        $header = (new siteMenuItemBlockType())->getExampleBlockData();
        $paragraph = (new siteMenuItemBlockType())->getExampleBlockData();
        //$header = (new siteHeadingBlockType())->getExampleBlockData();
        //$paragraph = (new siteParagraphBlockType())->getExampleBlockData();

        $logo = (new siteImageBlockType())->getExampleBlockData();
        $logo->data['block_props'] = ["margin-right" => "m-r-8","margin-bottom" => "m-b-0", 'border-radius' => "b-r-l",'width' => 'i-xxl'];
        $logo->data['indestructible'] = true;
        $logo->data['default_image_url'] = wa()->getAppStaticUrl('site').'img/image.svg';
        $logo->data['image'] = 'image.svg';
        $header->data = ["html" => _w('Company slogan'),"tag" => "h1","block_props" => ['button-style' => ["name" => "Palette", "value" => "btn-blc-lnk", "type" => "palette"], "font-header" => "t-hdn","font" => "t-7","margin-top" => "m-t-0","margin-bottom" => "m-b-2","align" => "t-l" ], 'indestructible' => true ];
        $paragraph->data = ["html" => _w('Company slogan'), "block_props" => ['button-style' => ["name" => "Palette", "value" => "btn-blc-lnk", "type" => "palette"], "font-header" => "t-rgl","font" => "t-8","margin-top" => "m-t-0","margin-bottom" => "m-b-0","align" => "t-l"], 'indestructible' => true];

        $vseq->addChild($header);
        $vseq->addChild($paragraph);
        $hseq->addChild($logo);
        $hseq->addChild($vseq);

        $result = $this->getEmptyBlockData();
        $result->addChild($hseq, '');
        $card_props = array();
        $card_props[$this->elements['main']] = ['padding-top' => "p-t-0", 'padding-bottom' => "p-b-0", 'padding-left' => "p-l-clm", 'padding-right' => "p-r-clm"];
        $card_props[$this->elements['wrapper']] = ['padding-top' => "p-t-10", 'padding-bottom' => "p-b-10", "border-radius" => "b-r-l", 'flex-align' => "y-l"];
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
            'type_name' => _w('Logo'),
            'tags' => 'element',
            'sections' => [

                [   'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'CommonLinkGroup',
                    'name' => _w('Link or action'),
                    'is_hidden' => true,
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
