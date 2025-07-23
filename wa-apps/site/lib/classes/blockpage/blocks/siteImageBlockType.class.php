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
            'tags' => 'element',
            'sections' => [
                [   'type' => 'ImageUploadGroup',
                    'name' => _w('Upload'),
                ],
                [   'type' => 'ButtonLinkGroup',
                    'name' => _w('Action'),
                    'is_hidden' => true, //Exception IMG element
                ],
                [   'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'BackgroundColorGroup',
                    'name' => _w('Background'),
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
                [   'type' => 'IdGroup',
                    'name' => _w('Identifier (ID)'),
                ],
                [   'type' => 'ImageSeoGroup',
                    'name' => _w('SEO'),
                ],

            ],
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
