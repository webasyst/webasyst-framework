<?php

class siteFilesDownloadController extends waController
{
    public function execute()
    {
        $path = rtrim(waRequest::get('path'), ' /');
        $path = wa()->getDataPath($path, true, null, false);
        $file = waRequest::get('file');
        $path .= '/'.$file;
        
        if (file_exists($path) && is_file($path) && substr($path, -4) !== '.php') {
            waFiles::readFile($path, $file);
        } else {
            throw new waException("File not found", 404);
        }
        
    }
}