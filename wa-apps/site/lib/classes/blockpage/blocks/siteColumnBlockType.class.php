<?php
/**
 * Single column. Not used as a separate block but as a part of siteColumnsBlockType.
 */
class siteColumnBlockType extends siteBlockType
{
    public $elements = [
        'main' => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
        ];

    public function getExampleBlockData()
    {
        // Default column contents: vertical sequence with heading and a paragraph of text
        $vseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $vseq->addChild((new siteHeadingBlockType())->getExampleBlockData());
        $vseq->addChild((new siteParagraphBlockType())->getExampleBlockData());
        $vseq->data['is_complex'] = 'with_row';
        $result = $this->getEmptyBlockData();
        $result->addChild($vseq, '');
        $column_props = array();
        $column_props[$this->elements['main']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20"];
        $column_props[$this->elements['wrapper']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", 'flex-align' => "y-c"];
        $result->data = ['block_props' => $column_props];
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
                [   'type' => 'ShadowsGroup',
                    'name' => _w('Shadows'),
                ],
            ],
            'elements' => $this->elements,
            'semi_headers' => [
                'main' => _w('Whole column'),
                'wrapper' => _w('Container'),
            ]
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
