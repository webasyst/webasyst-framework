<?php
/**
 * Button (a, button, input[type=submit])
 */
class siteButtonBlockType extends siteBlockType
{
    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        $result->data = ['html' => 'Ссылка в виде кнопки', 'tag' => 'a', 'block_props' => ['border-radius' => "b-r-r", 'margin-bottom' => "m-b-12", 'button-style' => "bg-brn-1 t-wht b-r-r", 'button-size' => 'inp-m p-l-13 p-r-13']];
        return $result;
    }
    protected function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('Button or link'),
            'sections' => [
                [   'type' => 'ButtonLinkGroup',
                    'name' => _w('Action'),
                ],
                [   'type' => 'ButtonStyleGroup',
                    'name' => _w('Style'),
                ],
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