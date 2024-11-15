<?php
/**
 * Block containing custom code content.
 */
class siteCustomCodeBlockType extends siteBlockType
{
    public function __construct(array $options=[])
    {
        parent::__construct($options);

    }

    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        $result->data = ['html' => 'Custom <strong>Code</strong> <div class="custom-code-class">content</div>.', 'css' => '.custom-code-class { color: red }', 'js' => 'let wrapper_code = document.querySelector(".custom-code-class"); console.log(wrapper_code)'];
        $result->data['is_block'] = ifset($this->options, 'is_block', false);
        return $result;
    }
    protected function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('CustomCode'),
            'sections' => [
                [   'type' => 'CustomCodeGroup',
                    'name' => _w('Custom code'),
                ]
            ],
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
