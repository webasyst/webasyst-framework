<?php
/**
 * List <ul/ol/li>
 */
class siteListBlockType extends siteBlockType
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
        $result->data = ['html' => '<li>Phasellus porttitor justo ultrices</li><li>Libero tincidunt varius odio</li><li>Purus luctus enim egestas</li>', 'tag' => 'ul', 'block_props' => ['font-header' => "t-rgl", 'font-size' => ["name" => "Size #6", "value" => "t-6", "unit" => "px", "type" => "library"], 'margin-top' => "m-t-0", 'margin-bottom' => "m-b-14", 'align' => "t-l"]];

        return $result;
    }

    protected function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('List'),
            'tags' => 'element',
            'sections' => [
                [   'type' => 'ListStyleGroup',
                    'name' => _w('Style'),
                ],
                [   'type' => 'FontGroup',
                    'name' => _w('Font'),
                ],
                [   'type' => 'TextColorGroup',
                    'name' => _w('Color'),
                ],
                [   'type' => 'FontStyleGroup',
                    'name' => _w('Font style'),
                ],
                [   'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'MarginGroup',
                    'name' => _w('Margin'),
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
