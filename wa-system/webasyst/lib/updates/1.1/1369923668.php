<?php

/**
 * Fix .htaccess - add -MultiViews
 * Rename webasyst.php to wa.php
 */

$path = $this->getRootPath().'/.htaccess';

if (file_exists($path)) {
    $content = file_get_contents($path);

    if (strpos($content, 'FilesMatch "\.md5$"') === false) {
        $content = '
<FilesMatch "\.md5$">
    Deny from all
</FilesMatch>

'.$content;
        file_put_contents($path, $content);
    }
}
