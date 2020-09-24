<?php

$_old_files = [
    waConfig::get('wa_path_system') . '/waid/waWebasystIDAuthController.class.php',
];

foreach ($_old_files as $_file) {
    if (file_exists($_file)) {
        try {
            waFiles::delete($_file);
        } catch (waException $exception) {

        }
    }
}
