<?php
/**
 * Access to all blocks available for blockpages to use.
 */
class siteBlockpageLibrary
{
    public $all_blocks = null;
    public $all_elements = null;

    public function getById($id)
    {
        $blocks = array_merge($this->getAllBlocks(), $this->getAllElements());
        return ifset($blocks, $id, null);
    }

    public function getByTypeName($id)
    {
        $blocks = array_merge($this->getAllBlocks(), $this->getAllElements());
        foreach ($blocks as  $key=>$val){
            if (strpos($key, $id)!==false){
                return $val;
            }
        }
        return null;
    }

    public function getAllBlocks()
    {
        if ($this->all_blocks !== null) {
            return $this->all_blocks;
        }

        $blocks = $this->getSiteBlocks();

        $blocks = array_merge(array_values($blocks), $this->getThirdPartyBlocks());

        $result = [];
        foreach ($blocks as $b) {
            if (empty($b['data'])) {
                continue; // should not happen
            }
            if (empty($b['id']) || isset($result[$b['id']])) {
                $b['id'] = $this->hashBlockId($b['data']);
            }
            $b['tags'] = ifempty($b, 'tags', []);
            $result[$b['id']] = $b;
        }
        $this->all_blocks = $result;

        return $result;
    }

    /**
     * @deprecated !!! TODO remove
     */
    public function getAllElements(string $is_complex = '')
    {
        if ($this->all_elements !== null) {
            return $this->all_elements;
        }

        $blocks = $this->getAllBlocks();
        $blocks = array_filter($blocks, function($b) {
            return in_array('element', $b['tags']);
        });

        $result = [];
        foreach ($blocks as $b) {
            if (empty($b['data'])) {
                continue; // should not happen
            }
            if ($is_complex) {
                if ($is_complex == 'only_columns' && (empty($b['is_complex']) || $b['is_complex'] != 'column')) {
                    continue; // skip all except column elements
                }
                if ($is_complex == 'with_row' && !empty($b['is_complex']) && $b['is_complex'] != 'row') {
                    continue; // skip all complex elements except row
                }
                if ($is_complex == 'no_complex' && !empty($b['is_complex'])) {
                    continue; // skip all complex elements except row
                }
            }

            if (empty($b['id']) || isset($result[$b['id']])) {
                $b['id'] = $this->hashBlockId($b['data']);
            }
            $result[$b['id']] = $b;
        }
        $this->all_elements = $result;

        return $result;
    }

