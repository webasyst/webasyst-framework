<?php
/**
 * Single column. Not used as a separate block but as a part of siteMenuBlockType.
 */
class siteMenuLogoT4BlockType extends siteBlockType
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
        $header = (new siteMenuItemBlockType())->getExampleBlockData();
        $paragraph = (new siteMenuItemBlockType())->getExampleBlockData();
        $logo = (new siteImageBlockType())->getExampleBlockData();
        $logo->data['block_props'] = ["margin-bottom" => "m-b-0", 'border-radius' => "b-r-l",'width' => 'i-xxl'];
        $logo->data['indestructible'] = false;
        $logo->data['default_image_url'] = wa()->getAppStaticUrl('site').'img/image.svg';
        $svg_html = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="64" height="64" viewBox="0 0 64 64" fill-rule="evenodd" fill="#ffffff">
        <path d="M32,47 C40.2842712,47 47,40.2842712 47,32 C47,23.7157288 40.2842712,17 32,17 C23.7157288,17 17,23.7157288 17,32 C17,40.2842712 23.7157288,47 32,47 Z M32,50 C41.9411255,50 50,41.9411255 50,32 C50,22.0588745 41.9411255,14 32,14 C22.0588745,14 14,22.0588745 14,32 C14,41.9411255 22.0588745,50 32,50 Z"></path>
        <path d="M32,61 C15.9837423,61 3,48.0162577 3,32 C3,15.9837423 15.9837423,3 32,3 C48.0162577,3 61,15.9837423 61,32 C61,48.0162577 48.0162577,61 32,61 Z M32,52 C20.954305,52 12,43.045695 12,32 C12,20.954305 20.954305,12 32,12 C43.045695,12 52,20.954305 52,32 C52,43.045695 43.045695,52 32,52 Z"></path>
        </svg>';
        $logo->data['image'] = ['type' => 'svg', 'svg_html' => $svg_html, 'color' => "ffffff"];
        $header->data = ["html" => "Turpis egestas","tag" => "h1","block_props" => ['button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], "font-header" => "t-hdn","margin-top" => "m-t-4","margin-bottom" => "m-b-0","align" => "t-l", 'button-size' => 'inp-s p-l-12 p-r-12' ]];
        $paragraph->data = ["html" => "Lobortis ante fames", "block_props" => ['button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], "font-header" => "t-rgl","margin-top" => "m-t-0","margin-bottom" => "m-b-0","align" => "t-l", 'button-size' => 'inp-xs p-l-11 p-r-11' ]];
        $vseq->addChild($header);
        $vseq->addChild($paragraph);
        $hseq->addChild($logo);
        $hseq->addChild($vseq);

        $result = $this->getEmptyBlockData();
        $result->addChild($hseq, '');
        $card_props = array();
        $card_props[$this->elements['main']] = ['padding-top' => "p-t-0", 'padding-bottom' => "p-b-0", 'padding-right' => "p-r-0"];
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
