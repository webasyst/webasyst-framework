<?php
/**
 * Restores a block previously marked as deleted
 */
class siteEditorRestoreBlockController extends siteEditorDeleteBlockController
{
    public function execute()
    {
        $block_id = waRequest::request('block_id', null, 'int');
        $blockpage_blocks_model = new siteBlockpageBlocksModel();
        $target_block = $blockpage_blocks_model->getById($block_id);
        if (!$target_block) {
            return;
        }

        $page_id = $target_block['page_id'];
        $page_blocks = $blockpage_blocks_model->getByPage($page_id, true);
        $block_ids = $this->getSubtreeIds($block_id, $page_blocks);
        $blockpage_blocks_model->restoreDeleted($block_ids);

        $page = new siteBlockPage($page_id);
        $page->updateDateTime();
        echo $page->renderBackend(ifempty($target_block, 'parent_id', null));
    }
}
