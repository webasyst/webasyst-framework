<?php

/**
 * First step of template import: uploading form for zip-archives.
 */
class mailerTemplatesImport1Action extends waViewAction
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }
        // Check that we can actually unzip the archive.
        if (!class_exists('ZipArchive')) {
            throw new waException(_w('ZipArchive class not found. Template import is not available on your server.'), 500);
        }

        // Delete old uploaded file, if exists
        $file = new mailerUploadedFile('template_import');
        $file->delete();
    }
}

