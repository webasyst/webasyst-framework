<?php
/**
 * Button (a, button, input[type=submit])
 */
class siteMenuItemBlockType extends siteBlockType
{
    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        $result->data = ['html' => 'Пункт меню', 'tag' => 'a', 'block_props' => ['border-radius' => "b-r-r", 'button-style' => "btn-clear t-rgl", 'button-size' => 'inp-s p-l-10 p-r-10']];
        return $result;
    }
    protected function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('Menu item'),
            'sections' => [
                [   'type' => 'ButtonLinkGroup',
                    'name' => _w('Action'),
                ],
                /*[   'type' => 'ButtonStyleGroup',
                    'name' => _w('Style'),
                ],*/
                [   'type' => 'ButtonSizeGroup',
                    'name' => _w('Size'),
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
            ],
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
