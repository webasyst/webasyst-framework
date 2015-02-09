<?php

/**
 * Second step of template import: read archive, allow user to select an HTML file from it and create template from that file.
 */
class mailerTemplatesImport2Action extends waViewAction
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

        $file = new mailerUploadedFile('template_import');
        if (!$file->uploaded()) {
            throw new waException('No file uploaded.', 404);
        }

        // Read a list of HTML files from archive.
        $error_msg = '';
        $html_files = array();
        $archive = new ZipArchive();
        if (!$archive->open($file->tmp_name)) {
            $error_msg = _w('Unable to open archive file.');
        } else {
            $html_files = self::getHtmlFiles($archive);
            if (!$html_files) {
                $error_msg = _w('Archive (zip) file for template import must contain an html file in the root of archive.');
            }
        }

        // Select a file to import.
        // When there's only one file, or when user specified a file to import, then import it.
        $import_index = null;
        if (!$error_msg) {
            if (count($html_files) == 1) {
                $import_index = key($html_files);
            } else {
                $import_index = waRequest::request('import_index', null, 'int');
                if (empty($html_files[$import_index])) {
                    $import_index = null;
                }
            }
        }

        // Import template from file.
        $template_id = null;
        if ($import_index !== null) {
            $template_id = self::importTemplateFile($archive, $import_index, $error_msg);
        }

        $archive->close();

        // Show result to user.
        if ($template_id) {
            // Redirect to template editor
            echo '<script>window.location.hash = "#/template/'.$template_id.'/"; $(".dialog").trigger("close"); $.wa.mailer.reloadSidebar(); </script>';
            exit;
        } else if ($error_msg) {
            // Show error
            $this->view->assign('error_msg', $error_msg);
            $this->template = 'templates/actions/templates/TemplatesImport1.html';
        } else {
            // Show list of files to select from
            $this->view->assign('html_files', $html_files);
        }
    }

    protected static function stripExt($filename)
    {
        $filename = explode('.', $filename);
        if (count($filename) > 1) {
            array_pop($filename);
        }
        return implode('.', $filename);
    }

    /** @return array file_index => file name: list of HTML files inside the archive. */
    protected static function getHtmlFiles($archive)
    {
        $html_files = array();
        for($i = 0; $i < $archive->numFiles; $i++) {
            $filename = $archive->getNameIndex($i);
            if (dirname($filename) == '.' && in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), array('html', 'htm'))) {
                $html_files[$i] = $filename;
            }
        }
        return $html_files;
    }

    /** Save given HTML file from archive as a campaign template. */
    protected static function importTemplateFile($archive, $import_index, &$error_msg)
    {
        // Get template contents
        $html = $archive->getFromIndex($import_index);
        if (!$html) {
            $error_msg = _w('Unable to extract template file.');
            return null;
        }

        // Remove UTF bit order mask, if present
        if (substr($html, 0, 3) === pack("CCC", 0xef, 0xbb, 0xbf)) {
            $html = substr($html, 3);
        }

        // Template contents to save to DB
        $data = array();
        $data['name'] = '';
        $data['body'] = _w('Error importing template.');
        $data['subject'] = self::stripExt(basename($archive->getNameIndex($import_index)));
        $data['is_template'] = 1;
        $data['create_datetime'] = date("Y-m-d H:i:s");
        $data['create_contact_id'] = wa()->getUser()->getId();
        if (empty($data['subject']) && preg_match('~<title>([^<]+)</title>~', $html, $m)) {
            $data['subject'] = $m[1];
        }

        // save template
        $tm = new mailerTemplateModel();
        $template_id = $tm->insert($data);
        $mpm = new mailerMessageParamsModel();
        $mpm->save($template_id, array('sort' => 1));

        // For each file in archive search its filename in template HTML.
        // When found, mark this file to extract later and replace its URL in template.
        $data_path = wa()->getDataPath('files/'.$template_id.'/', true, 'mailer');
        $url_prefix = wa()->getDataUrl('files/'.$template_id.'/', true, 'mailer', true);
        $url_prefix = str_replace('https://', 'http://', $url_prefix);
        $files_to_extract = array();
        $preview_image = null;
        for($i = 0; $i < $archive->numFiles; $i++) {
            if ($i == $import_index) {
                continue;
            }
            $archive_path = $archive->getNameIndex($i);
            if (substr($archive_path, -1) == '/') {
                continue;
            }
            if (false !== strpos($html, $archive_path)) {
                $html = str_replace(array('./'.$archive_path, $archive_path), '{REPLACE_WITH_PATH}', $html);
                $html = str_replace('{REPLACE_WITH_PATH}', $url_prefix.$archive_path, $html);
                $files_to_extract[$archive_path] = $archive->statName($archive_path);
            }
            // Look for a template preview named <template_file_name>_preview
            if (!$preview_image && self::stripExt(basename($archive_path)) == $data['subject'].'_preview') {
                $preview_image = $data_path.$archive_path;
                $files_to_extract[$archive_path] = $archive->statName($archive_path);
            }
        }

        // Extract assets and save modified template text.
        if ($files_to_extract) {
            if (file_exists($data_path)) {
                waFiles::delete($data_path);
            }
            waFiles::create($data_path);
            $archive->extractTo($data_path, array_keys($files_to_extract));

            // Use the largest image as a template preview, when not found otherwise.
            if (!$preview_image) {
                $size = 0;
                foreach($files_to_extract as $file_data) {
                    $ext = strtolower(pathinfo($file_data['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif'))) {
                        if ($file_data['size'] > $size) {
                            $size = $file_data['size'];
                            $preview_image = $data_path.$file_data['name'];
                        }
                    }
                }
            }

            // Save template preview when found.
            // !!! Preview functionality is (temporarily?..) disabled.
            if (false && $preview_image) {
                $full_path = mailerHelper::getTemplatePreviewFile($template_id, true);
                waFiles::create(dirname($full_path));
                try {
                    // Resize and save image
                    $image = waImage::factory($preview_image);
                    if ($image->width > 213) {
                        $image->resize(213);
                    }
                    if ($image->height > 128) {
                        $image->crop(213, 128, 0, 0);
                    }
                    $image->save($full_path);
                } catch(Exception $e) { }
            }
        }

        // Remove title
        //$html = preg_replace('~<title>[^<]+</title>~iusm', '<title></title>', $html);

        // Replace <style> blocks with inline styles
        $emo = new mailerEmogrifier($html);
        @$html = $emo->emogrify();

        // Finally save the template HTML
        $tm->updateById($template_id, array(
            'body' => $html,
        ));

        return $template_id;
    }

    public static function importFirst($archive_filename)
    {
        if (!class_exists('ZipArchive')) {
            return false;
        }

        $archive = new ZipArchive();
        if (!$archive->open($archive_filename)) {
            return false;
        }

        $html_files = self::getHtmlFiles($archive);
        if (!$html_files) {
            $archive->close();
            return false;
        }

        $error_msg = '';
        $import_index = key($html_files);
        $result = self::importTemplateFile($archive, $import_index, $error_msg);
        $archive->close();

        return $result;
    }
}

