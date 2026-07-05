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
        $files = array();
        if ($dh) {
            while (($f = readdir($dh)) !== false) {
                if ($f !== '.' && $f !== '..') {
                    $files[] = [
                        'name' => $f,
                        'is_file' => is_file($path.'/'.$f)
                    ];
                }
            }
            closedir($dh);
        }

        // Сортировка по типу (сначала папки, потом файлы)
        usort($files, function($a, $b) {
            if (!$a['is_file'] && $b['is_file']) {
                return -1; // Папка должна быть выше файла
            } elseif ($a['is_file']  && !$b['is_file']) {
                return 1;
            } else {
                return strnatcasecmp($a['name'], $b['name']);
            }
        });

        $limit = 100;
        $n = count($files);
        $page = waRequest::get('page', 1);
        $files = array_slice($files, ($page - 1) * $limit, 100);

        $files_page = [];
        foreach ($files as $f) {
            $name = $f['name'];
            $is_file = $f['is_file'];

            $type = 'folder';
            if ($is_file) {
                $type = $this->getType($name);
            }

            $t = filemtime($path.'/'.$name);
            $files_page[] = array(
                'file' => htmlspecialchars($name),
                'type' => $type,
                'size' => filesize($path.'/'.$name),
                'timestamp' => $t,
                'datetime' => waDateTime::format('humandatetime', $t),
                'is_file' => $is_file,
            );
        }

        $this->response['pages'] = ceil((float)$n / $limit);
        $this->response['files'] = $files_page;
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
