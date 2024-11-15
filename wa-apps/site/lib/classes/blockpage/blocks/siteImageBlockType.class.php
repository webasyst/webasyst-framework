<?php
/**
 * Button (a, button, input[type=submit])
 */
class siteImageBlockType extends siteBlockType
{

    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        $result->data = [
            'block_props' => [
                'margin-bottom' => "m-b-14",
            ],
        ];
        return $result;
    }
    protected function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('Image'),
            'sections' => [
                [   'type' => 'ImageUploadGroup',
                    'name' => _w('Upload'),
                ],
                [   'type' => 'MarginGroup',
                    'name' => _w('Margin'),
                ],
                [   'type' => 'ShadowsGroup',
                    'name' => _w('Shadows'),
                ],
                [   'type' => 'BorderGroup',
                    'name' => _w('Border'),
                    'is_block' => true, //Exception IMG element
                ],
                [   'type' => 'BorderRadiusGroup',
                    'name' => _w('Angle'),
                ],
                [   'type' => 'VisibilityGroup',
                    'name' => _w('Visibility on devices'),
                ],
            ],
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
