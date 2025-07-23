<?php
/**
 * Blocks from apps.
 */
class siteAppsBlockType extends siteBlockType
{
    public $elements = [
        'main' => 'site-apps-block',
        'wrapper' => 'site-apps-block-wrapper',
    ];

    public function prepareBlockData($block)
    {
        $html_block = new siteAppsHtmlBlockType();

        $app_icon = '';
        if (!empty($block['app_icon']['16'])) {
            $app_icon = $block['app_icon']['16'];
        } elseif (!empty($block['app']['icon']['16'])) {
            $app_icon = $block['app']['icon']['16'];
        }
        if ($app_icon) {
            $app_icon = '<img src="'.(wa_url().$app_icon).'" class="icon size-48 custon-mr-4">';
        }

        $id = $block['id'];
        $description= ifset($block, 'description', '');
        $preview_html = <<<HTML
        {$app_icon}
        <h5 class="custom-my-12"></i>{$id}</h5>
        <div class="gray">{$description}</div>
        HTML;

        $block_data = $html_block->fillBlockData([
            'preview_html' => $preview_html,
            'html' => ifset($block, 'content', '')
        ]);

        $result = $this->getEmptyBlockData();
        $result->addChild($block_data, '');
        $column_props = array();
        $column_props[$this->elements['main']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", 'padding-left' => "p-l-clm", 'padding-right' => "p-r-clm"];
        $column_props[$this->elements['wrapper']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", 'flex-align' => "y-c"];
        $result->data = ['block_props' => $column_props];
        $result->data['elements'] = $this->elements;

        return $result;
    }

    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars=[])
    {
        return parent::render($data, $is_backend, $tmpl_vars + [
            'children' => array_reduce($data->getRenderedChildren($is_backend), 'array_merge', []),
        ]);
    }

    protected function getRawBlockSettingsFormConfig()
    {
        return [
            'type_name' => _w('Apps block'),
            'sections' => [
            ],
            'elements' => $this->elements,
        ] + parent::getRawBlockSettingsFormConfig();
    }

    public function getBlocksForAdd()
    {
        $library_blocks = [];
        $blocks = siteHelper::getBlocks();
        if ($blocks) {
            foreach ($blocks as $block) {
                $library_blocks[] = [
                    'image' => '',
                    'title' => $block['id'] . (!empty($block['description']) ? " ({$block['description']})"  : ''),
                    'data' => $this->prepareBlockData($block),
                ];
            }

        }

        return $library_blocks;
    }
}
