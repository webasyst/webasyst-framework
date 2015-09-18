<?php

$files = array(
    '/plugins/category/lib/actions/blogCategoryPluginBackendSettings.action.php',
    '/plugins/category/templates/actions/backend/BackendSettings.html'
);

foreach ($files as $file) {
    waFiles::delete(wa()->getAppPath($file, 'blog'), true);
}
