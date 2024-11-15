<?php
/**
 * Block has many 'slots' for files. Slots are named, a key-value hashmap.
 * Each file has a global id, not attached to a block.
 * This table connects files to blocks.
 */
class siteBlockpageBlockFilesModel extends waModel
{
    protected $table = 'site_blockpage_block_files';
}
