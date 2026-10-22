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
        ];
        $logo->data['indestructible'] = false;
        $logo->data['default_image_url'] = wa()->getAppStaticUrl('site').'img/image.svg';
        $svg_html = '<svg width="44" height="44" viewBox="0 0 44 44" fill="var(--black)" xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M22 32.3125C27.6954 32.3125 32.3125 27.6954 32.3125 22C32.3125 16.3046 27.6954 11.6875 22 11.6875C16.3046 11.6875 11.6875 16.3046 11.6875 22C11.6875 27.6954 16.3046 32.3125 22 32.3125ZM22 34.375C28.8345 34.375 34.375 28.8345 34.375 22C34.375 15.1655 28.8345 9.625 22 9.625C15.1655 9.625 9.625 15.1655 9.625 22C9.625 28.8345 15.1655 34.375 22 34.375Z"/>
        <path fill-rule="evenodd" clip-rule="evenodd" d="M22 41.9375C10.9888 41.9375 2.0625 33.0112 2.0625 22C2.0625 10.9888 10.9888 2.0625 22 2.0625C33.0112 2.0625 41.9375 10.9888 41.9375 22C41.9375 33.0112 33.0112 41.9375 22 41.9375ZM22 35.75C14.4061 35.75 8.25 29.5939 8.25 22C8.25 14.4061 14.4061 8.25 22 8.25C29.5939 8.25 35.75 14.4061 35.75 22C35.75 29.5939 29.5939 35.75 22 35.75Z"/>
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
                    'scroll-margin-top' => [
                        'value' => '',
                        'unit' => 'px',
                        'id' => 'logo',
                    ],
                ],
            ],
            'id' => [$this->elements['main'] => ['id' => 'logo']],
            'elements' => $this->elements,
            'indestructible' => true,
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
