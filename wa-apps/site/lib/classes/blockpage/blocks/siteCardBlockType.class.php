<?php
/**
 * Single card. Not used as a separate block but as a part of siteCardsBlockType.
 */
class siteCardBlockType extends siteBlockType
{
    public $elements = [   
        'main' => 'site-block-card',
        'wrapper' => 'site-block-card-wrapper',
        ];

    public function getExampleBlockData()
    {
        // Default card contents: vertical sequence with heading and a paragraph of text
        $vseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $vseq->addChild((new siteHeadingBlockType())->getExampleBlockData());
        $paragraph = (new siteParagraphBlockType())->getExampleBlockData();
        $paragraph->data["html"] = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed ";
        $vseq->addChild($paragraph);

        $result = $this->getEmptyBlockData();
        $result->addChild($vseq, '');
        $card_props = array();
        $card_props[$this->elements['main']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", 'padding-left' => "p-l-clm", 'padding-right' => "p-r-clm"];
        $card_props[$this->elements['wrapper']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", "border-radius" => "b-r-l", 'flex-align' => "y-c"];
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
            'type_name' => _w('Card'),
            'tags' => 'element',
            'sections' => [
                [   'type' => 'ColumnsAlignGroup',
                    'name' => _w('Columns alignment'),
                ],
                [   'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'ColumnWidthGroup',
                    'name' => _w('Width limit'),
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
            ],
            'elements' => $this->elements,
            'semi_headers' => [
                'main' => _w('Outside'),
                'wrapper' => _w('Inside'),
            ]
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
