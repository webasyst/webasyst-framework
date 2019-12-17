<?php

$_plugin_path = wa()->getConfig()->getRootPath() . '/wa-plugins/shipping/boxberry/';

$_files = array(
    'lib/classes/boxberryShippingCalculate.class.php',
    'lib/classes/boxberryShippingGetSchedule.class.php',
    'lib/classes/boxberryShippingHandbookManager.class.php',
    'lib/classes/boxberryShippingSaveSettings.class.php',
    'lib/classes/calculate/boxberryShippingCalculate.class.php',
);

foreach ($_files as $_file) {
    $_file_path = $_plugin_path . $_file;
    if (file_exists($_file_path)) {
        waFiles::delete($_file_path, true);
    }
}


