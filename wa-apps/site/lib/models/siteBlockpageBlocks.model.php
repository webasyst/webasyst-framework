<?php

class siteBlockpageBlocksModel extends waModel
{
    protected $table = 'site_blockpage_blocks';

    public function getByPage($page_id, $with_deleted = false)
    {
        if (!$page_id) {
            return [];
        }
        $deleted_sql = '';
        if (empty($with_deleted)){
            $deleted_sql = "AND `deleted` = 0";
        }
        $sql = "SELECT *
                FROM {$this->table}
                WHERE page_id IN (?) {$deleted_sql}
                ORDER BY page_id, parent_id, child_key, sort";
        return $this->query($sql, [$page_id])->fetchAll('id');
    }

    public function addToParent(siteBlockData $block_data, int $page_id, ?int $parent_block_id=null, string $child_key='', ?int $before_block_id=null, ?int $after_block_id=null)
    {
        // Create new block
        $blockpage_blocks_model = $this;
        $new_block_id = $blockpage_blocks_model->insert([
            'page_id' => $page_id,
            'parent_id' => $parent_block_id,
            'child_key' => $child_key,
            'type' => $block_data->block_type->getTypeId(),
            'data' => $block_data->getDataEncoded(),
            'sort' => 0, // will be updated below
        ]);

        // Create all children of new block, recursively
        foreach($block_data->children as $ck => $arr) {
            foreach($arr as $child) {
                $this->addToParent($child, $page_id, $new_block_id, $ck, null, null);
            }
        }

        // Fetch all siblings of new block to update their sort ordering
        $siblings = $blockpage_blocks_model->getByField([
            'page_id' => $page_id,
            'parent_id' => $parent_block_id,
            'child_key' => $child_key,
        ], true);
        uasort($siblings, function($a, $b) {
            return ((int)$a['sort']) <=> ((int)$b['sort']);
        });

        // Update sort order of new block and of all its siblings
        $sort = 0;
        $updated = false;
        $new_sort_values = [];
        foreach($siblings as $b) {
            if ($b['id'] == $new_block_id) {
                continue;
            }

            if (!$updated && $b['id'] == $before_block_id) {
                if ($sort !== 0) {
                    $blockpage_blocks_model->updateById($new_block_id, ['sort' => $sort]);
                }
                $updated = true;
                $sort++;
            }

            if ($sort != $b['sort']) {
                $blockpage_blocks_model->updateById($b['id'], ['sort' => $sort]);
            }
            $sort++;

            if (!$updated && $b['id'] == $after_block_id) {
                if ($sort !== 0) {
                    $blockpage_blocks_model->updateById($new_block_id, ['sort' => $sort]);
                }
                $updated = true;
                $sort++;
            }
        }

        // By default when $before_block_id and $after_block_id are not specified,
        // add block at the end of its parent
        if (!$updated && $sort !== 0) {
            $blockpage_blocks_model->updateById($new_block_id, ['sort' => $sort]);
        }

        return $new_block_id;
    }

    public function markAsDeleted($ids)
    {
        $this->updateById($ids, [
            'deleted' => 1,
        ]);
    }

    public function restoreDeleted($ids)
    {
        $this->updateById($ids, [
            'deleted' => 0,
        ]);
    }

    public function delete($ids)
    {
        $this->deleteById($ids);
        // !!! TODO: also delete images, files, etc.
    }
}
