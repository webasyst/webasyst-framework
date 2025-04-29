<?php
/**
 * HTML for a dropdown list to select element to add inside a block.
 */
class siteEditorAddElementsListAction extends siteEditorAddBlockDialogAction
{
    protected function getLibraryContents($parent_block)
    {
        $complex_param = ''; //type String = '' | 'only_columns' | 'with_row' | 'no_complex'
        $library = new siteBlockpageLibrary();

        if (!empty($parent_block)) {
            //need for show special elements in dropdown
            if (!empty($parent_block['data']) && !empty(json_decode($parent_block['data'])->is_complex)) {
                $complex_param = json_decode($parent_block['data'])->is_complex;
            }
        }

        $blocks = $library->getAllElements($complex_param);
        $categories = $this->getCategories($parent_block);

        $result = [];
        foreach ($blocks as $b) {
            $add_global = true;
            foreach ($categories as &$c) {
                if ($this->goesInCategory($b, $c)) {
                    $c['blocks'][] = $b;
                    $add_global = false;
                }
            }
            if ($add_global && !in_array('hidden', $b['tags'])) {
                $result[] = $b;
            }
        }
        unset($c);

        foreach ($categories as $c) {
            if ($c['blocks']) {
                $result[] = $c;
            }
        }

        return $result;
    }

    protected function goesInCategory($b, $c)
    {
        if (isset($c['callback']) && is_callable($c['callback'])) {
            return !!$c['callback']($b);
        }
        foreach ($c['tags'] as $c) {
            if (!in_array($c, $b['tags'])) {
                return false;
            }
        }
        return true;
    }

    protected function getCategories($parent_block)
    {
        $event_result = wa()->event('blockpage_elements_list', ref([
            'parent_block' => $parent_block,
        ]));

        $result = [
            [
                'title' => _w('Web form'),
                'icon' => 'clipboard-list',
                'tags' => ['form'],
                'blocks' => [],
            ],
        ];
        foreach ($event_result as $r) {
            if (isset($r['title'])) {
                $r = [$r];
            }
            foreach ($r as $category) {
                if (empty($category['title'])) {
                    continue;
                }
                $category['tags'] = (array) ifset($category, 'tags', []);
                $result[] = [
                    'title' => $category['title'],
                    'icon' => ifset($category, 'icon', null),
                    'app_icon' => ifset($category, 'app_icon', null),
                    'tags' => $category['tags'],
                    'callback' => ifset($category, 'callback', null),
                    'blocks' => [],
                ];
            }
        }

        return $result;
    }
}
