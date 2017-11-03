<?php

class siteFilesDeleteController extends waJsonController
{
    public function execute()
    {
        $path = rtrim(waRequest::post('path'), ' /');

        $file = waRequest::post('file');

        try {
            if ($file) {
                if (!is_array($file)) {
                    $file = array($file);
                }
                $count = 0;
                foreach ($file as $f) {
                    if (strlen($f)) {
                        $f = $path.'/'.$f;
                        waFiles::delete(wa()->getDataPath($f, true, null, false));
                        $count++;
                    }
                }
                if ($count) {
                    $this->log('file_delete', $count);
                }
            } else if ($path) {
                $path = wa()->getDataPath($path, true, null, false);
                if (!is_writable($path)) {
                    $this->errors = sprintf(_w("Folder could not bet deleted due to the insufficient permissions."), $path);
                } else {
                    waFiles::delete($path);
                    $this->log('file_delete', 1);
                }
            }
        } catch (Exception $e) {
            $this->errors = $e->getMessage();
        }
    }
}