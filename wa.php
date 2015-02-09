#!/usr/bin/php
<?php

if (!isset($argc) || php_sapi_name() !== 'cli') {
    die("Run from CLI only!");
}

require_once(dirname(__FILE__).'/wa-config/SystemConfig.class.php');
$wa = waSystem::getInstance(null, new SystemConfig('cli'));
// run cli
array_splice($argv, 1, 0, 'webasyst');
$wa->dispatchCli($argv);

