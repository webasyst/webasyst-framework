<?php
/**
 * Block has many 'slots' for files. Slots are named, a key-value hashmap.
 * Each file has a global id, not attached to a block.
 *
 * This table stores file metadata.
 * Files themselves are in wa-data/public/site/bpfiles/<>/<>/<id>/<name>.<ext>
 *
 * In order to support UNDO, files are not immediately deleted after user removes them in editor.
 * Files are marked for deletion, then a separate process cleans them up after a while.
 */
class siteBlockpageFileModel extends waModel
{
    protected $table = 'site_blockpage_file';

    public function getByBlocks($block_ids)
    {
        if (!$block_ids) {
            return [];
        }

        $file_ids = [];
        $sql = "SELECT * FROM site_blockpage_block_files WHERE block_id IN (?)";
        $rows = $this->query($sql, [$block_ids])->fetchAll();
        foreach($rows as $row) {
            $file_ids[$row['file_id']] = $row['file_id'];
        }

        $files = $this->getById(array_values($file_ids));

        $result = [];
        foreach($rows as $row) {
            if (isset($files[$row['file_id']])) {
                $result[$row['block_id']][$row['key']] = $files[$row['file_id']];
            }
        }

        return $result;
    }
}
