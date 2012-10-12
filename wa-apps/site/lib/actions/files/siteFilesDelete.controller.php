<?php

class siteFilesDeleteController extends waJsonController
{
    public function execute()
    {
        $p = $path = rtrim(waRequest::post('path'), ' /');
        $file = waRequest::post('file');
        try {
            if ($file) {
                if (!is_array($file)) {
                    $file = array($file);
                }
                foreach ($file as $f) {
                    $f = $path.'/'.$f;
                    waFiles::delete(wa()->getDataPath($f, true, null, false));
                }
                $this->log('file_delete', count($file));
            } else {
                $path = wa()->getDataPath($path, true, null, false);
                if (!is_writable($path)) {
                    $this->errors = sprintf(_w("Folder could not bet deleted due to the insufficient permissions."), $p);
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