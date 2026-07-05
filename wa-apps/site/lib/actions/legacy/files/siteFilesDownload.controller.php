<?php

class siteFilesDownloadController extends waController
{
    public function execute()
    {
        $path = rtrim(waRequest::get('path'), ' /');
        $path = wa()->getDataPath($path, true, null, false);
        $file = waRequest::get('file');
        $path .= '/'.$file;

        if (preg_match('@\.\.[\\/]@', $path)) {
            throw new waException("File not found", 404);
        }

        if (file_exists($path) && is_file($path) && !in_array(waFiles::extension($path), array('php', 'phtml'))) {
            waFiles::readFile($path, $file);
        } else {
            throw new waException("File not found", 404);
        }
    }
}
