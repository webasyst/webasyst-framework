<?php

if (php_sapi_name() !== "cli") {
    die("Run from CLI only!");
}

require_once(dirname(__FILE__).'/../wa-config/SystemConfig.class.php');

if (count($argv) < 3) {
    die("Use\r\n".realpath(dirname(__FILE__).'/../')."/cli.php  APP CLASS PARAMS\r\n");
}

try {
    $config = new SystemConfig('cli');
    waSystem::getInstance(null, $config)->dispatchCli($argv);
} catch (Exception $e) {
    waLog::log($e, "cli.log");
    if (waSystemConfig::isDebug()) {
        echo $e;
    }
}
