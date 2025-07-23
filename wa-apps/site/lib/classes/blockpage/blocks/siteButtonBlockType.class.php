<?php
/**
 * Button (a, button, input[type=submit])
 */
class siteButtonBlockType extends siteBlockType
{
    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        $result->data = ['html' => 'Exercitation', 'tag' => 'a', 'block_props' => ['margin-bottom' => "m-b-12", 'button-style' => ['name' => "complementary", 'scheme' => 'complementary', 'value' => "btn-a", 'type' => 'palette'], 'button-size' => 'inp-m p-l-13 p-r-13']];
        return $result;
    }
    protected function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('Button or link'),
            'tags' => 'element',
            'sections' => [
                [   'type' => 'ButtonLinkGroup',
                    'name' => _w('Action'),
                ],
                [   'type' => 'ButtonStyleGroup',
                    'name' => _w('Style'),
                ],
                [   'type' => 'ButtonToggleGroup',
                    'name' => _w('Button toggle'),
                ],
                [   'type' => 'ButtonSizeGroup',
                    'name' => _w('Size'),
                ],
                [   'type' => 'FullWidthToggleGroup',
                    'name' => _w('Full width'),
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
