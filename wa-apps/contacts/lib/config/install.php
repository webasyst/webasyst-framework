<?php 

$path = wa()->getDataPath('photo', true, 'contacts');
waFiles::create($path);

$data = <<<DATA
<ifModule mod_rewrite.c>
    RewriteEngine On
    #RewriteBase /wa-data/public/contacts/photo/

    RewriteCond %{REQUEST_URI} \.jpg$
    RewriteCond %{REQUEST_FILENAME} !-f
	RewriteRule ^(.*)$ thumb.php [L,QSA]
</ifModule>
DATA;

file_put_contents($path.'/.htaccess', $data);

if (!copy($this->getAppPath('lib/config/data/thumb.php'), $path.'/thumb.php')) {
    $error = sprintf('Installation could not be completed due to the insufficient file write permissions for the %s folder.', $path);
    if(class_exists('waLog')) {
        waLog::log(basename(__FILE__).': '.$error,'contacts-install.log');
    }
    throw new waException($error);
}