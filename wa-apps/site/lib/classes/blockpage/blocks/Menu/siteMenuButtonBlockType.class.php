<?php
/**
 * Button (a, button, input[type=submit])
 */
class siteMenuButtonBlockType extends siteBlockType
{
    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();

        $color = ifset($this->options['color']);
        $svg_color = $color === 'white' ? 'var(--white)' : 'var(--black)';

        $result->data = [
            'image' => [
                'color' => [
                    'name' => 'Palette',
                    'value' => $color === 'white' ? 'tx-wh' : 'tx-blc',
                    'type' => 'palette'
                ],
                'fill' => 'removed',
                'open_menu_svg_html' =>
                    '<svg viewBox="0 0 16 16" fill="'.$svg_color.'" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15.2914 1.56903L0.70099 1.56903C0.313844 1.56903 -3.61259e-08 1.88288 0 2.27002C3.61259e-08 2.65717 0.313844 2.97101 0.70099 2.97101L15.2914 2.97101C15.6786 2.97101 16 2.65717 16 2.27002C16 1.88287 15.6786 1.56903 15.2914 1.56903Z"></path>
                        <path d="M15.2914 7.29901L0.70099 7.29902C0.313844 7.29902 -3.61259e-08 7.61286 0 8.00001C3.61259e-08 8.38715 0.313844 8.70099 0.70099 8.70099L15.2914 8.70099C15.6786 8.70099 16 8.38715 16 8C16 7.61285 15.6786 7.29901 15.2914 7.29901Z"></path>
                        <path d="M15.2914 13.0286L0.70099 13.0286C0.313844 13.0286 -3.61259e-08 13.3424 0 13.7296C3.61259e-08 14.1167 0.313844 14.4305 0.70099 14.4305L15.2914 14.4305C15.6786 14.4305 16 14.1167 16 13.7296C16 13.3424 15.6786 13.0286 15.2914 13.0286Z"></path>
                    </svg>',
                'close_menu_svg_html' =>
                    '<svg viewBox="0 0 16 16" fill="'.$svg_color.'" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.33952 1.3482L8 7.00848L13.6605 1.3482C13.9342 1.07444 14.3781 1.07444 14.6518 1.3482C14.9256 1.62195 14.9256 2.06579 14.6518 2.33955L8.99154 8.00003L14.6518 13.6605C14.9256 13.9343 14.9256 14.3781 14.6518 14.6519C14.3781 14.9256 13.9342 14.9256 13.6605 14.6519L8 8.99157L2.33952 14.6519C2.06576 14.9256 1.62192 14.9256 1.34817 14.6519C1.07441 14.3781 1.07441 13.9343 1.34817 13.6605L7.00845 8.00003L1.34817 2.33955C1.07441 2.06579 1.07441 1.62195 1.34817 1.3482C1.62192 1.07444 2.06576 1.07444 2.33952 1.3482Z"></path>
                    </svg>',
            ],
            'block_props' => [
                'visibility' => 'd-n-ds d-n-lp',
                'margin-bottom' => "m-b-8-mb",
                'picture-size' => "i-s",
            ],
        ];
        return $result;
    }
    protected function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('Menu button design'),
            'tags' => 'element',
            'sections' => [
                [   'type' => 'MenuButtonGroup',
                    'name' => _w('Menu button'),
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
            ],
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
