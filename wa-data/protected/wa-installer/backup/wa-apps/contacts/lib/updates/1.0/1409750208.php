<?php

$path = wa()->getDataPath('photos', true, 'contacts', false);
if (!file_exists($path.'./htaccess')) {
    $path = wa()->getDataPath('photos', true, 'contacts');
    waFiles::write($path.'/thumb.php', '<?php
    $file = realpath(dirname(__FILE__)."/../../../../")."/wa-apps/contacts/lib/config/data/thumb.php";

    if (file_exists($file)) {
        include($file);
    } else {
        header("HTTP/1.0 404 Not Found");
    }
    ');
    waFiles::copy(wa()->getAppPath('lib/config/data/.htaccess', 'contacts'), $path.'/.htaccess');
}