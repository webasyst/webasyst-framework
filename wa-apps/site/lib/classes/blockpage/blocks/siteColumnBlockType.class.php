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
        $column_props[$this->elements['main']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", 'padding-left' => "p-l-clm", 'padding-right' => "p-r-clm"];
        $column_props[$this->elements['wrapper']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", 'flex-align' => "y-c"];
        $column_wrapper_class = 'st-3 st-6-lp st-6-tb st-12-mb';
        $result->data = ['block_props' => $column_props];
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
                [   'type' => 'ColumnsAlignGroup',
                    'name' => _w('Horizontal alignment'),
                ],
                [   'type' => 'ColumnAlignVerticalGroup',
                    'name' => _w('Vertical alignment'),
                ],
                [   'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'CommonLinkGroup',
                    'name' => _w('Link or action'),
                    'is_hidden' => true,
                ],
                [   'type' => 'ColumnWidthGroup',
                    'name' => _w('Width limit'),
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
                [   'type' => 'ShadowsGroup',
                    'name' => _w('Shadows'),
                ],
                [   'type' => 'VisibilityGroup',
                    'name' => _w('Visibility on devices'),
                ],
                [   'type' => 'IdGroup',
                    'name' => _w('Identifier (ID)'),
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
