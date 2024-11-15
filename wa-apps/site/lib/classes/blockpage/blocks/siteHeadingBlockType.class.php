<?php
/**
 * Heading (h1, h2, ...)
 */
class siteHeadingBlockType extends siteBlockType
{
    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        $result->data = ['html' => 'Excepteur', 'tag' => 'h2', 'block_props' => ['font-header' => "t-hdn", 'font' => "t-2", 'margin-top' => "m-t-0", 'margin-bottom' => "m-b-8", 'align' => "t-l"]];
        return $result;
    }
    protected function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('Heading'),
            'sections' => [
                [   'type' => 'FontHeaderGroup',
                    'name' => _w('Font header'),
                ],
                [   'type' => 'FontGroup',
                    'name' => _w('Font'),
                ],
                [   'type' => 'FontStyleGroup',
                    'name' => _w('Font style'),
                ],
                [   'type' => 'TextColorGroup',
                    'name' => _w('Color'),
                ],
                [   'type' => 'LineHeightGroup',
                    'name' => _w('Line height'),
                ],
                [   'type' => 'AlignGroup',
                    'name' => _w('Alignment'),
                ],
                [   'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'MarginGroup',
                    'name' => _w('Margin'),
                ],
                [   'type' => 'ShadowsGroup',
                    'name' => _w('Shadows'),
                ],
                [   'type' => 'VisibilityGroup',
                    'name' => _w('Visibility on devices'),
                ],
                [   'type' => 'TagsGroup',
                    'name' => _w('SEO'),
                ],
            ],
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
