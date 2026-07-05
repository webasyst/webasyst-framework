<?php
/**
 * Block containing custom code content.
 */
class siteCustomCodeBlockType extends siteBlockType
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
        $result->data = ['html' => 'Custom <strong>code</strong> <div class="custom-code-class">content</div>', 'css' => '.custom-code-class { color: red; }', 'js' => 'let wrapper_code = document.querySelector(".custom-code-class"); console.log(wrapper_code)'];
        $result->data['is_block'] = ifset($this->options, 'is_block', false);
        return $result;
    }

    protected function getRawBlockSettingsFormConfig()
    {
        $is_block = ifset($this->options, 'is_block', false);
        $tags = $is_block ? 'block' : 'element';
        return [
            'type_name' => _w('Custom code'),
            'tags' => $tags,
            'sections' => [
                [   'type' => 'CustomCodeGroup',
                    'name' => _w('Custom code'),
                ]
            ],
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
