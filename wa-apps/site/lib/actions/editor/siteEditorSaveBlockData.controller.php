<?php
/**
 * Save block data (if came) then rerender the block
 */
class siteEditorSaveBlockDataController extends waJsonController
{
    public function execute()
    {
        if (!waLicensing::check('site')->isPremium()) {
            return;
        }
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

        $has_unsaved_changes = true;
        $new_datetime = $old_datetime = '';
        $setdt = waRequest::post('setdt', null, 'string');
        $ifdt = waRequest::post('ifdt', null, 'string');
        $page = new siteBlockPage($target_block['page_id']);
        try {
            if (!$ifdt || !$setdt || $page->data['update_datetime'] !== $ifdt) {
                $setdt = null;
            }
            list($new_datetime, $old_datetime) = $page->updateDateTime($setdt);
            $has_unsaved_changes = $new_datetime !== $page->data['create_datetime'];
        } catch (Throwable $e) {
        }

        try {
            list($blocks, $block_types) = $page->prepareBlocksForRender(true, $block_id);
            if ($blocks) {
                $block_data = reset($blocks)->ensureAdditionalData();
                $additional_data = ifempty($block_data->data, 'additional', null);

                if ($block_data->block_type->shouldRenderBlockOnSave(
                    json_decode($target_block['data'], true),
                    $block_data->data
                )) {
                    $block_html = $page->renderBackend($block_id);
                }
            }
        } catch (Throwable $e) {
            $debug_error = join("\n", [
                $e->getMessage().' ('.$e->getCode().')',
                $e instanceof waException ? $e->getFullTraceAsString() : $e->getTraceAsString()
            ]);
        }

        $this->response = [
            'page_has_unsaved_changes' => $has_unsaved_changes,
            'additional_data' => ifset($additional_data),
            'undo' => [
                'url' => wa()->getAppUrl(null, true).'?module=editor&action=saveBlockData',
                'post' => [
                    'mode' => 'set',
                    'block_id' => $target_block['id'],
                    'data' => $target_block['data'],
                    'setdt' => $old_datetime,
                    'ifdt' => $new_datetime,
                ],
            ],
        ];
        if (isset($block_html)) {
            $this->response['html'] = $block_html;
        }
        if (isset($debug_error) && SystemConfig::isDebug()) {
            $this->response['debug_error'] = $debug_error;
        }
    }
}
