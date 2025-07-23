<?php
/**
 * Single column. Not used as a separate block but as a part of siteMenuBlockType.
 */
class siteMenuLogoT1BlockType extends siteBlockType
{
    public $elements = [   
        'main' => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
        ];

    public function getExampleBlockData()
    {
        $logo = (new siteImageBlockType())->getExampleBlockData();
        $logo->data['block_props'] = [
            "margin-bottom" => "m-b-8",
            "margin-right" => "m-r-12",
            "margin-top" => "m-t-5",
            'pictures-size' => "i-xl",
        ];
        $logo->data['indestructible'] = false;
        $logo->data['default_image_url'] = wa()->getAppStaticUrl('site').'img/image.svg';
        $svg_html = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="64" viewBox="0 0 64 64" fill-rule="evenodd">
        <path d="M32,47 C40.2842712,47 47,40.2842712 47,32 C47,23.7157288 40.2842712,17 32,17 C23.7157288,17 17,23.7157288 17,32 C17,40.2842712 23.7157288,47 32,47 Z M32,50 C41.9411255,50 50,41.9411255 50,32 C50,22.0588745 41.9411255,14 32,14 C22.0588745,14 14,22.0588745 14,32 C14,41.9411255 22.0588745,50 32,50 Z"/>
        <path d="M32,61 C15.9837423,61 3,48.0162577 3,32 C3,15.9837423 15.9837423,3 32,3 C48.0162577,3 61,15.9837423 61,32 C61,48.0162577 48.0162577,61 32,61 Z M32,52 C20.954305,52 12,43.045695 12,32 C12,20.954305 20.954305,12 32,12 C43.045695,12 52,20.954305 52,32 C52,43.045695 43.045695,52 32,52 Z"/>
        </svg>';
        $logo->data['image'] = ['type' => 'svg', 'svg_html' => $svg_html];

        $header = (new siteHeadingBlockType())->getExampleBlockData();
        $header->data = [
            "html" => "<b>Nomen Societatis</b>",
            "tag" => "h3",
            "block_props" => [
                'font-size' => [
                    "name" => "Size #7", 
                    "value" => "t-7", 
                    "type" => "library",
                    'unit' => 'px',
                ], 
                "font-header" => "t-hdn",
                "margin-top" => "m-t-0",
                "margin-bottom" => "m-b-2",
                "align" => "t-l",
            ],
        ];
        
        $paragraph = (new siteHeadingBlockType())->getExampleBlockData();
        $paragraph->data = [
            "html" => '<font color="" class="tx-bw-3">Motto succinctum</font>', 
            "block_props" => [
                'font-size' => [
                    "name" => "Size #8", 
                    "value" => "t-8", 
                    "type" => "library",
                    'unit' => 'px',
                ],
                "margin-top" => "m-t-0",
                "margin-bottom" => "m-b-6",
                "align" => "t-l",
                "font-header" => "t-rgl",
            ],
            "tag" => "p",
        ];
        
        $sub_column = (new siteMenuT1BlockType())->createSubColumn([
            'block_props' => [
                'padding-top' => 'p-t-8',
                'padding-bottom' => 'p-b-6',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
            ],
        ], [$header, $paragraph]);

        $row = (new siteMenuT1BlockType())->createRow([
            'block_props' => [
                'padding-top' => 'p-t-6',
                'padding-bottom' => 'p-b-6',
            ],
            'wrapper_props' => [
                'justify-align' => 'j-s',
                'flex-wrap' => 'n-wr-mb',
            ],
        ], [$logo, $sub_column]);

        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['indestructible'] = true;
        $hseq->data['is_complex'] = 'no_complex';
        $hseq->addChild($row);

        $result = $this->getEmptyBlockData();
        $result->addChild($hseq, '');
        $result->data = [
            'block_props' => [
                $this->elements['main'] => [
                    "margin-left" => "m-l-0",
                    "margin-right" => "m-r-a",
                    "padding-bottom" => "p-b-6",
                    "padding-left" => "p-l-clm",
                    "padding-right" => "p-r-clm",
                    "padding-top" => "p-t-6",
                ],
                $this->elements['wrapper'] => [
                    'flex-align' => "y-c",
                ],
            ],
            'inline_props' => [
                $this->elements['main'] => [
                    'croll-margin-top' => [
                        'value' => '',
                        'unit' => 'px',
                        'id' => 'logo',
                    ],
                ],
            ],
            'id' => [$this->elements['main'] => ['id' => 'logo']],
            'elements' => $this->elements,
            'indestructible' => false,
        ];

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
                'main' => _w('Whole block'),
                'wrapper' => _w('Container'),
            ]
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
