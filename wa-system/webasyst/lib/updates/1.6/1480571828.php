<?php

/** @var webasystConfig $config */
$config = $this;

$app_path = $config->getAppPath();
$files = array(
    '/lib/actions/profile/webasystProfileDeletePhoto.controller.php',
    '/lib/actions/profile/webasystProfileLoc.controller.php',
    '/lib/actions/profile/webasystProfilePhoto.controller.php',
    '/lib/actions/profile/webasystProfileTmpimage.controller.php',
);
foreach($files as $file) {
    $file = $app_path.$file;
    if (file_exists($file)) {
        waFiles::delete($file);
    }
}
