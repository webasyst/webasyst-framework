<?php

class siteFilemanagerListController extends waJsonController
{
    public function execute()
    {
        $path = rtrim(waRequest::post('path'), ' /');
        $path = wa()->getDataPath($path, true, null, false);

        if (!file_exists($path)) {
            throw new waException("File not found", 404);
        }

        $dh = opendir($path);
        $names = array();
        if ($dh) {
            while (($f = readdir($dh)) !== false) {
                if ($f !== '.' && $f !== '..') {
                    $names[] = $f;
                }
            }
            closedir($dh);
        }

        $n = count($names);
        $limit = 100;
        $page = waRequest::get('page', 1);
        $names = array_slice($names, ($page - 1) * $limit, 100);
        $files = array();

        foreach ($names as $name) {
            $f = $name;
            $t = filemtime($path.'/'.$f);
            $is_file = is_file($path.'/'.$f);
            $type = 'folder';
            if ($is_file) {
                $type = $this->getType($f);
            }

            $files[] = array(
                'file' => htmlspecialchars($name),
                'type' => $type,
                'size' => filesize($path.'/'.$f),
                'timestamp' => $t,
                'datetime' => waDateTime::format('humandatetime', $t),
                'is_file' => $is_file
            );
        }

        // Сортировка по типу (сначала папки, потом файлы)
        usort($files, function($a, $b) {
            if ($a['type'] === 'folder' && $b['type'] !== 'folder') {
                return -1; // Папка должна быть выше файла
            } elseif ($a['type'] !== 'folder' && $b['type'] === 'folder') {
                return 1;
            } else {
                return strnatcasecmp($a['file'], $b['file']);
            }
        });

        $this->response['pages'] = ceil((float)$n / $limit);
        $this->response['files'] = $files;
    }

    protected function getType($file)
    {
        if (($i = strrpos($file, '.')) !== false) {
            $ext = strtolower(substr($file, $i + 1));
            switch ($ext) {
                case 'jpg': case 'jpeg': case 'png': case 'gif': case 'ico':
                    return 'file-image';
                case 'txt': case 'odt': case 'pdf': case 'doc':
                    return 'file-alt';
                case 'php':
                    return 'file-code';
                case 'zip': case 'rar': case 'gz': case '7z': case 'tar':
                    return 'file-archive';
                case 'js':
                    return 'file-code';
                case 'css':
                    return 'file-code';
                case 'html': case 'htm': case 'tpl':
                    return 'file-code';
            }
        }
        return 'file-alt';
    }
}
