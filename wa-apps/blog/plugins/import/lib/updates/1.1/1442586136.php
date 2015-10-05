<?php

$files = array(
    '/plugins/import/lib/actions/backend/blogImportPluginBackendSettings.action.php',
    '/plugins/import/templates/actions/backend/'
);

foreach ($files as $file) {
    waFiles::delete(wa()->getAppPath($file, 'blog'), true);
}