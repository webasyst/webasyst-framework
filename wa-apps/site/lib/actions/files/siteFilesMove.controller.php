<?php

class siteFilesMoveController extends waJsonController
{
    public function execute()
    {
        $path = rtrim(waRequest::post('path'), ' /');
        $path = wa()->getDataPath($path, true, null, false);
        $hash = $new_path = waRequest::post('new_path');
        $new_path = wa()->getDataPath($new_path, true, null, false).($new_path ? '' : '/');

        if (!is_writable($new_path)) {
            $this->errors = sprintf(_w("Files could not bet moved due to the insufficient file write permissions for the %s folder."), rtrim($hash, '/'));
            return;
        }
        
        if ($file = waRequest::post('file')) {
            if (!is_array($file)) {
                $file = array($file);
            }
            foreach ($file as $f) {
                if (!@rename($path."/".$f, $new_path.$f)) {
                    $this->errors[] = sprintf(_w("Can not move file “%s” to a new location"), $f);    
                }
            }
            if ($this->errors && is_array($this->errors)) {
                $this->errors = implode(";\r\n", $this->errors);
            }
        } else {
            $new_path .= basename($path);
            $hash .= basename($path)."/";
            if (@rename($path, $new_path)) {
                $this->response['hash'] = $hash;
            } else {
                $this->errors = _w("Can not move to a new location");
            }
        }
    }
}