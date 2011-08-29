<?php

$path = wa()->getConfig()->getRootPath().'/.htaccess';

if (file_exists($path)) {
    $content = file_get_contents($path);
    
    // remove *.ico from rule for static
    $content = str_replace('|ico', '', $content);
    file_put_contents($path, $content);
}

