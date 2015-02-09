<?php

class siteFilesListController extends waJsonController
{
    public function execute()
    {
        $path = rtrim(waRequest::post('path'), ' /');
        $path = wa()->getDataPath($path, true);

        if (!file_exists($path)) {
            throw new waException("File not found", 404);
        }

        $dh = opendir($path);
        $names = array();
        while (($f = readdir($dh)) !== false) {
            if ($f !== '.' && $f !== '..' && is_file($path.'/'.$f)) {
                $names[] = $f;
            }
        }
        natcasesort($names);
        $n = count($names);
        $limit = 100;
        $page = waRequest::get('page', 1);
        $names = array_slice($names, ($page - 1) * $limit, 100);

        $files = array();

        foreach ($names as $name) {
            $f = $name;
            $t = filemtime($path.'/'.$f);
            $files[] = array(
                'file' => htmlspecialchars($name),
                'type' => $this->getType($f),
                'size' => filesize($path.'/'.$f),
                'timestamp' => $t,
                'datetime' => waDateTime::format('humandatetime', $t)
            );
        }
        closedir($dh);
        $this->response['pages'] = ceil((float)$n / $limit);
        $this->response['files'] = $files;
    }

    protected function getType($file)
    {
        if (($i = strrpos($file, '.')) !== false) {
            $ext = strtolower(substr($file, $i + 1));
            switch ($ext) {
                case 'jpg': case 'jpeg': case 'png': case 'gif': case 'ico':
                    return 'image';
                case 'txt': case 'odt': case 'pdf': case 'doc':
                    return 'text';
                case 'php':
                    return 'script-php';
                case 'zip': case 'rar': case 'gz': case '7z': case 'tar':
                    return 'zip';
                case 'js':
                    return 'script-js';
                case 'css':
                    return 'script-css';
                case 'html': case 'htm': case 'tpl':
                    return 'script';
            }
        }
        return 'text';
    }
}