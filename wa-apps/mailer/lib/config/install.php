<?php

// Create script that tracks email openings using embedded image
$path = wa()->getDataPath('files', true, 'mailer');
waFiles::create($path);
copy($this->getAppPath('lib/config/data/.htaccess'), $path.'/.htaccess');
file_put_contents($path.'/file.php', '<?php
$file = realpath(dirname(__FILE__)."/../../../../")."/wa-apps/mailer/lib/config/data/file.php";
if (file_exists($file)) {
    include($file);
} else {
    header("HTTP/1.0 404 Not Found");
}');

// Import pre-installed templates to database
$tmpls_dir = $this->getAppPath('templates/preinstalled');
if ($tmpls_dir && is_readable($tmpls_dir) && is_dir($tmpls_dir) && class_exists('ZipArchive')) {
    $locale = wa()->getLocale();
    foreach(waFiles::listdir($tmpls_dir) as $tmpl_name) {
        $tmpl_dir = $tmpls_dir.'/'.$tmpl_name;
        if (is_readable($tmpl_dir) && is_dir($tmpl_dir)) {
            $files = array_fill_keys(waFiles::listdir($tmpl_dir), 1);
            $file = null;
            foreach(array("{$tmpl_name}.{$locale}.zip", "{$tmpl_name}.zip") as $f) {
                if (!empty($files[$f])) {
                    $file = $f;
                    break;
                }
            }
            if ($file) {
                $file = $tmpl_dir.'/'.$file;
                if (is_readable($file)) {
                    mailerTemplatesImport2Action::importFirst($file);
                }
            }
        }
    }
}

