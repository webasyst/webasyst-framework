<?php
/**
 * Block containing custom Video content.
 */
class siteVideoBlockType extends siteBlockType
{
    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars=[])
    {
        if (!$is_backend) {
            $data->data['html'] = $this->renderSmarty($data->data['html']);
        }
        return parent::render($data, $is_backend, $tmpl_vars);
    }

    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        //$default_url = '<iframe src=""></iframe>';
        $default_url = '';

        $result->data = ['html' => $default_url, 'block_props' => ['margin-bottom' => "m-b-16"]];
        return $result;
    }

    protected function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('Video'),
            'tags' => 'element',
            'sections' => [
                [   'type' => 'VideoUploadGroup',
                    'name' => _w('Upload'),
                ],
                [   'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'MarginGroup',
                    'name' => _w('Margin'),
                ],
                [   'type' => 'BorderGroup',
                    'name' => _w('Border'),
                    'is_block' => true, //Exception Row element
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
            ],
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
