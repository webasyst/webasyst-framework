<?php

/**
 * Download a .zip file containing template.
 */
class mailerTemplatesExportController extends waJsonController
{
    public function execute()
    {
        if (!mailerHelper::isAdmin()) {
            throw new waException('Access denied.', 403);
        }
        if (!class_exists('ZipArchive')) {
            throw new waException(_w('ZipArchive class not found. Template import is not available on your server.'), 500);
        }

        $id = waRequest::request('id', 0, 'int');
        if (!$id) {
            throw new waException('Not found.', 404);
        }
        $tm = new mailerTemplateModel();
        $tmpl = $tm->getById($id);
        if (!$tmpl) {
            throw new waException('Not found.', 404);
        }

        $temp_path = tempnam(wa()->getTempPath(), 'tmpl_export_'.$id);

        $archive = new ZipArchive();
        $archive->open($temp_path, ZipArchive::CREATE);

        // Human-readable .zip and .html file name
        $file_name = trim(waLocale::transliterate(ifempty($tmpl['subject'], 'template'.$id)));
        $file_name = trim(preg_replace('~[^a-z0-9]+~i', '_', $file_name), '_');

        // Add HTML file with template contents
        $url_prefix = wa()->getDataUrl('files/'.$id.'/', true, 'mailer', true);
        $contents = str_replace($url_prefix, $id.'/', $tmpl['body']);
        if (strpos($contents, '<body') === false) {
            $contents = "<html><head><title>".htmlspecialchars($tmpl['subject'])."</title></head><body>\n".$contents."\n</body></html>";
        }
        $archive->addFromString($file_name.".html", $contents);

        // Add images and other files
        $data_path = wa()->getDataPath('files/'.$id, true, 'mailer');
        if (file_exists($data_path) && is_dir($data_path)) {
            self::folderToZip($data_path, $archive, strlen(dirname($data_path)) + 1);
        }

        $archive->close();
        waFiles::readFile($temp_path, $file_name.'.zip', false);
        unlink($temp_path);
        exit;
    }

    /**
     * Add files and sub-directories in a folder to zip file.
     * @param string $folder
     * @param ZipArchive $zipFile
     * @param int $exclusiveLength Number of text to be exclusived from the file path.
     */
    private static function folderToZip($folder, $zipFile, $exclusiveLength) {
        $handle = opendir($folder);
        while (false !== ($f = readdir($handle))) {
            if ($f != '.' && $f != '..') {
                $filePath = "$folder/$f";
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    $zipFile->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }
}

