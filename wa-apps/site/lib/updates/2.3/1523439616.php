<?php
$files = array(
    'lib/classes/siteFrontend.class.php',
    'lib/actions/helper/siteHelper.action.php',
);

foreach ($files as $f) {
    waFiles::delete($this->getAppPath($f));
}