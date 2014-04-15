#!/usr/bin/php
<?php

if (PHP_SAPI !== 'cli') {
    die("Run from CLI only!");
}

require_once dirname(__FILE__).'/wa-config/SystemConfig.class.php';
$wa = waSystem::getInstance(null, new SystemConfig('cli'));
// Replace script name
$_SERVER['argv'][0] = 'webasyst';
// Run CLI
$wa->dispatchCli($_SERVER['argv']);
