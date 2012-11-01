<?php

/**
 * Fix .htaccess - add -MultiViews
 * Rename webasyst.php to wa.php
 */

$path = $this->getRootPath().'/.htaccess';

if (file_exists($path)) {
    $content = file_get_contents($path);

    if (strpos($content , '-MultiViews') === false) {
        $content = str_replace('Options -Indexes', 'Options -Indexes -MultiViews', $content);
        file_put_contents($path, $content);
    }
}

$old_path = $this->getRootPath().'/webasyst.php';
$new_path = $this->getRootPath().'/wa.php';

if (file_exists($old_path)) {
    // new file already exists, just delete old file
    if (file_exists($new_path)) {
        unlink($old_path);
    }
    // rename old file
    else {
        rename($old_path, $new_path);
    }
}
