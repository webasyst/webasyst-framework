<?php
$files = array(
    'lib/classes/blockpage/siteBlockpageTemplates.class.php',
);
foreach ($files as $f) {
    waFiles::delete($this->getAppPath($f));
}
//$this->clearCache();