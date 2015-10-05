<?php

$files = array(
    'js/blogPlugins.js',
    'lib/actions/plugins/blogPlugins.action.php',
    'lib/actions/plugins/blogPluginsSettings.action.php',
    'lib/actions/plugins/blogPluginsSort.controller.php',
    'templates/actions/plugins/',
);

foreach ($files as $f) {
    waFiles::delete($this->getAppPath($f));
}