    protected function getSiteBlocks()
    {
        return [
            [
                'image' => '',
                'title' => _w('Site footer top block'),
                'data' => (new siteFooterTopBlockType(['columns' => 4]))->getExampleBlockData(),
                'tags' => ['category_footer'],
                'disabled' => false,
            ],
            [
                'image' => '',
                'title' => _w('Site footer bottom block'),
                'data' => (new siteFooterBottomBlockType())->getExampleBlockData(),
                'tags' => ['category_footer'],
                'disabled' => false,
            ],
            [
                'image' => '',
                'title' => _w('One column'),
                'data' => (new siteNewColumnsBlockType(['columns' => 1]))->getExampleBlockData(),
                'tags' => ['category_main_page'],
            ],
            [
                'image' => '',
                'title' => _w('Two columns'),
                'data' => (new siteNewColumnsBlockType(['columns' => 2]))->getExampleBlockData(),
                'tags' => ['category_main_page'],
            ],
            [
                'image' => '',
                'title' => _w('Three columns'),
                'data' => (new siteNewColumnsBlockType(['columns' => 3]))->getExampleBlockData(),
                'tags' => ['category_main_page'],
            ],
            [
                'image' => '',
                'title' => _w('Four columns'),
                'data' => (new siteNewColumnsBlockType(['columns' => 4]))->getExampleBlockData(),
                'tags' => ['category_main_page'],
            ],
            [
                'image' => '',
                'title' => _w('Cards'),
                'data' => (new siteCardsBlockType(['cards' => 6]))->getExampleBlockData(),
                'tags' => ['category_main_page'],
            ],
            [
                'image' => '',
                'title' => _w('Menu'),
                'data' => (new siteMenuBlockType(['columns' => 4]))->getExampleBlockData(),
                'tags' => ['category_menu'],
            ],
            [
                'image' => '',
                'icon' => 'code',
                'title' => _w('Custom Code'),
                'data' => (new siteCustomCodeBlockType(['is_block' => true]))->getExampleBlockData(),
                'tags' => ['category_main_page'],
            ],

            [
                'image' => '',
                'icon' => 'heading',
                'title' => _w('Heading'),
                'data' => (new siteHeadingBlockType())->getExampleBlockData(),
                'tags' => ['element'],
            ],
            [
                'image' => '',
                'icon' => 'paragraph',
                'title' => _w('Text'),
                'data' => (new siteParagraphBlockType())->getExampleBlockData(),
                'tags' => ['element'],
            ],
            [
                'image' => '',
                'icon' => 'square',
                'title' => _w('Button or link'),
                'data' => (new siteButtonBlockType())->getExampleBlockData(),
                'tags' => ['element'],
            ],
            [
                'image' => '',
                'icon' => 'image',
                'title' => _w('Image'),
                'data' => (new siteImageBlockType())->getExampleBlockData(),
                'tags' => ['element'],
            ],
            [
                'image' => '',
                'icon' => 'minus',
                'title' => _w('Horizontal ruler'),
                'data' => (new siteHrBlockType())->getExampleBlockData(),
                'tags' => ['element'],
            ],
            [
                'image' => '',
                'icon' => 'table',
                'title' => _w('Row'),
                'data' => (new siteRowBlockType())->getExampleBlockData(),
                'is_complex' => 'row',
                'tags' => ['element'],
            ],
            [
                'image' => '',
                'icon' => 'table',
                'title' => _w('Column'),
                'data' => (new siteNewColumnBlockType())->getExampleBlockData(),
                'is_complex' => 'column',
                'tags' => ['element'],
            ],
            [
                'image' => '',
                'icon' => 'code',
                'title' => _w('Custom Code'),
                'data' => (new siteCustomCodeBlockType(['is_block' => false]))->getExampleBlockData(),
                'tags' => ['element'],
            ],
        ];
    }

    protected function getThirdPartyBlocks()
    {
        $result = [];
        $plugin_results = wa('site')->event('blockpage_blocks');
        foreach ($plugin_results as $plugin_id => $blocks) {
            foreach ($blocks as $b) {
                if (empty($b['data'])) {
                    try {
                        if (empty($b['block_type'])) {
                            if (!empty($b['block_type_class']) && class_exists($b['block_type_class'])) {
                                $b['block_type'] = new $b['block_type_class'];
                            }
                        }
                        if (!empty($b['block_type'])) {
                            $b['data'] = $b['block_type']->getExampleBlockData();
                        }
                    } catch (Throwable $e) {
                        continue;
                    }
                }
                unset($b['block_type'], $b['block_type_class']);

                if (empty($b['data']) || !($b['data'] instanceof siteBlockData)) {
                    continue;
                }

                $result[] = $b + [
                    'image' => '',
                    'icon' => '',
                    'title' => get_class($b['data']->block_type),
                    'tags' => [],
                ];
            }
        }

        return $result;
    }

    protected function hashBlockId(siteBlockData $data)
    {
        $block_type_id = $data->block_type->getTypeId();
        $parts = [
            $block_type_id,
            json_encode($data->data),
        ];
        foreach($data->children as $child_key => $arr) {
            $parts[] = $child_key;
            foreach($arr as $d) {
                $parts[] = $this->hashBlockId($d);
            }
        }
        return $block_type_id.'_'.md5(join(';', $parts));
    }
}
