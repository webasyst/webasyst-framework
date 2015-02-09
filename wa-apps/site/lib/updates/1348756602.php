<?php

/**
 * Rename snippets to blocks
 */

// remove old files
$path = $this->getAppPath('lib/actions/snippets/');
if (file_exists($path)) {
    waFiles::delete($path);
}
$path = $this->getAppPath('templates/actions/snippets/');
if (file_exists($path)) {
    waFiles::delete($path);
}

// rename table
$model = new waModel();

$exists = false;
try {
    $model->exec("SELECT 1 FROM site_snippet WHERE 0");
    $exists = true;
} catch (waDbException $e) {
}

if ($exists) {
    $model->exec("RENAME TABLE `site_snippet` TO  `site_block`");
}

// change rights key
$sql = "UPDATE wa_contact_rights SET name = 'blocks' WHERE app_id = 'site' AND name = 'snippets'";
$model->exec($sql);



