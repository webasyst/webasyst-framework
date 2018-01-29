<?php

class siteFilesRenameController extends waJsonController
{
    public function execute()
    {
        $p = $path = rtrim(waRequest::post('path'), ' /');
        if ($file = waRequest::post('file')) {
            $path .= '/'.$file;
        }
        $path = wa()->getDataPath($path, true, null, false);
        $name = trim(waRequest::post('name'));
        $name = preg_replace('!\.\.[/\\\]!','', $name);
        if ($file) {
            $name_ext = waFiles::extension($name);
            if ($name_ext != waFiles::extension($file) || !$name_ext) {
                if (strpos(strtolower($name), '.php') !== false) {
                    if ($name_ext != 'php') {
                        $name_ext = 'php';
                    }
                }
                if (in_array($name_ext, array('php', 'phtml', 'htaccess'))) {
                    $this->errors = sprintf(_w("Files with extension .%s are not allowed to security considerations."), $name_ext);
                    return;
                }
            }
        }
        if (file_exists($path) && strlen($name)) {
            if (!is_writable(dirname($path))) {
                $this->errors = sprintf(_w("Folder or file could not bet renamed due to the insufficient file write permissions for the %s folder."), $p);
            } elseif (@rename($path, dirname($path).'/'.$name)) {
                if ($file) {
                    $this->response = $name;
                } else {
                    $this->response = array(
                        'name' => $name,
                        'hash' => '#/files'.substr(dirname($path).'/'.$name.'/', strlen(wa()->getDataPath('', true, null, false)))
                    );
                }
            } else {
                $this->errors = _w("File (folder) can not be renamed");
            }
        } else {
            if (!strlen($name)) {
                $this->errors = _w("Enter the new name");
            } else {
                $this->errors = _w("Selected folder (file) does not exist anymore");
            }
        }
    }
}