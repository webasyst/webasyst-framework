<?php

$path = wa()->getDataPath(null, true, 'photos');
waFiles::copy($this->getAppPath('lib/config/data/.htaccess'), $path.'/.htaccess');
waFiles::write($path.'/thumb.php', '<?php
$file = realpath(dirname(__FILE__)."/../../../")."/wa-apps/photos/lib/config/data/thumb.php";

if (file_exists($file)) {
    include($file);
} else {
    header("HTTP/1.0 404 Not Found");
}
');