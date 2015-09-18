<?php

$files = array(
    'lib/actions/plugins/photosPlugins.action.php',
    'lib/actions/plugins/photosPlugins.controller.php',
    'lib/actions/plugins/photosPluginsSave.controller.php',
    'lib/actions/plugins/photosPluginsSettings.action.php',
    'lib/actions/plugins/photosPluginsSort.controller.php',
    'templates/actions/plugins/',
);

foreach ($files as $f) {
    waFiles::delete($this->getAppPath($f));
}