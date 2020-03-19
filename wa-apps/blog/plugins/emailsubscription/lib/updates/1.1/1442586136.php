<?php

$files = array(
    'plugins/emailsubscription/lib/actions/blogEmailsubscriptionPluginBackendSettings.action.php',
);

foreach ($files as $file) {
    waFiles::delete(wa()->getAppPath($file, 'blog'), true);
}
