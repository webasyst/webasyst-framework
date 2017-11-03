<?php

class siteFilesAddFolderController extends waJsonController
{
    public function execute()
    {
        $p = $path = rtrim(waRequest::post('path'), ' /');
        $path = wa()->getDataPath($path, true, null, false);
        $folder = waRequest::post('name');
        $folder = waLocale::transliterate($folder);
        $folder = preg_replace('/[^a-zA-Z0-9_\-]+/', '-', $folder);
        $folder = trim($folder, '-');

        if (file_exists($path)) {
            if (!is_writable($path)) {
                $this->errors = sprintf(_w("Folder could not bet created due to the insufficient file write permissions for the %s folder."), $p);
            } elseif (@mkdir($path.'/'.$folder)) {
                $this->response = htmlspecialchars($folder);
            } else {
                $this->errors = _w("An unkown error occured when attempting to create a folder.");
            }
        }
    }
}