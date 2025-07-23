<?php
/**
 * Deletes a block by id (marks as deleted with a possibility to restore later)
 */
class siteEditorDeleteBlockController extends waController
{
    public function execute()
    {
        if (!waLicensing::check('site')->isPremium()) {
            return;
        }
        $block_id = waRequest::request('block_id', null, 'int');
        $blockpage_blocks_model = new siteBlockpageBlocksModel();
        $target_block = $blockpage_blocks_model->getById($block_id);
        if (!$target_block) {
            return;
        }

        $page_id = $target_block['page_id'];
        $page_blocks = $blockpage_blocks_model->getByPage($page_id);
        $block_ids = $this->getSubtreeIds($block_id, $page_blocks);
        $blockpage_blocks_model->markAsDeleted($block_ids);

        $page = new siteBlockPage($page_id);
        $page->updateDateTime();
        echo $page->renderBackend(ifempty($target_block, 'parent_id', null));
    }

    // also used in siteEditorRestoreBlockController
    protected function getSubtreeIds($parent_id, $page_blocks)
    {
        // only mark parent as deleted, keep children as
        return [$parent_id];

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
