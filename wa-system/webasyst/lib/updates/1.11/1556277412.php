<?php

$file_paths = array(
    wa()->getConfig()->getRootPath().'/wa-system/design/templates/ThemeUpdate.html',
);

foreach ($file_paths as $_file_path) {
    if (file_exists($_file_path)) {
        try {
            waFiles::delete($_file_path);
        } catch (Exception $e) {
        }
    }
}

waAppConfig::clearAutoloadCache('webasyst');