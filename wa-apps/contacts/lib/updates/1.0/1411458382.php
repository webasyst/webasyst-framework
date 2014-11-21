<?php

// rm cli files
$path = wa('contacts')->getAppPath().'/lib/cli/';
$files = array(
    'contactsGeocoding.cli.php'
);

foreach ($files as $f) {
    $file = $path . $f;
    if (file_exists($file)) {
        waFiles::delete($file, true);
    }
}