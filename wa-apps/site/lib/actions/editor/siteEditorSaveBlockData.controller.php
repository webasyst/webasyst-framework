<?php
/**
 * Save block data (if came) then rerender the block
 */
class siteEditorSaveBlockDataController extends waJsonController
{
    public function execute()
    {
        $block_id = waRequest::request('block_id', null, 'int');
        $data = waRequest::post('data', null, 'string');
        $blockpage_blocks_model = new siteBlockpageBlocksModel();
        $target_block = $blockpage_blocks_model->getById($block_id);
        if (!$target_block) {
            throw new waException('Unknown block');
        }

        if ($data !== null) {
            $blockpage_blocks_model->updateById($block_id, [
                'data' => $data,
            ]);
        }

        $this->response = [
            'undo' => [
                'url' => wa()->getAppUrl(null, true).'?module=editor&action=saveBlockData',
                'post' => [
                    'mode' => 'set',
                    'block_id' => $target_block['id'],
                    'data' => $target_block['data'],
                ],
            ],
            //'html' => (new siteBlockPage($target_block['page_id']))->renderBackend($block_id),
        ];
    }
}
