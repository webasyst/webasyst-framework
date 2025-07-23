<?php
/**
 * Creates a new block of specified type and attaches to parent at specified place.
 * Outputs HTML of parent block.
 */
class siteEditorAddBlockController extends waController
{
    public function execute()
    {
        $blockpage_blocks_model = new siteBlockpageBlocksModel();

        $duplicate_block_id = waRequest::request('duplicate_block_id', null, 'string');
        if ($duplicate_block_id) {
            $original_block = $blockpage_blocks_model->getById($duplicate_block_id);
            if (!$original_block || $original_block['deleted']) {
                throw new waException('block not found', 400);
            }

            $page_id = $original_block['page_id'];
            $parent_block_id = $original_block['parent_id'];
            $child_key = $original_block['child_key'];
            $after_block_id = $original_block['id'];
            $before_block_id = null;
        } else {
            $type_id = waRequest::request('type_id', null, 'string');
            $type_name = waRequest::request('type_name', null, 'string');
            $parent_block_id = waRequest::request('parent_block_id', null, 'int');
            $before_block_id = waRequest::request('before_block_id', null, 'int');
            $after_block_id = waRequest::request('after_block_id', null, 'int');
            $child_key = waRequest::request('child_key', '', 'string');

            list($parent_block, $page_id) = siteEditorAddBlockDialogAction::getParentBlockFromParams($parent_block_id, $before_block_id, $after_block_id);
            $parent_block_id = ifset($parent_block, 'id', null);
            if (empty($parent_block) && empty($page_id)) {
                $page_id = waRequest::request('page_id', null, 'int');
            }
        }

        if ($page_id) {
            $parent_page = (new siteBlockpageModel())->getById($page_id);
        }
        if (empty($parent_block) && empty($parent_page)) {
            throw new waException('bad parameters', 400);
        }
        $library = new siteBlockpageLibrary();
        if ($duplicate_block_id) {
            $block_type = siteBlockType::factory($original_block['type']);
            $block_data = $block_type->getEmptyBlockData()->setDbRow(['id' => null] + $original_block);
            //if ((strpos($original_block['type'], 'site.Columns.') !== false)) {
            $this->copyChildrenBlocks($blockpage_blocks_model, $block_data, $original_block['id']);
            //}
        } else {
            //$library = new siteBlockpageLibrary();
            if ($type_id) {
                $block_type_info = $library->getById($type_id);
            }
            elseif ($type_name) {
                $block_type_info = $library->getByTypeName($type_name);
            }

            if (empty($block_type_info['data'])) {
                throw new waException('unknown type_id', 400);
            }
            $block_data = $block_type_info['data'];
        }

        $new_block_id = $blockpage_blocks_model->addToParent($block_data, $parent_page['id'], $parent_block_id, $child_key, $before_block_id, $after_block_id);

        $page = new siteBlockPage($parent_page);
        $page->updateDateTime();
        echo $page->renderBackend($parent_block_id);
        echo $this->getUndoScript($parent_block_id, $new_block_id);
    }

    protected function getUndoScript($parent_block_id, $new_block_id)
    {
        if ($parent_block_id) {
            $parent_block_id = (int) $parent_block_id;
        }
        $parent_block_id = json_encode($parent_block_id);
        $new_block_id = json_encode((int)$new_block_id);

        return <<<EOF
<script>(function() { "use strict";
    var wrapper;
    if ({$parent_block_id}) {
        wrapper = $('.js-seq-wrapper[data-block-id="{$parent_block_id}"]');
    } else {
        wrapper = $('#js-global-seq');
    }
    $.wa.editor.api.addBlockUndoOperation({$new_block_id}, wrapper);

    setTimeout(() => { //set NEW block selected
        if ({$parent_block_id}) {
            var parent_wrapper = wrapper.parent().closest('*[data-block-id]');
            var parent_wrapper_id = parent_wrapper.data('block-id');
            var parent_block_data = $.wa.editor.api.block_storage.getData(parent_wrapper_id);
            if (parent_block_data) window.updateBlockStyles(parent_wrapper, parent_block_data, parent_wrapper_id);
        }
        var block_wrapper = wrapper.find('.seq-child[data-block-id="{$new_block_id}"]');
        $.wa.editor.setSelectedBlock({$new_block_id}, block_wrapper, null, true);
    }, 0);
})();</script>
EOF;
    }

    private function copyChildrenBlocks(siteBlockpageBlocksModel $blockpage_blocks_model, siteBlockData $block_data, $parent_id)
    {
        $columns = $blockpage_blocks_model->getByField([
            'parent_id' => $parent_id,
            'deleted' => '0',
        ], true);
        if (empty($columns)) {
            return false;
        }

        $addChild = function (array $block, siteBlockData $parent_block_data) {
            $block_data = siteBlockType::factory($block['type'])->getEmptyBlockData()->setDbRow(['id' => null] + $block);
            $parent_block_data->addChild($block_data, $block['child_key']);
            return $block_data;
        };

        foreach ($columns as $col) {
            $col_data = $addChild($col, $block_data);

            $v_sequences = $blockpage_blocks_model->getByField([
                'parent_id' => $col['id'],
                'deleted' => '0',
            ], true);
            if (empty($v_sequences)) {
                continue;
            }

            foreach ($v_sequences as $v_sequence) {
                $v_sequence_data = $addChild($v_sequence, $col_data);

                $elements = $blockpage_blocks_model->getByField([
                    'parent_id' => $v_sequence['id'],
                    'deleted' => '0',
                ], true);
                if (empty($elements)) {
                    continue;
                }
                foreach ($elements as $el) {

                    if ($el['type'] == 'site.VerticalSequence') {
                        $this->copyChildrenBlocks($blockpage_blocks_model, $v_sequence_data, $v_sequence['id']);
                    } else {
                        $addChild($el, $v_sequence_data);
                    }
                }
            }
        }

    }
}
