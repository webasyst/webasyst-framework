<?php

$files = array(
    '/plugins/emailsubscription/lib/actions/blogEmailsubscriptionPluginBackendSettings.action.php',
    '/plugins/category/templates/actions/backend/'
);

foreach ($files as $file) {
    waFiles::delete(wa()->getAppPath($file, 'blog'), true);
}
