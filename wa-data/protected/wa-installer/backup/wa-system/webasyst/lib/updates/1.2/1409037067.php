<?php

$path = $this->getRootPath().'/.htaccess';

if (file_exists($path)) {
    $content = file_get_contents($path);

    if (strpos($content, 'RewriteCond %{REQUEST_URI} apple-touch-icon\.png') === false) {
        $content = str_replace('RewriteCond %{REQUEST_URI} !\.(js|css|jpg|jpeg|gif|png)$', "RewriteCond %{REQUEST_URI} !\.(js|css|jpg|jpeg|gif|png)$ [or]\n    RewriteCond %{REQUEST_URI} apple-touch-icon\.png$", $content);
        file_put_contents($path, $content);
    }
}