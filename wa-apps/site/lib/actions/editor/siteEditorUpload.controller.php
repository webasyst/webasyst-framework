<?php
/**
 * Responsible for DO, UNDO and REDO operations that concern uploading files to blockpage blocks.
 *
 * Block has many 'slots' for files. Slots are named, a key-value hashmap.
 * Each file has a global id, not attached to a block.
 *
 * DO operation: uploads a file and puts it into a slot. Marks previous file in that slot as deleted (if exists and not used elsewhere).
 * Returns previous file_id in response data - it is required for UNDO operation.
 * DO operation may result in an error if file could not be saved due to permissions, security issue
 * or some other server problem.
 *
 * UNDO: Removes file from a slot and marks file as deleted unless used in some other block. Restores another file in slot,
 * using provided file_id from POST data. Return file_id in response data - it is required for REDO operation.
 *
 * REDO: Restores given file_id in given slot. Marks previous file in that slot as deleted (if exists and not used elsewhere).
 * Returns previous file_id in response data - it is required for UNDO operation.
 */
class siteEditorUploadController extends waJsonController
{
    public function execute()
    {
        if (!waLicensing::check('site')->isPremium()) {
            return;
        }
        $this->errors = [];

        // Block we're about to attach a file to. Always required.
        $block_id = waRequest::request('block_id', null, 'int');

        // Block slot key. Always used, but may be empty string.
        $file_key = waRequest::request('key', '', 'string');

        // Uploaded file. If it exists, then this is a DO operation.
        $file = waRequest::file('file');

        // id of a file we need to restore into a slot during UNDO or REDO operation.
        // Only used for UNDO or REDO (i.e. $file is not uploaded). Not required for UNDO
        // in case file used to be uploaded into an empty slot (no old file there).
        $file_id = waRequest::request('file_id', null, 'int');

        if (!$block_id) {
            $this->errors = [
                'error_code' => 'block_id_required',
                'error_message' => 'block_id is required',
            ];
            return;
        }

        $must_undelete_file = true;
        if ($file->uploaded()) {
            // This is a DO operation: upload a file and attach it to a block into a given $file_key slot
            $file_id = $this->processFile($file, $file_key);
            $must_undelete_file = false;
            if (!$file_id) {
                return; // unable to save uploaded file; $this->errors is set by ->processFile()
            }
        }

        //
        // Need to put $file_id (if present; it may not in case it's an UNDO operation with a slot
        // that used to be empty before the original operation) into $block_id slot $file_key.
        // Previous file in that slot must be marked as deleted unless used in some other block.
        // During DO operation $file_id is a file we just uploaded.
        // During UNDO or REDO it is some other old $file_id that used to be there before.
        //

        // Find previous file that is currently in the slot
        $blockpage_block_files_model = new siteBlockpageBlockFilesModel();
        $blockpage_file_model = new siteBlockpageFileModel();
        $row = $blockpage_block_files_model->getByField([
            'block_id' => $block_id,
            'key' => $file_key,
        ]);
        $existing_file_id = ifset($row, 'file_id', null);

        if ($existing_file_id) {
            // Remove previous file from the slot
            $blockpage_block_files_model->deleteByField([
                'block_id' => $block_id,
                'key' => $file_key,
            ]);

            // The file we just removed from the slot should be marked as deleted if not used elsewhere.
            $count = $blockpage_block_files_model->countByField([
                'file_id' => $existing_file_id,
            ]);
            if ($count <= 0) {
                $blockpage_file_model->updateById($existing_file_id, [
                    'delete_datetime' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // Add file to the slot
        if ($file_id) {
            $blockpage_block_files_model->insert([
                'block_id' => $block_id,
                'file_id' => $file_id,
                'key' => $file_key,
            ]);

            if ($must_undelete_file) {
                // The file we just added to the slot should not be marked as deleted anymore.
                $blockpage_file_model->updateById($file_id, [
                    'delete_datetime' => null,
                ]);
            }

            $file_data = $blockpage_file_model->getById($file_id);
            $file_data['url'] = siteBlockData::getBlockpageFileUrl($file_data);
        }

        $has_unsaved_changes = true;
        $new_datetime = $old_datetime = '';
        $blockpage_blocks_model = new siteBlockpageBlocksModel();
        $target_block = $blockpage_blocks_model->getById($block_id);
        if ($target_block) {
            $setdt = waRequest::post('setdt', null, 'string');
            $ifdt = waRequest::post('ifdt', null, 'string');
            try {
                $page = new siteBlockPage($target_block['page_id']);
                if (!$ifdt || !$setdt || $page->data['update_datetime'] !== $ifdt) {
                    $setdt = null;
                }
                list($new_datetime, $old_datetime) = $page->updateDateTime($setdt);
                $has_unsaved_changes = $new_datetime !== $page->data['create_datetime'];
            } catch (Throwable $e) {
            }
        }

        $this->response = [
            'file' => ifset($file_data),
            'page_has_unsaved_changes' => $has_unsaved_changes,
            'undo' => [
                'url' => wa()->getAppUrl(null, true).'?module=editor&action=upload',
                'post' => [
                    'key' => $file_key,
                    'block_id' => $block_id,
                    'file_id' => $existing_file_id,
                    'setdt' => $old_datetime,
                    'ifdt' => $new_datetime,
                ],
            ],
        ];
    }

    protected function isValid($f)
    {
        $ext = $f->extension;
        if (strpos(strtolower($f->name), '.php') !== false) {
            if (strtolower($ext) != 'php') {
                $ext = 'php';
            }
        }
        if (in_array(strtolower($ext), array('php', 'phtml', 'htaccess', 'phar'))) {
            $this->errors = [
                'error_code' => 'extension_not_allowed',
                'error_message' => sprintf(_ws("Files with the .%s extension are not allowed to upload due to security considerations."), $ext),
            ];
            return false;
        }
        return true;
    }

    /**
     * @param waRequestFile $f
     * @return bool
     */
    protected function processFile(waRequestFile $f, $file_key)
    {
        if (!$this->isValid($f)) {
            return false;
        }

        $is_image = in_array(strtolower($f->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if ($is_image) {
            try {
                $image = $f->waImage();
                $image_width = $image->width;
                $image_height = $image->height;
                unset($image);
            } catch (waException $e) {
                $is_image = false;
            }
        }

        $safe_filename = $this->transliterateFilename($f, $is_image);
        $blockpage_file_model = new siteBlockpageFileModel();
        $file_id = $blockpage_file_model->insert([
            'contact_id' => wa()->getUser()->getId(),
            'create_datetime' => date('Y-m-d H:i:s'),
            'name' => $safe_filename,
            'ext' => $f->extension,
            'orig_name' => $f->name,
            'size' => $f->size,
            'width' => $is_image ? $image_width : null,
            'height' => $is_image ? $image_height : null,
        ]);
        $path = siteBlockData::getBlockpageFilePath([
            'name' => $safe_filename,
            'ext' => $f->extension,
            'id' => $file_id,
        ]);

        waFiles::create($path);
        if (!is_writable(dirname($path))) {
            $blockpage_file_model->deleteById($file_id);
            $p = dirname(substr($path, strlen(wa()->getDataPath('', true))));
            $this->errors = [
                'error_code' => 'file_permissions',
                'error_message' => sprintf(_ws("File could not be saved due to insufficient write permissions for the %s directory."), $p),
            ];
            return;
        }

        if (!$f->moveTo($path)) {
            $blockpage_file_model->deleteById($file_id);
            $this->errors = [
                'error_code' => 'upload_failed',
                'error_message' => sprintf(_ws('Failed to upload file %s.'), $f->name),
            ];
            return false;
        }
        return $file_id;
    }

    protected function transliterateFilename(waRequestFile $f, $is_image)
    {
        $name = $f->name;
        if (stripos(PHP_OS, 'WIN') === 0 && !preg_match('//u', $name)) {
            $tmp_name = @iconv('windows-1251', 'utf-8//ignore', $name);
            if ($tmp_name) {
                $name = $tmp_name;
            }
        }
        $name = waLocale::transliterate($name);
        if ($name) {
            $name = preg_replace('~[^a-z0-9._-]+~i', '-', $name);
        }
        if (!$name) {
            $name = '-';
        }
        if (substr($name, -1-strlen($f->extension)) !== '.'.$f->extension) {
            $name .= '.'.$f->extension;
        }
        if ($name === '-.'.$f->extension) {
            if ($is_image) {
                return "image.".$f->extension;
            } else {
                return "file.".$f->extension;
            }
        }
        return $name;
    }
}
