<?php
/**
 * Horizontal ruler (hr)
 */
class siteHrBlockType extends siteBlockType
{
    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        $result->data = ['tag' => 'hr', 'block_props' => ['margin-top' => "m-t-20", 'margin-bottom' => "m-b-20", 'border-width' => ['value' => 'b-w-s', 'name' => _w('Width 1'), 'unit' => "px", 'type' => 'library'], 'border-color' => [ 'css' => '#0000001a','name' => '1-1', 'type' => 'palette', 'value' => 'bd-tr-1' ]]];
        //$result->data['inline_props'] = ['border-width' => ['value' => '3px', 'name' => 'Толщина 3', 'unit' => "px", 'type' => 'self_size']];
        return $result;
    }
    protected function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('Horizontal ruler'),
            'tags' => 'element',
            'sections' => [
                [   'type' => 'BorderGroup',
                    'name' => _w('Border'),
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
                [   'type' => 'IdGroup',
                    'name' => _w('Identifier (ID)'),
                ],
            ],
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
