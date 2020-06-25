<?php

$_waid_config_path = waConfig::get('wa_path_config') . '/waid.php';

if (file_exists($_waid_config_path)) {
    $_waid_config = include($_waid_config_path);

    // change scheme of urls: http:// => https://
    $_changed = false;
    foreach ($_waid_config as $_key => $_url) {
        $_new_url = str_replace('http://', 'https://', $_url);
        if ($_new_url !== $_url) {
            $_waid_config[$_key] = $_new_url;
            $_changed = true;
        }
    }

    if ($_changed) {
        waUtils::varExportToFile($_waid_config, $_waid_config_path);
    }

}
