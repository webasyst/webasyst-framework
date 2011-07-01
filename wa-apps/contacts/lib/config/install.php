<?php 

$path = wa()->getDataPath('photo', true, 'contacts');
waFiles::create($path);

$data = <<<DATA
<ifModule mod_rewrite.c>
    RewriteEngine On

    RewriteCond %{REQUEST_URI} \.jpg$
    RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*)$ thumb.php [L,QSA]
</ifModule>
DATA;

file_put_contents($path.'/.htaccess', $data);

copy($this->getAppPath('lib/config/data/thumb.php'), $path.'/thumb.php');