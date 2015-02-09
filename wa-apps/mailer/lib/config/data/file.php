<?php

// Start up the WA framework
$path = realpath(dirname(__FILE__)."/../../../../../")."/wa-config/SystemConfig.class.php";
if (!file_exists($path)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}
require_once($path);
$config = new SystemConfig();
waSystem::getInstance(null, $config);

// Try to get log_id from request file name.
// The format is: {real_file_name}.{log_id}.{real_ext}, e.g.: my.1234.jpg
$file = wa()->getConfig()->getRequestUrl(true);
$file = explode('.', urldecode($file));
$n = count($file);
if ($n > 2) {
    $log_id = $file[$n-2];
    unset($file[$n-2]);
    $file = implode('.', $file);
    $path = wa()->getDataPath("files/", true, "mailer");

    if (file_exists($path.$file)) {
        // Mark message as viewed by recipient (identified by log_id)
        if (is_numeric($log_id)) {
            require(wa()->getAppPath('lib/models/mailerMessageLog.model.php', 'mailer'));
            $log_model = new mailerMessageLogModel();
            $log = $log_model->getById($log_id);
            if ($log && $log['status'] < 3) {
                $log_model->setStatus($log_id, 3);
            }
        }

        // Send file to browser
        header('X-Powered-By: ');
        waFiles::readFile($path.$file);
        exit;
    }
}

// The original requested file does not exist, since the .htaccess specifies RewriteCond %{REQUEST_FILENAME} !-f
// So it is safe to return 404 here.
header("HTTP/1.0 404 Not Found");
exit;

