<?php
/**
 * Save new child blocks ordering for given parent block and child_key
 */
class siteEditorMoveBlockController extends waController
{
    public function execute()
    {
        if (!waLicensing::check('site')->isPremium()) {
            return;
        }
        $parent_block_id = waRequest::request('parent_block_id', null, 'int');
        if (!$parent_block_id || $parent_block_id < 0) {
            $parent_block_id = null;
        }
        $page_id = waRequest::request('page_id', null, 'int');
        $child_key = waRequest::request('child_key', '', 'string');
        $child_ids_order = waRequest::request('child_ids', '', 'array_int');

        $blockpage_blocks_model = new siteBlockpageBlocksModel();

        // Fetch all siblings
        $siblings = $blockpage_blocks_model->getByField([
            'parent_id' => $parent_block_id,
            'child_key' => $child_key,
            'page_id' => $page_id,
            'deleted' => 0,
        ], 'id');
        uasort($siblings, function($a, $b) {
            return ((int)$a['sort']) <=> ((int)$b['sort']);
        });

        $sort = 0;
        $new_order = array_keys(array_intersect_key(array_fill_keys($child_ids_order, true), $siblings) + $siblings);
        foreach ($new_order as $id) {
            if ($siblings[$id]['sort'] != $sort) {
                $blockpage_blocks_model->updateById($id, ['sort' => $sort]);
            }
            $sort++;
        }

        $page = new siteBlockPage($page_id);
        $page->updateDateTime();
        echo $page->renderBackend($parent_block_id);
    }

    protected function getSubtreeIds($parent_id, $page_blocks)
    {
        $result = $ids_to_check = [$parent_id => true];
        while ($ids_to_check) {
            $current_ids = $ids_to_check;
            $ids_to_check = [];
            foreach($page_blocks as $b) {
                if ($b['parent_id'] && isset($current_ids[$b['parent_id']])) {
                    $result[$b['id']] = $ids_to_check[$b['id']] = true;
                }
            }
        }
        return array_keys($result);
    }
}
